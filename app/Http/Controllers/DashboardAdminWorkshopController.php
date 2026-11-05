<?php

namespace App\Http\Controllers;

use App\Helpers\AuditRecorder;
use App\Helpers\WorkshopDivision;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Workshop;
use App\Models\WorkshopBlock;
use App\Models\WorkshopSession;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminWorkshopController extends Controller
{
    /**
     * Display the blocks, workshops and the assignment of a workshop booking event
     */
    public function index(Event $event): Response
    {
        $this->ensureWorkshopEvent($event);

        $registrations = $event->registrations()
            ->with(['user:id,firstname,lastname,email,course_id', 'user.course:id,abbreviation,classes', 'workshopPriorities', 'workshopAssignments'])
            ->get()
            ->map(fn (Registration $registration) => [
                'id' => $registration->id,
                'user' => $registration->user,
                'priorities' => $registration->workshopPriorities->pluck('workshop_id'),
                // session id by block id
                'assignments' => $registration->workshopAssignments->pluck('workshop_session_id', 'workshop_block_id'),
            ])
            ->sortBy(fn (array $registration) => mb_strtolower($registration['user']->firstname.' '.$registration['user']->lastname))
            ->values();

        return Inertia::render('Dashboard/Admin/Workshops', [
            'event' => $event,
            // the counts warn the admin before deleting priorities or assignments
            'blocks' => $event->workshopBlocks()->withCount('assignments')->get(),
            'workshops' => $event->workshops()->withCount('priorities')->with(['sessions' => fn ($query) => $query->withCount('assignments')])->get(),
            'registrations' => $registrations,
        ]);
    }

    /**
     * Update the amount of priorities of the event, only possible before the first registration
     */
    public function updateSettings(Event $event): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $validated = Request::validate([
            'workshop_priority_count' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($event->registrations()->exists() && $event->workshop_priority_count !== (int) $validated['workshop_priority_count']) {
            Session::flash('error', 'Die Anzahl der Prioritäten kann nach der ersten Anmeldung nicht mehr geändert werden.');

            return Redirect::back();
        }

        $event->update($validated);

        Session::flash('success', 'Die Einstellungen wurden gespeichert.');

        return Redirect::back();
    }

    /**
     * Create a block
     */
    public function storeBlock(Event $event): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $block = $event->workshopBlocks()->create($this->validateBlock());

        Session::flash('success', 'Der Zeitblock <strong>'.e($block->name).'</strong> wurde angelegt.');

        return Redirect::back();
    }

    /**
     * Update a block
     */
    public function updateBlock(Event $event, WorkshopBlock $workshopBlock): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $workshopBlock->update($this->validateBlock());

        Session::flash('success', 'Der Zeitblock <strong>'.e($workshopBlock->name).'</strong> wurde gespeichert.');

        return Redirect::back();
    }

    /**
     * Delete a block with its sessions and assignments
     */
    public function deleteBlock(Event $event, WorkshopBlock $workshopBlock): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $workshopBlock->delete();

        Session::flash('success', 'Der Zeitblock <strong>'.e($workshopBlock->name).'</strong> wurde gelöscht.');

        return Redirect::back();
    }

    /**
     * Create a workshop with its sessions
     */
    public function storeWorkshop(Event $event): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $validated = $this->validateWorkshop($event);

        $workshop = DB::transaction(function () use ($event, $validated) {
            $workshop = $event->workshops()->create(['name' => $validated['name'], 'description' => $validated['description'] ?? null]);
            $this->syncSessions($workshop, $validated['sessions']);

            return $workshop;
        });

        Session::flash('success', 'Der Workshop <strong>'.e($workshop->name).'</strong> wurde angelegt.');

        return Redirect::back();
    }

    /**
     * Update a workshop and its sessions
     */
    public function updateWorkshop(Event $event, Workshop $workshop): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $validated = $this->validateWorkshop($event);

        DB::transaction(function () use ($workshop, $validated) {
            $workshop->update(['name' => $validated['name'], 'description' => $validated['description'] ?? null]);
            $this->syncSessions($workshop, $validated['sessions']);
        });

        Session::flash('success', 'Der Workshop <strong>'.e($workshop->name).'</strong> wurde gespeichert.');

        return Redirect::back();
    }

    /**
     * Delete a workshop with its sessions, priorities and assignments
     */
    public function deleteWorkshop(Event $event, Workshop $workshop): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $workshop->delete();

        Session::flash('success', 'Der Workshop <strong>'.e($workshop->name).'</strong> wurde gelöscht.');

        return Redirect::back();
    }

    /**
     * Assign all registrations to the workshops by their priorities, replacing the previous assignment
     */
    public function run(Event $event): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        if ($event->areWorkshopsPublished()) {
            Session::flash('error', 'Die Einteilung ist veröffentlicht. Nimm die Veröffentlichung zurück, um neu einzuteilen.');

            return Redirect::back();
        }

        $summary = (new WorkshopDivision($event))->assign();

        // the assignments are replaced in bulk without audits of their own
        AuditRecorder::record($event, 'workshopsAssigned', [], [
            'assigned' => array_sum($summary['priorities']),
            'unassigned' => $summary['unassigned'],
            'cancelled_sessions' => $summary['cancelled_sessions'],
        ]);

        $priorities = collect($summary['priorities'])->map(fn (int $count, int $priority) => "Prio {$priority}: {$count}")->implode(', ');

        Session::flash('success', 'Die Einteilung ist abgeschlossen. '
            .($priorities ?: 'Es wurde niemand eingeteilt')
            .'. Ohne Workshop: '.$summary['unassigned']
            .'. Ausgefallene Termine: '.$summary['cancelled_sessions'].'.');

        return Redirect::back();
    }

    /**
     * Publish the assignment, so the users see their workshops
     */
    public function publish(Event $event): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $event->update(['workshops_published_at' => now()]);

        Session::flash('success', 'Die Einteilung wurde veröffentlicht.');

        return Redirect::back();
    }

    /**
     * Take back the publication of the assignment
     */
    public function unpublish(Event $event): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $event->update(['workshops_published_at' => null]);

        Session::flash('success', 'Die Veröffentlichung wurde zurückgenommen.');

        return Redirect::back();
    }

    /**
     * Assign a registration manually to a session of a block or remove it from the block. A full session is only a warning.
     */
    public function assign(Event $event): RedirectResponse
    {
        $this->ensureWorkshopEvent($event);

        $validated = Request::validate([
            'registration_id' => ['required', 'integer', Rule::exists('registrations', 'id')->where('event_id', $event->id)],
            'workshop_block_id' => ['required', 'integer', Rule::exists('workshop_blocks', 'id')->where('event_id', $event->id)],
            'workshop_session_id' => ['nullable', 'integer', Rule::exists('workshop_sessions', 'id')->where('workshop_block_id', Request::input('workshop_block_id'))],
        ]);

        $registration = Registration::with('user')->findOrFail($validated['registration_id']);
        $name = e($registration->user->firstname.' '.$registration->user->lastname);

        if (empty($validated['workshop_session_id'])) {
            $registration->workshopAssignments()->where('workshop_block_id', $validated['workshop_block_id'])->get()->each->delete();

            Session::flash('success', "<strong>{$name}</strong> hat in diesem Block keinen Workshop mehr.");

            return Redirect::back();
        }

        $session = WorkshopSession::with('workshop')->findOrFail($validated['workshop_session_id']);

        if (! $registration->assignWorkshopSession($session)) {
            Session::flash('error', "<strong>{$name}</strong> ist bereits in einem anderen Block im Workshop <strong>".e($session->workshop->name).'</strong>.');

            return Redirect::back();
        }

        Session::flash('success', "<strong>{$name}</strong> ist jetzt im Workshop <strong>".e($session->workshop->name).'</strong>.');

        $participantCount = $session->assignments()->count();
        if ($session->maximum_participants !== null && $participantCount > $session->maximum_participants) {
            Session::flash('warning', "Der Termin hat jetzt {$participantCount} von maximal {$session->maximum_participants} Personen.");
        } elseif ($session->is_cancelled) {
            Session::flash('warning', 'Der Termin ist als ausgefallen markiert.');
        }

        return Redirect::back();
    }

    /**
     * Abort if the event is not a workshop booking
     */
    private function ensureWorkshopEvent(Event $event): void
    {
        abort_unless($event->type === 'workshop_booking', 404);
    }

    /**
     * Validate a block. The browser sends the times in UTC, Eloquent stores them without conversion, so they are
     * converted to the timezone of the app first.
     *
     * @return array{name: string, starts_at: CarbonInterface, ends_at: ?CarbonInterface}
     */
    private function validateBlock(): array
    {
        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        foreach (['starts_at', 'ends_at'] as $field) {
            $validated[$field] = isset($validated[$field]) ? Date::parse($validated[$field])->setTimezone(config('app.timezone')) : null;
        }

        return $validated;
    }

    /**
     * Validate a workshop with its sessions. Every session belongs to another block of the event.
     *
     * @return array{name: string, description: ?string, sessions: list<array{workshop_block_id: int, location: ?string, minimum_participants: int, maximum_participants: ?int}>}
     */
    private function validateWorkshop(Event $event): array
    {
        return validator(Request::all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sessions' => ['present', 'array'],
            'sessions.*.workshop_block_id' => ['required', 'integer', 'distinct', Rule::exists('workshop_blocks', 'id')->where('event_id', $event->id)],
            'sessions.*.location' => ['nullable', 'string', 'max:255'],
            'sessions.*.minimum_participants' => ['required', 'integer', 'min:0'],
            'sessions.*.maximum_participants' => ['nullable', 'integer', 'min:1'],
        ], [
            'sessions.*.workshop_block_id.distinct' => 'Ein Workshop kann pro Zeitblock nur einmal stattfinden.',
        ])->after(function (Validator $validator) {
            foreach (Request::input('sessions', []) as $index => $session) {
                if (isset($session['maximum_participants'], $session['minimum_participants']) && (int) $session['maximum_participants'] < (int) $session['minimum_participants']) {
                    $validator->errors()->add("sessions.{$index}.maximum_participants", 'Das Maximum darf nicht kleiner als das Minimum sein.');
                }
            }
        })->validate();
    }

    /**
     * Create, update and delete the sessions of a workshop, so it only takes place in the given blocks.
     *
     * @param  list<array{workshop_block_id: int, location: ?string, minimum_participants: int, maximum_participants: ?int}>  $sessions
     */
    private function syncSessions(Workshop $workshop, array $sessions): void
    {
        $blockIds = array_map(fn (array $session) => (int) $session['workshop_block_id'], $sessions);

        $workshop->sessions()->whereNotIn('workshop_block_id', $blockIds)->get()->each->delete();

        foreach ($sessions as $session) {
            $workshop->sessions()->updateOrCreate(
                ['workshop_block_id' => $session['workshop_block_id']],
                [
                    'location' => $session['location'] ?? null,
                    'minimum_participants' => $session['minimum_participants'],
                    'maximum_participants' => $session['maximum_participants'] ?? null,
                ],
            );
        }
    }
}
