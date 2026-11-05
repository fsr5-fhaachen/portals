<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\WorkshopAssignment;
use App\Models\WorkshopSession;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Assignment of the registrations of a workshop booking event to the workshop sessions by the priorities of the users.
 *
 * Every user gets at most one workshop per block and no workshop twice. The registrations are drawn in a random order
 * (lottery) and get their wishes in rounds: first everyone gets the workshop of priority 1 if possible, then of priority 2
 * and so on. The order is reversed every round, so nobody is always first. Earlier sessions are filled first. A session
 * with less participants than its minimum is cancelled and its participants are distributed again. Users whose wishes
 * can not be fulfilled stay unassigned in a block. Registrations without wishes (e.g. assigned by an admin) are not part
 * of the lottery, they keep the workshops they were placed in by hand and take up those places. Their sessions are never
 * cancelled, the admin decided that they take place.
 */
class WorkshopDivision
{
    /**
     * Ids of the blocks ordered by time.
     *
     * @var list<int>
     */
    private array $blockIds = [];

    /**
     * Sessions by id.
     *
     * @var array<int, array{workshop_id: int, block_id: int, minimum: int, maximum: int}>
     */
    private array $sessions = [];

    /**
     * Session ids by workshop id and block id.
     *
     * @var array<int, array<int, int>>
     */
    private array $sessionIdsByWorkshopAndBlock = [];

    /**
     * Workshop ids ordered by priority of every registration.
     *
     * @var array<int, list<int>>
     */
    private array $prioritiesByRegistration = [];

    /**
     * Session ids by assignment id of every registration without wishes, these assignments are kept.
     *
     * @var array<int, array<int, int>>
     */
    private array $fixedSessionIdsByRegistration = [];

    /**
     * Amount of fixed participants by session id, only sessions with fixed participants.
     *
     * @var array<int, int>
     */
    private array $fixedParticipantCounts = [];

    /**
     * Registration ids in the order of the lottery.
     *
     * @var list<int>
     */
    private array $lotteryOrder = [];

    /**
     * Seed for the lottery, so the result is reproducible (e.g. in tests). Without seed the result is random.
     */
    public function __construct(private Event $event, private ?int $seed = null) {}

    /**
     * Assign the registrations to the sessions, replacing all existing assignments of the event.
     *
     * @return array{priorities: array<int, int>, unassigned: int, cancelled_sessions: int}
     */
    public function assign(): array
    {
        $this->load();

        $activeSessionIds = array_keys($this->sessions);
        $cancelledSessionIds = [];

        // cancel the session with the fewest participants below its minimum and distribute again, until all sessions are valid
        while (true) {
            [$assignments, $participantCounts] = $this->distribute($activeSessionIds);

            $sessionToCancel = $this->sessionBelowMinimum($activeSessionIds, $participantCounts);
            if ($sessionToCancel === null) {
                break;
            }

            $cancelledSessionIds[] = $sessionToCancel;
            $activeSessionIds = array_values(array_diff($activeSessionIds, [$sessionToCancel]));
        }

        // sessions without any participants do not take place either, if they need participants
        foreach ($activeSessionIds as $sessionId) {
            if ($participantCounts[$sessionId] === 0 && $this->sessions[$sessionId]['minimum'] > 0) {
                $cancelledSessionIds[] = $sessionId;
            }
        }

        $this->save($assignments, $cancelledSessionIds);

        return $this->summary($assignments, $cancelledSessionIds);
    }

