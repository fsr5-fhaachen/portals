<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StrichlistensystemSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        Module::where('key', 'strichlistensystem')->update(['active' => true]);
    }

    public function test_self_service_is_not_reachable_if_the_module_is_inactive(): void
    {
        Module::where('key', 'strichlistensystem')->update(['active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('strichlistensystem.index'))
            ->assertRedirect(route('app.index'));
    }

    public function test_guest_is_redirected_to_login_and_back_after_login(): void
    {
        $user = User::factory()->create();

        $this->get(route('strichlistensystem.index'))->assertRedirect(route('app.login'));

        $this->post(route('app.loginUser'), ['email' => $user->email])
            ->assertRedirect(route('strichlistensystem.index'));
    }

    public function test_login_without_intended_strichlistensystem_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post(route('app.loginUser'), ['email' => $user->email])
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_user_without_pin_has_to_set_a_pin(): void
    {
        $user = User::factory()->withoutPin()->create();

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Strichlistensystem/Index')
                ->where('hasPin', false)
                ->missing('user.pin')
            );
    }

    public function test_user_without_pin_can_not_buy_articles(): void
    {
        $user = User::factory()->withoutPin()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('error');

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_user_can_set_a_pin(): void
    {
        $user = User::factory()->withoutPin()->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.updatePin'), ['pin' => '0042', 'pin_confirmation' => '0042'])
            ->assertRedirect(route('strichlistensystem.index'))
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->isPinValid('0042'));
        $this->assertNotSame('0042', $user->fresh()->getRawOriginal('pin'));
    }

    public function test_user_can_change_the_pin(): void
    {
        $user = User::factory()->create(['pin' => '5821']);

        $this->actingAs($user)
            ->post(route('strichlistensystem.updatePin'), ['pin' => '3907', 'pin_confirmation' => '3907'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->isPinValid('3907'));
        $this->assertFalse($user->fresh()->isPinValid('5821'));
    }

    public function test_invalid_pins_are_rejected(): void
    {
        $user = User::factory()->withoutPin()->create();

        foreach ([
            ['pin' => '123', 'pin_confirmation' => '123'],
            ['pin' => '12345', 'pin_confirmation' => '12345'],
            ['pin' => '12a4', 'pin_confirmation' => '12a4'],
            ['pin' => '1234', 'pin_confirmation' => '4321'],
            ['pin' => '', 'pin_confirmation' => ''],
        ] as $data) {
            $this->actingAs($user)
                ->post(route('strichlistensystem.updatePin'), $data)
                ->assertSessionHasErrors('pin');
        }

        $this->assertNull($user->fresh()->pin);
    }

    public function test_demo_users_have_no_pin(): void
    {
        $this->seed(UserDemoSeeder::class);

        $this->assertFalse(User::where('email', 'admin@example.com')->sole()->hasPin());
        $this->assertSame(0, User::whereNotNull('pin')->count());
    }

    public function test_trivial_pins_are_rejected(): void
    {
        $user = User::factory()->withoutPin()->create();

        foreach (['0000', '1234', '1111', '4321'] as $pin) {
            $this->actingAs($user)
                ->post(route('strichlistensystem.updatePin'), ['pin' => $pin, 'pin_confirmation' => $pin])
                ->assertSessionHasErrors(['pin' => 'Diese PIN ist zu leicht zu erraten. Bitte wähle eine andere PIN.']);
        }

        $this->assertFalse($user->fresh()->hasPin());
    }

    public function test_user_has_to_set_a_new_pin_after_the_reset(): void
    {
        $user = User::factory()->create();
        $user->pin = null;
        $user->save();

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page->where('hasPin', false));
    }

    public function test_user_with_pin_can_buy_and_sees_the_bookings(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 2]);

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 2, 'price' => $article->price])
            ->assertRedirect(route('strichlistensystem.index'))
            ->assertSessionHas('success');

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('hasPin', true)
                ->has('articleActionLogs', 2)
                ->where('articleActionLogs.0.article.name', $article->name)
                ->missing('articleActionLogs.0.ip')
            );
    }

    public function test_booking_keeps_the_price_and_page_contains_the_balance(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 3, 'price' => 250]);
        $user->strichlistensystemPayments()->create(['amount' => 200]);

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 3, 'price' => 250])
            ->assertSessionHas('success');

        $this->assertSame([250, 250, 250], $user->articleActionLogs()->pluck('price')->all());

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('balance', ['total' => 750, 'paid' => 200, 'open' => 550, 'bookings_without_price' => 0])
                ->where('articles.0.price', 250)
            );
    }

    public function test_booking_is_rejected_if_the_price_changed(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['name' => 'Bier', 'max_order_amount' => 3, 'price' => 250]);

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1, 'price' => 200])
            ->assertRedirect(route('strichlistensystem.index'))
            ->assertSessionHas('error', 'Der Preis von <strong>Bier</strong> hat sich geändert. Bitte prüfe den neuen Preis und buche erneut.');

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1])
            ->assertSessionHasErrors('price');

        $this->assertSame(0, $user->articleActionLogs()->count());
    }

    public function test_buy_article_rejects_a_changed_price(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 3, 'price' => 250]);

        // the price changed after the article was loaded
        Article::whereKey($article->id)->update(['price' => 300]);

        $this->assertFalse($user->buyArticle($article, 1, null, now()->subMinute(), 250));
        $this->assertTrue($user->buyArticle($article, 1, null, now()->subMinute(), 300));
        $this->assertSame([300], $user->articleActionLogs()->pluck('price')->all());
    }

    public function test_inactive_articles_are_hidden_and_can_not_be_bought(): void
    {
        $user = User::factory()->create();
        $activeArticle = Article::factory()->create();
        $inactiveArticle = Article::factory()->inactive()->create();

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('articles', 1)
                ->where('articles.0.id', $activeArticle->id)
            );

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $inactiveArticle), ['amount' => 1, 'price' => $inactiveArticle->price])
            ->assertSessionHas('error');

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_limit_applies_per_time_window(): void
    {
        config(['strichlistensystem.self_service_limit_window' => 60]);
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 1]);

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');

        $this->travel(59)->seconds();

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('error');

        $this->assertSame(1, ArticleActionLog::count());

        $this->travel(1)->seconds();

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');

        $this->assertSame(2, ArticleActionLog::count());
    }

    public function test_page_contains_remaining_amount_and_limit_reset_time(): void
    {
        config(['strichlistensystem.self_service_limit_window' => 60]);
        $this->freezeSecond();
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 2, 'show_order' => 1]);
        $exhaustedArticle = Article::factory()->create(['max_order_amount' => 1, 'show_order' => 2]);
        ArticleActionLog::factory()->for($user)->for($article)->create();
        ArticleActionLog::factory()->for($user)->for($exhaustedArticle)->create(['created_at' => now()->subSeconds(20)]);

        $this->actingAs($user)
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('limitWindow', 60)
                ->where('articles.0.remaining_amount', 1)
                ->where('articles.0.limit_resets_at', null)
                ->where('articles.1.remaining_amount', 0)
                ->where('articles.1.limit_resets_at', now()->addSeconds(40)->toIso8601String())
            );
    }

    public function test_cancelled_bookings_do_not_count_for_the_self_service_limit(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 1]);
        ArticleActionLog::factory()->for($user)->for($article)->create()->delete();

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('success');
    }

    public function test_buy_article_does_not_exceed_the_limit(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 2]);
        $limitSince = now()->subMinute();

        $this->assertFalse($user->buyArticle($article, 3, null, $limitSince, $article->price));
        $this->assertFalse($user->buyArticle($article, 0, null, $limitSince, $article->price));
        $this->assertTrue($user->buyArticle($article, 2, null, $limitSince, $article->price));
        $this->assertFalse($user->buyArticle($article, 1, null, $limitSince, $article->price));
        $this->assertSame(2, ArticleActionLog::count());
    }

    public function test_amount_above_maximum_is_rejected(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['max_order_amount' => 1]);

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 2, 'price' => $article->price])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_disabled_user_can_not_buy_articles(): void
    {
        $user = User::factory()->create(['is_disabled' => true]);
        $article = Article::factory()->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.buy', $article), ['amount' => 1, 'price' => $article->price])
            ->assertSessionHas('error');

        $this->assertSame(0, ArticleActionLog::count());
    }

    public function test_user_can_cancel_own_booking(): void
    {
        $user = User::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.cancel', $articleActionLog))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($articleActionLog);
    }

    public function test_disabled_user_can_not_cancel_a_booking(): void
    {
        $user = User::factory()->create(['is_disabled' => true]);
        $articleActionLog = ArticleActionLog::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.cancel', $articleActionLog))
            ->assertRedirect(route('strichlistensystem.index'))
            ->assertSessionHas('error', 'Du kannst aktuell keine Buchungen stornieren.');

        $this->assertNotSoftDeleted($articleActionLog);
    }

    public function test_user_without_pin_can_not_cancel_a_booking(): void
    {
        $user = User::factory()->withoutPin()->create();
        $articleActionLog = ArticleActionLog::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.cancel', $articleActionLog))
            ->assertSessionHas('error', 'Du kannst aktuell keine Buchungen stornieren.');

        $this->assertNotSoftDeleted($articleActionLog);
    }

    public function test_user_can_not_cancel_booking_of_another_user(): void
    {
        $user = User::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->create();

        $this->actingAs($user)
            ->post(route('strichlistensystem.cancel', $articleActionLog))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($articleActionLog);
    }

    public function test_user_can_not_cancel_booking_after_cancel_window(): void
    {
        $user = User::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->for($user)->create();

        $this->travel(config('strichlistensystem.cancel_window') + ArticleActionLog::CANCEL_GRACE_SECONDS + 1)->seconds();

        $this->actingAs($user)
            ->post(route('strichlistensystem.cancel', $articleActionLog))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($articleActionLog);
    }

    public function test_pin_is_not_shared_with_the_frontend(): void
    {
        $user = User::factory()->create(['pin' => '9876']);

        $response = $this->actingAs($user)->get(route('strichlistensystem.index'));

        $response->assertOk();
        $this->assertStringNotContainsString('9876', $response->getContent());
    }
}
