<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\StrichlistensystemPayment;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardAdminStrichlistensystemBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        config(['strichlistensystem.enabled' => true]);

        $this->admin = User::factory()->create(['lastname' => 'Admin']);
        $this->admin->assignRole(['admin', 'tutor']);
    }

    /**
     * Act as the admin that is logged in as tutor.
     */
    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['tutor' => true]);
    }

    /**
     * Create a user with bookings of the given total (in cents).
     */
    private function userWithBookings(int $price, int $amount = 1, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $article = Article::factory()->create(['price' => $price]);
        ArticleActionLog::factory()->count($amount)->for($user)->for($article)->create();

        return $user;
    }

    public function test_admin_can_view_the_balances(): void
    {
        $user = $this->userWithBookings(150, 3, ['lastname' => 'Bauer']);
        ArticleActionLog::factory()->for($user)->for(Article::factory()->state(['price' => 1000]))->create()->delete();
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 200]);
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 100])->delete();
        Article::factory()->withoutPrice()->create();

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.billing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Strichlistensystem/Billing')
                ->where('articlesWithoutPriceCount', 1)
                ->has('balances.data', 2)
                ->where('balances.data.0.lastname', 'Admin')
                ->where('balances.data.0.total', 0)
                ->where('balances.data.1.id', $user->id)
                ->where('balances.data.1.article_action_logs_count', 3)
                ->where('balances.data.1.total', 450)
                ->where('balances.data.1.paid', 200)
                ->where('balances.data.1.open', 250)
                ->missing('balances.data.1.pin')
            );
    }

    public function test_balances_can_be_sorted_by_the_open_amount(): void
    {
        $smallOpen = $this->userWithBookings(100);
        $bigOpen = $this->userWithBookings(500);
        $paid = $this->userWithBookings(900);
        StrichlistensystemPayment::factory()->for($paid)->create(['amount' => 900]);

        foreach (['open', 'total', 'paid', 'article_action_logs_count', 'firstname', 'lastname'] as $sort) {
            $this->actingAsAdmin()
                ->get(route('dashboard.admin.strichlistensystem.billing', ['sort' => $sort, 'direction' => 'desc']))
                ->assertOk();
        }

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.billing', ['sort' => 'open', 'direction' => 'desc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('balances.data.0.id', $bigOpen->id)
                ->where('balances.data.1.id', $smallOpen->id)
            );
    }

    public function test_balances_can_be_searched(): void
    {
        $user = $this->userWithBookings(100, 1, ['firstname' => 'Zacharias']);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.billing', ['search' => 'zachar']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('balances.data', 1)
                ->where('balances.data.0.id', $user->id)
            );
    }

    public function test_live_update_contains_rows_with_new_bookings_and_payments(): void
    {
        $bookingUser = User::factory()->create();
        $paymentUser = $this->userWithBookings(300);
        $unchangedUser = User::factory()->create();

        $this->travel(10)->seconds();
        $since = now()->timestamp;

        ArticleActionLog::factory()->for($bookingUser)->for(Article::factory()->state(['price' => 70]))->create();
        StrichlistensystemPayment::factory()->for($paymentUser)->create(['amount' => 100]);

        $changes = $this->actingAsAdmin()
            ->getJson(route('dashboard.admin.strichlistensystem.billing', [
                'changes_since' => $since,
                'loaded_ids' => implode(',', [$bookingUser->id, $paymentUser->id, $unchangedUser->id]),
            ]))
            ->assertOk()
            ->json();

        $rows = collect($changes['rows'])->keyBy('id');
        $this->assertEqualsCanonicalizing([$bookingUser->id, $paymentUser->id], $rows->keys()->all());
        $this->assertSame(70, $rows[$bookingUser->id]['open']);
        $this->assertSame(200, $rows[$paymentUser->id]['open']);
    }

    public function test_live_update_contains_rows_with_undone_payments(): void
    {
        $user = $this->userWithBookings(300);
        $payment = StrichlistensystemPayment::factory()->for($user)->create(['amount' => 300]);

        $this->travel(10)->seconds();
        $since = now()->timestamp;
        $payment->delete();

        $changes = $this->actingAsAdmin()
            ->getJson(route('dashboard.admin.strichlistensystem.billing', ['changes_since' => $since, 'loaded_ids' => (string) $user->id]))
            ->json();

        $this->assertCount(1, $changes['rows']);
        $this->assertSame(300, $changes['rows'][0]['open']);
    }

    public function test_billing_is_not_reachable_without_permission_or_if_disabled(): void
    {
        $user = $this->userWithBookings(100);
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->get(route('dashboard.admin.strichlistensystem.billing'))
            ->assertRedirect();
        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->post(route('dashboard.admin.strichlistensystem.storePayment', $user), ['amount' => 100])
            ->assertRedirect();
        $this->assertSame(0, StrichlistensystemPayment::count());

        config(['strichlistensystem.enabled' => false]);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.billing'))
            ->assertNotFound();
        $this->actingAsAdmin()
            ->getJson(route('dashboard.admin.strichlistensystem.billingUser', $user))
            ->assertNotFound();
    }

    public function test_admin_can_record_a_partial_payment(): void
    {
        $user = $this->userWithBookings(150, 2, ['firstname' => 'Jörg', 'lastname' => 'Älter']);

        $this->actingAsAdmin()
            ->from(route('dashboard.admin.strichlistensystem.billing', ['sort' => 'open']))
            ->post(route('dashboard.admin.strichlistensystem.storePayment', $user), ['amount' => 120])
            ->assertRedirect(route('dashboard.admin.strichlistensystem.billing', ['sort' => 'open']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Die Zahlung von <strong>1,20 €</strong> für <strong>Jörg Älter</strong> wurde gespeichert.');

        $payment = StrichlistensystemPayment::sole();
        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame($this->admin->id, $payment->created_by_id);
        $this->assertSame(120, $payment->amount);
        $this->assertSame(['total' => 300, 'paid' => 120, 'open' => 180, 'bookings_without_price' => 0], $user->strichlistensystemBalance());
    }

    public function test_admin_can_pay_the_complete_open_amount(): void
    {
        $user = $this->userWithBookings(150, 2);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storePayment', $user), ['amount' => 300])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $user->strichlistensystemBalance()['open']);
    }

    public function test_invalid_payments_are_rejected(): void
    {
        $user = $this->userWithBookings(150, 2);

        foreach ([0, -100, 301, 1.5, 'abc', null] as $amount) {
            $this->actingAsAdmin()
                ->post(route('dashboard.admin.strichlistensystem.storePayment', $user), ['amount' => $amount])
                ->assertSessionHasErrors('amount');
        }

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storePayment', $user), ['amount' => 301])
            ->assertSessionHasErrors(['amount' => 'Der Betrag darf den offenen Betrag von 3,00 € nicht übersteigen.']);

        $this->assertSame(0, StrichlistensystemPayment::count());
    }

    public function test_payment_without_open_amount_is_rejected(): void
    {
        $user = $this->userWithBookings(150);
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 150]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storePayment', $user), ['amount' => 1])
            ->assertSessionHas('error');

        $this->assertSame(1, StrichlistensystemPayment::count());
    }

    public function test_pay_does_not_exceed_the_open_amount(): void
    {
        $user = $this->userWithBookings(150, 2);

        $this->assertNull($user->payStrichlistensystem(0, null));
        $this->assertNull($user->payStrichlistensystem(301, null));
        $this->assertNotNull($user->payStrichlistensystem(200, null));
        $this->assertNull($user->payStrichlistensystem(101, null));
        $this->assertNotNull($user->payStrichlistensystem(100, null));
        $this->assertSame(0, $user->strichlistensystemBalance()['open']);
    }

    public function test_admin_can_undo_the_latest_payment(): void
    {
        $user = $this->userWithBookings(500, 1, ['firstname' => 'Jörg', 'lastname' => 'Älter']);
        $firstPayment = $user->payStrichlistensystem(100, $this->admin);
        $this->travel(1)->seconds();
        $secondPayment = $user->payStrichlistensystem(200, $this->admin);

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.strichlistensystem.deletePayment', $firstPayment))
            ->assertSessionHas('error', 'Nur die letzte Zahlung kann rückgängig gemacht werden.');
        $this->assertNotSoftDeleted($firstPayment);

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.strichlistensystem.deletePayment', $secondPayment))
            ->assertSessionHas('success', 'Die Zahlung von <strong>2,00 €</strong> für <strong>Jörg Älter</strong> wurde rückgängig gemacht.');
        $this->assertSoftDeleted($secondPayment);
        $this->assertSame(400, $user->strichlistensystemBalance()['open']);

        // the previous payment is the latest payment now
        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.strichlistensystem.deletePayment', $firstPayment))
            ->assertSessionHas('success');
        $this->assertSoftDeleted($firstPayment);
        $this->assertSame(500, $user->strichlistensystemBalance()['open']);

        // an undone payment can not be undone again
        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.strichlistensystem.deletePayment', $firstPayment))
            ->assertNotFound();
    }

    public function test_admin_can_get_the_payments_of_a_user(): void
    {
        $user = $this->userWithBookings(500);
        $articleWithoutPrice = Article::factory()->withoutPrice()->create();
        ArticleActionLog::factory()->for($user)->for($articleWithoutPrice)->create();
        $undonePayment = $user->payStrichlistensystem(100, $this->admin);
        $undonePayment->delete();
        $this->travel(1)->seconds();
        $olderPayment = $user->payStrichlistensystem(100, $this->admin);
        $this->travel(1)->seconds();
        $latestPayment = $user->payStrichlistensystem(150, null);

        $this->actingAsAdmin()
            ->getJson(route('dashboard.admin.strichlistensystem.billingUser', $user))
            ->assertOk()
            ->assertJsonPath('balance', ['total' => 500, 'paid' => 250, 'open' => 250, 'bookings_without_price' => 1])
            ->assertJsonCount(3, 'payments')
            ->assertJsonPath('payments.0.id', $latestPayment->id)
            ->assertJsonPath('payments.0.is_undoable', true)
            ->assertJsonPath('payments.0.created_by', null)
            ->assertJsonPath('payments.1.id', $olderPayment->id)
            ->assertJsonPath('payments.1.is_undoable', false)
            ->assertJsonPath('payments.1.created_by.lastname', 'Admin')
            ->assertJsonPath('payments.2.id', $undonePayment->id)
            ->assertJsonPath('payments.2.is_undoable', false)
            ->assertJsonMissingPath('payments.1.created_by.pin');
    }

    public function test_cancelled_bookings_and_bookings_without_price_do_not_count(): void
    {
        $user = $this->userWithBookings(150, 2);
        $user->articleActionLogs()->first()->delete();
        ArticleActionLog::factory()->for($user)->for(Article::factory()->withoutPrice())->create();

        $this->assertSame(['total' => 150, 'paid' => 0, 'open' => 150, 'bookings_without_price' => 1], $user->strichlistensystemBalance());
    }
}
