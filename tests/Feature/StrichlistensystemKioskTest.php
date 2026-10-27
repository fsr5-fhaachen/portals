<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StrichlistensystemKioskTest extends TestCase
{
    use RefreshDatabase;

    private const KIOSK_TOKEN = 'kiosk-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        Module::where('key', 'strichlistensystem')->update(['active' => true]);
        config(['strichlistensystem.kiosk_token' => self::KIOSK_TOKEN]);
    }

    /**
     * Get the headers of the kiosk.
     *
     * @return array<string, string>
     */
    private function kioskHeaders(string $token = self::KIOSK_TOKEN): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    /**
     * Unlock the kiosk for the given user.
     */
    private function unlockKiosk(User $user): void
    {
        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => $user->pin])
            ->assertRedirect(route('strichlistensystem.kiosk.show', $user));
    }

    public function test_kiosk_is_not_reachable_if_the_module_is_inactive(): void
    {
        Module::where('key', 'strichlistensystem')->update(['active' => false]);

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.index'))
            ->assertRedirect(route('app.index'));
    }

    public function test_kiosk_requires_bearer_token(): void
    {
        $this->get(route('strichlistensystem.kiosk.index'))->assertUnauthorized();

        $this->withHeaders($this->kioskHeaders('wrong-token'))
            ->get(route('strichlistensystem.kiosk.index'))
            ->assertUnauthorized();
    }

    public function test_kiosk_is_not_reachable_without_configured_token(): void
    {
        config(['strichlistensystem.kiosk_token' => null]);

        $this->withHeaders(['Authorization' => 'Bearer '])
            ->get(route('strichlistensystem.kiosk.index'))
            ->assertUnauthorized();
    }

    public function test_kiosk_post_requests_require_bearer_token(): void
    {
        $user = User::factory()->create();

        $this->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => $user->pin])
            ->assertUnauthorized();

        $this->assertNull(session('strichlistensystem.kiosk'));
    }

    public function test_kiosk_lists_active_persons_without_exposing_pins(): void
    {
        $userWithPin = User::factory()->create(['firstname' => 'Anna']);
        $userWithoutPin = User::factory()->withoutPin()->create(['firstname' => 'Bert']);
        User::factory()->create(['firstname' => 'Carl', 'is_disabled' => true]);

        $response = $this->withHeaders($this->kioskHeaders())->get(route('strichlistensystem.kiosk.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Strichlistensystem/Kiosk/Index')
                ->has('persons.data', 2)
                ->where('persons.data.0.id', $userWithPin->id)
                ->where('persons.data.0.has_pin', true)
                ->missing('persons.data.0.pin')
                ->where('persons.data.1.id', $userWithoutPin->id)
                ->where('persons.data.1.has_pin', false)
            );

        $this->assertStringNotContainsString('"pin":', $response->getContent());
    }

    public function test_correct_pin_unlocks_the_person_page(): void
    {
        $user = User::factory()->create(['pin' => '0123']);
        Article::factory()->count(2)->create();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Strichlistensystem/Kiosk/Show')
                ->where('person.id', $user->id)
                ->missing('person.pin')
                ->has('articles', 2)
                ->has('articleActionLogs', 0)
            );
    }

    public function test_inactive_articles_are_hidden_and_can_not_be_bought(): void
    {
        $user = User::factory()->create();
        $activeArticle = Article::factory()->create();
        $inactiveArticle = Article::factory()->inactive()->create();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertInertia(fn (Assert $page) => $page
                ->has('articles', 1)
                ->where('articles.0.id', $activeArticle->id)
            );

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $inactiveArticle]), ['amount' => 1, 'price' => $inactiveArticle->price])
            ->assertRedirect(route('strichlistensystem.kiosk.show', $user))
            ->assertSessionHas('error');

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_booking_of_a_deactivated_article_is_still_shown_and_cancellable(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->for($user)->for($article)->create();
        $article->update(['is_active' => false]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertInertia(fn (Assert $page) => $page
                ->has('articles', 0)
                ->where('articleActionLogs.0.article.name', $article->name)
            );

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.cancel', [$user, $articleActionLog]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($articleActionLog);
    }

    public function test_wrong_pin_is_rejected(): void
    {
        $user = User::factory()->create(['pin' => '1234']);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => '4321'])
            ->assertSessionHasErrors('pin');

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertRedirect(route('strichlistensystem.kiosk.index'));
    }

    public function test_pin_with_wrong_format_is_rejected(): void
    {
        $user = User::factory()->create(['pin' => '1234']);

        foreach (['123', '12345', 'abcd', ''] as $pin) {
            $this->withHeaders($this->kioskHeaders())
                ->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => $pin])
                ->assertSessionHasErrors('pin');
        }
    }

    public function test_person_without_pin_can_not_unlock_the_kiosk(): void
    {
        $user = User::factory()->withoutPin()->create();

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => '0000'])
            ->assertSessionHasErrors('pin');

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertRedirect(route('strichlistensystem.kiosk.index'));
    }

    public function test_disabled_person_can_not_unlock_the_kiosk(): void
    {
        $user = User::factory()->create(['is_disabled' => true]);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => $user->pin])
            ->assertNotFound();
    }

    public function test_pin_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create(['pin' => '1234']);

        for ($i = 0; $i < config('strichlistensystem.pin_max_attempts'); $i++) {
            $this->withHeaders($this->kioskHeaders())
                ->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => '0000']);
        }

        // even the correct pin is rejected now
        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.verifyPin', $user), ['pin' => '1234'])
            ->assertSessionHasErrors('pin');

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertRedirect(route('strichlistensystem.kiosk.index'));
    }

    public function test_unlocked_person_can_buy_articles(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 3]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 3, 'price' => $article->price])
            ->assertRedirect(route('strichlistensystem.kiosk.show', $user));

        $this->assertSame(3, $user->articleActionLogs()->where('article_id', $article->id)->count());
    }

    public function test_bookings_keep_the_price_of_the_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 5, 'price' => 150]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 2, 'price' => 150])
            ->assertSessionHas('success');

        $article->update(['price' => 200]);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => 200])
            ->assertSessionHas('success');

        $this->assertSame([150, 150, 200], $user->articleActionLogs()->orderBy('id')->pluck('price')->all());
        $this->assertSame(500, $user->articleActionLogsTotal());
    }

    public function test_booking_is_rejected_if_the_price_changed(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['name' => 'Bier', 'max_order_amount' => 5, 'price' => 200]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => 150])
            ->assertRedirect(route('strichlistensystem.kiosk.show', $user))
            ->assertSessionHas('error', 'Der Preis von <strong>Bier</strong> hat sich geändert. Bitte prüfe den neuen Preis und buche erneut.');

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => null])
            ->assertSessionHas('error');

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1])
            ->assertSessionHasErrors('price');

        $this->assertSame(0, $user->articleActionLogs()->count());
    }

    public function test_article_without_price_can_be_booked_and_gets_the_price_later(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->withoutPrice()->create(['max_order_amount' => 5]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 2, 'price' => null])
            ->assertSessionHas('success');

        $this->assertSame([null, null], $user->articleActionLogs()->pluck('price')->all());

        $article->update(['price' => 120]);
        $article->applyPriceToBookingsWithoutPrice();

        $this->assertSame([120, 120], $user->articleActionLogs()->pluck('price')->all());
    }

    public function test_person_page_contains_the_balance_and_the_total_of_the_login(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 5, 'price' => 150]);
        ArticleActionLog::factory()->for($user)->for($article)->create(['created_at' => now()->subHour()]);
        ArticleActionLog::factory()->for($user)->for($article)->create(['created_at' => now()->subHour()])->delete();
        $user->strichlistensystemPayments()->create(['amount' => 100]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 2, 'price' => 150]);
        $user->articleActionLogs()->latest('id')->first()->delete();

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertInertia(fn (Assert $page) => $page
                ->where('loginTotal', 150)
                ->where('balance', ['total' => 300, 'paid' => 100, 'open' => 200, 'bookings_without_price' => 0])
                ->where('articles.0.price', 150)
                ->where('articleActionLogs.0.price', 150)
            );
    }

    public function test_amount_above_maximum_is_rejected(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 2]);

        $this->unlockKiosk($user);

        foreach ([0, 3, 'abc'] as $amount) {
            $this->withHeaders($this->kioskHeaders())
                ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => $amount, 'price' => $article->price])
                ->assertSessionHasErrors('amount');
        }

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 3, 'price' => $article->price])
            ->assertSessionHasErrors(['amount' => "Du kannst von {$article->name} in dieser Anmeldung nur noch 2 Stück buchen."]);

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_limit_applies_per_kiosk_login(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 1]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertRedirect(route('strichlistensystem.kiosk.show', $user))
            ->assertSessionHas('error');

        $this->assertSame(1, ArticleActionLog::count());

        // a new login resets the limit
        $this->withHeaders($this->kioskHeaders())->post(route('strichlistensystem.kiosk.logout'));
        $this->travel(1)->seconds();
        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');

        $this->assertSame(2, ArticleActionLog::count());
    }

    public function test_limit_counts_all_bookings_of_the_kiosk_login(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 3]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 2, 'price' => $article->price])
            ->assertSessionHas('success');

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 2, 'price' => $article->price])
            ->assertSessionHasErrors(['amount' => "Du kannst von {$article->name} in dieser Anmeldung nur noch 1 Stück buchen."]);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');

        $this->assertSame(3, ArticleActionLog::count());
    }

    public function test_bookings_before_the_kiosk_login_do_not_count(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 1]);
        ArticleActionLog::factory()->for($user)->for($article)->create(['created_at' => now()->subMinute()]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');
    }

    public function test_cancelled_bookings_do_not_count_for_the_kiosk_limit(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 1]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price]);
        $user->articleActionLogs()->first()->delete();

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');

        $this->assertSame(1, $user->articleActionLogs()->count());
    }

    public function test_person_page_contains_the_remaining_amount_of_the_login(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 3, 'show_order' => 1]);
        $exhaustedArticle = Article::factory()->create(['max_order_amount' => 1, 'show_order' => 2]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price]);
        $this->withHeaders($this->kioskHeaders())->post(route('strichlistensystem.kiosk.buy', [$user, $exhaustedArticle]), ['amount' => 1, 'price' => $exhaustedArticle->price]);

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertInertia(fn (Assert $page) => $page
                ->where('articles.0.id', $article->id)
                ->where('articles.0.remaining_amount', 2)
                ->where('articles.0.limit_resets_at', null)
                ->where('articles.1.id', $exhaustedArticle->id)
                ->where('articles.1.remaining_amount', 0)
                ->where('articles.1.limit_resets_at', null)
            );
    }

    public function test_unlocked_person_can_not_buy_for_another_person(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->create();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$otherUser, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertRedirect(route('strichlistensystem.kiosk.index'));

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_kiosk_session_expires_after_inactivity(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $this->unlockKiosk($user);

        $this->travel(config('strichlistensystem.kiosk_session_timeout') + 1)->seconds();

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.buy', [$user, $article]), ['amount' => 1, 'price' => $article->price])
            ->assertRedirect(route('strichlistensystem.kiosk.index'));

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_visiting_the_overview_locks_the_kiosk(): void
    {
        $user = User::factory()->create();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())->get(route('strichlistensystem.kiosk.index'))->assertOk();

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertRedirect(route('strichlistensystem.kiosk.index'));
    }

    public function test_logout_locks_the_kiosk(): void
    {
        $user = User::factory()->create();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.logout'))
            ->assertRedirect(route('strichlistensystem.kiosk.index'));

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.show', $user))
            ->assertRedirect(route('strichlistensystem.kiosk.index'));
    }

    public function test_booking_can_be_cancelled_within_the_cancel_window(): void
    {
        $user = User::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->for($user)->create();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.cancel', [$user, $articleActionLog]))
            ->assertRedirect(route('strichlistensystem.kiosk.show', $user));

        $this->assertSoftDeleted($articleActionLog);
    }

    public function test_booking_can_not_be_cancelled_after_the_cancel_window(): void
    {
        $user = User::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->for($user)->create([
            'created_at' => now()->subSeconds(config('strichlistensystem.cancel_window') + ArticleActionLog::CANCEL_GRACE_SECONDS + 1),
        ]);

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.cancel', [$user, $articleActionLog]))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($articleActionLog);
    }

    public function test_booking_of_another_person_can_not_be_cancelled(): void
    {
        $user = User::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->create();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.cancel', [$user, $articleActionLog]))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($articleActionLog);
    }

    public function test_already_cancelled_booking_shows_an_error(): void
    {
        $user = User::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->for($user)->create();
        $articleActionLog->delete();

        $this->unlockKiosk($user);

        $this->withHeaders($this->kioskHeaders())
            ->post(route('strichlistensystem.kiosk.cancel', [$user, $articleActionLog]))
            ->assertRedirect(route('strichlistensystem.kiosk.show', $user))
            ->assertSessionHas('error');
    }

    public function test_avatar_is_only_delivered_with_bearer_token(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('avatars/test.jpg', 'image-content');
        $user = User::factory()->create(['avatar' => 'avatars/test.jpg']);

        $this->get(route('strichlistensystem.kiosk.avatar', $user))->assertUnauthorized();

        $response = $this->withHeaders($this->kioskHeaders())->get(route('strichlistensystem.kiosk.avatar', $user));
        $response->assertOk();
        $this->assertSame('image-content', $response->streamedContent());
    }

    public function test_missing_avatar_returns_not_found(): void
    {
        Storage::fake('s3');
        $userWithoutAvatar = User::factory()->create(['avatar' => null]);
        $userWithMissingFile = User::factory()->create(['avatar' => 'avatars/missing.jpg']);

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.avatar', $userWithoutAvatar))
            ->assertNotFound();

        $this->withHeaders($this->kioskHeaders())
            ->get(route('strichlistensystem.kiosk.avatar', $userWithMissingFile))
            ->assertNotFound();
    }
}