    /**
     * Load the blocks, sessions, priorities and fixed assignments of the event and draw the lottery order.
     */
    private function load(): void
    {
        $this->sessions = [];
        $this->sessionIdsByWorkshopAndBlock = [];
        $this->prioritiesByRegistration = [];
        $this->fixedSessionIdsByRegistration = [];
        $this->fixedParticipantCounts = [];

        $this->blockIds = $this->event->workshopBlocks()->pluck('id')->all();
        $blockOrder = array_flip($this->blockIds);

        foreach ($this->event->workshopSessions()->get() as $session) {
            $this->sessions[$session->id] = [
                'workshop_id' => $session->workshop_id,
                'block_id' => $session->workshop_block_id,
                'minimum' => $session->minimum_participants,
                'maximum' => $session->maximum_participants ?? PHP_INT_MAX,
            ];
            $this->sessionIdsByWorkshopAndBlock[$session->workshop_id][$session->workshop_block_id] = $session->id;
        }

        // sessions of a workshop are tried in the order of the blocks, so earlier sessions are filled first
        foreach ($this->sessionIdsByWorkshopAndBlock as &$sessionIdsByBlock) {
            uksort($sessionIdsByBlock, fn (int $a, int $b) => $blockOrder[$a] <=> $blockOrder[$b]);
        }
        unset($sessionIdsByBlock);

        $registrations = $this->event->registrations()->with(['workshopPriorities', 'workshopAssignments'])->get();
        foreach ($registrations as $registration) {
            if ($registration->workshopPriorities->isEmpty()) {
                $this->fixedSessionIdsByRegistration[$registration->id] = $registration->workshopAssignments->pluck('workshop_session_id', 'id')->all();

                foreach ($this->fixedSessionIdsByRegistration[$registration->id] as $sessionId) {
                    $this->fixedParticipantCounts[$sessionId] = ($this->fixedParticipantCounts[$sessionId] ?? 0) + 1;
                }
            } else {
                $this->prioritiesByRegistration[$registration->id] = $registration->workshopPriorities->pluck('workshop_id')->all();
            }
        }

        $randomizer = new Randomizer($this->seed === null ? null : new Mt19937($this->seed));
        $this->lotteryOrder = $randomizer->shuffleArray(array_keys($this->prioritiesByRegistration));
    }

    /**
     * Distribute the registrations to the given sessions in rounds of the priorities.
     *
     * @param  list<int>  $activeSessionIds
     * @return array{0: array<int, array<int, int>>, 1: array<int, int>} the session ids by workshop id of every registration and the participant count of every session
     */
    private function distribute(array $activeSessionIds): array
    {
        $isActive = array_fill_keys($activeSessionIds, true);
        // the fixed participants take up places
        $participantCounts = array_replace(array_fill_keys(array_keys($this->sessions), 0), $this->fixedParticipantCounts);
        $assignments = array_fill_keys($this->lotteryOrder, []);

        $maxPriorityCount = max([0, ...array_map('count', $this->prioritiesByRegistration)]);

        for ($round = 0; $round < $maxPriorityCount; $round++) {
            $order = $round % 2 === 0 ? $this->lotteryOrder : array_reverse($this->lotteryOrder);

            foreach ($order as $registrationId) {
                $workshopId = $this->prioritiesByRegistration[$registrationId][$round] ?? null;
                if ($workshopId === null || count($assignments[$registrationId]) >= count($this->blockIds)) {
                    continue;
                }

                // the own places of the registration are free while searching, so they can move to another block
                foreach ($assignments[$registrationId] as $sessionId) {
                    $participantCounts[$sessionId]--;
                }

                $workshopIds = [...array_keys($assignments[$registrationId]), $workshopId];
                $newAssignment = $this->findSessions($workshopIds, $isActive, $participantCounts);
                if ($newAssignment !== null) {
                    $assignments[$registrationId] = $newAssignment;
                }

                foreach ($assignments[$registrationId] as $sessionId) {
                    $participantCounts[$sessionId]++;
                }
            }
        }

        return [$assignments, $participantCounts];
    }

    /**
     * Find a session for every given workshop, each in another block and with a free place. The workshops are in order of
     * the priority and every workshop tries the earliest block first. Returns null if there is no solution.
     *
     * @param  list<int>  $workshopIds
     * @param  array<int, bool>  $isActive
     * @param  array<int, int>  $participantCounts
     * @param  array<int, bool>  $usedBlockIds
     * @return array<int, int>|null the session ids by workshop id
     */
    private function findSessions(array $workshopIds, array $isActive, array $participantCounts, array $usedBlockIds = []): ?array
    {
        if (! $workshopIds) {
            return [];
        }

        $workshopId = array_shift($workshopIds);

        foreach ($this->sessionIdsByWorkshopAndBlock[$workshopId] ?? [] as $blockId => $sessionId) {
            if (isset($usedBlockIds[$blockId]) || ! isset($isActive[$sessionId]) || $participantCounts[$sessionId] >= $this->sessions[$sessionId]['maximum']) {
                continue;
            }

            $otherSessions = $this->findSessions($workshopIds, $isActive, $participantCounts, $usedBlockIds + [$blockId => true]);
            if ($otherSessions !== null) {
                return [$workshopId => $sessionId] + $otherSessions;
            }
        }

        return null;
    }

    /**
     * Get the session that should be cancelled, because it has participants, but less than its minimum. This is the session
     * with the fewest participants, on a tie the later one. Sessions with fixed participants are never cancelled.
     *
     * @param  list<int>  $activeSessionIds
     * @param  array<int, int>  $participantCounts
     */
    private function sessionBelowMinimum(array $activeSessionIds, array $participantCounts): ?int
    {
        $blockOrder = array_flip($this->blockIds);

        $candidates = array_filter($activeSessionIds, fn (int $sessionId) => ! isset($this->fixedParticipantCounts[$sessionId])
            && $participantCounts[$sessionId] > 0
            && $participantCounts[$sessionId] < $this->sessions[$sessionId]['minimum']);

        usort($candidates, fn (int $a, int $b) => [$participantCounts[$a], $blockOrder[$this->sessions[$b]['block_id']], $b]
            <=> [$participantCounts[$b], $blockOrder[$this->sessions[$a]['block_id']], $a]);

        return $candidates[0] ?? null;
    }

    /**
     * Replace the assignments of the event, except the fixed ones, and mark the cancelled sessions.
     *
     * @param  array<int, array<int, int>>  $assignments
     * @param  list<int>  $cancelledSessionIds
     */
    private function save(array $assignments, array $cancelledSessionIds): void
    {
        $keptAssignmentIds = array_merge(...array_map('array_keys', array_values($this->fixedSessionIdsByRegistration)));

        DB::transaction(function () use ($assignments, $cancelledSessionIds, $keptAssignmentIds) {
            WorkshopAssignment::whereIn('workshop_block_id', $this->blockIds)->whereNotIn('id', $keptAssignmentIds)->delete();

            $now = now();
            $rows = [];
            foreach ($assignments as $registrationId => $sessionIds) {
                foreach ($sessionIds as $sessionId) {
                    $rows[] = [
                        'registration_id' => $registrationId,
                        'workshop_session_id' => $sessionId,
                        'workshop_block_id' => $this->sessions[$sessionId]['block_id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                WorkshopAssignment::insert($chunk);
            }

            WorkshopSession::whereIn('id', array_keys($this->sessions))->update(['is_cancelled' => false]);
            WorkshopSession::whereIn('id', $cancelledSessionIds)->update(['is_cancelled' => true]);
        });
    }

    /**
     * Summarize the assignment: amount of places per priority, amount of blocks without a workshop and cancelled sessions.
     *
     * @param  array<int, array<int, int>>  $assignments
     * @param  list<int>  $cancelledSessionIds
     * @return array{priorities: array<int, int>, unassigned: int, cancelled_sessions: int}
     */
    private function summary(array $assignments, array $cancelledSessionIds): array
    {
        $priorities = [];
        $unassigned = 0;

        foreach ($assignments as $registrationId => $sessionIds) {
            foreach (array_keys($sessionIds) as $workshopId) {
                $priority = array_search($workshopId, $this->prioritiesByRegistration[$registrationId]) + 1;
                $priorities[$priority] = ($priorities[$priority] ?? 0) + 1;
            }

            $unassigned += count($this->blockIds) - count($sessionIds);
        }

        foreach ($this->fixedSessionIdsByRegistration as $sessionIds) {
            $unassigned += count($this->blockIds) - count($sessionIds);
        }

        ksort($priorities);

        return [
            'priorities' => $priorities,
            'unassigned' => $unassigned,
            'cancelled_sessions' => count($cancelledSessionIds),
        ];
    }
}
