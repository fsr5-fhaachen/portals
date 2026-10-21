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

class DashboardAdminStrichlistensystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        config(['strichlistensystem.enabled' => true]);

        $this->admin = User::factory()->create();
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
     * Get valid article data.
     *
     * @return array{name: string, icon: string, show_order: int, max_order_amount: int, price: int, is_active: bool}
     */
    private function articleData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mate',
            'icon' => 'bottle-water',
            'show_order' => 7,
            'max_order_amount' => 4,
            'price' => 250,
            'is_active' => true,
        ], $overrides);
    }

    public function test_admin_can_view_the_article_management(): void
    {
        $article = Article::factory()->create();
        ArticleActionLog::factory()->count(2)->for($article)->create();

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Strichlistensystem/Index')
                ->has('articles.data', 1)
                ->where('articles.data.0.article_action_logs_count', 2)
                ->where('icons', config('strichlistensystem.icons'))
            );
    }

    public function test_article_management_is_not_reachable_if_strichlistensystem_is_disabled(): void
    {
        config(['strichlistensystem.enabled' => false]);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.index'))
            ->assertNotFound();
    }

    public function test_user_without_permission_can_not_manage_articles(): void
    {
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->get(route('dashboard.admin.strichlistensystem.index'))
            ->assertRedirect(route('dashboard.index'));

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData())
            ->assertRedirect(route('dashboard.index'));

        $this->assertSame(0, Article::count());
    }

    public function test_esa_without_permission_can_not_manage_articles(): void
    {
        $esa = User::factory()->create();
        $esa->assignRole(['esa', 'tutor']);

        $this->actingAs($esa)->withSession(['tutor' => true])
            ->get(route('dashboard.admin.strichlistensystem.index'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_admin_can_create_an_article(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData())
            ->assertRedirect(route('dashboard.admin.strichlistensystem.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('articles', $this->articleData());
    }

    public function test_invalid_article_data_is_rejected(): void
    {
        Article::factory()->create(['name' => 'Bier']);

        foreach ([
            ['name', $this->articleData(['name' => ''])],
            ['name', $this->articleData(['name' => 'Bier'])],
            ['icon', $this->articleData(['icon' => 'skull'])],
            ['show_order', $this->articleData(['show_order' => -1])],
            ['max_order_amount', $this->articleData(['max_order_amount' => 0])],
            ['price', $this->articleData(['price' => null])],
            ['price', $this->articleData(['price' => -1])],
            ['price', $this->articleData(['price' => 100001])],
            ['price', $this->articleData(['price' => 1.5])],
            ['price', $this->articleData(['price' => '1,50'])],
            ['is_active', $this->articleData(['is_active' => 'yes'])],
        ] as [$field, $data]) {
            $this->actingAsAdmin()
                ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $data)
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(1, Article::count());
    }

    public function test_admin_can_update_an_article(): void
    {
        $article = Article::factory()->create(['name' => 'Bier']);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData(['name' => 'Bier']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('articles', ['id' => $article->id] + $this->articleData(['name' => 'Bier']));
    }

    public function test_article_can_be_free(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData(['price' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Article::first()->price);
    }

    public function test_price_change_does_not_change_existing_bookings(): void
    {
        $article = Article::factory()->create(['name' => 'Bier', 'price' => 150]);
        $articleActionLog = ArticleActionLog::factory()->for($article)->create();
        $cancelledArticleActionLog = ArticleActionLog::factory()->for($article)->create();
        $cancelledArticleActionLog->delete();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData(['name' => 'Bier', 'price' => 200]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Der Artikel <strong>Bier</strong> wurde gespeichert.');

        $this->assertSame(200, $article->fresh()->price);
        $this->assertSame(150, $articleActionLog->fresh()->price);
        $this->assertSame(150, $cancelledArticleActionLog->fresh()->price);
    }

    public function test_bookings_without_price_get_the_first_price_once(): void
    {
        $article = Article::factory()->withoutPrice()->create(['name' => 'Bier']);
        $articleActionLogs = ArticleActionLog::factory()->count(2)->for($article)->create();
        $cancelledArticleActionLog = ArticleActionLog::factory()->for($article)->create();
        $cancelledArticleActionLog->delete();
        $otherArticleActionLog = ArticleActionLog::factory()->for(Article::factory()->withoutPrice())->create();

        $this->assertNull($articleActionLogs[0]->price);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData(['name' => 'Bier', 'price' => 180]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Der Artikel <strong>Bier</strong> wurde gespeichert. 3 Buchungen ohne Preis haben den Preis 1,80 € erhalten.');

        $this->assertSame(180, $articleActionLogs[0]->fresh()->price);
        $this->assertSame(180, $articleActionLogs[1]->fresh()->price);
        $this->assertSame(180, $cancelledArticleActionLog->fresh()->price);
        $this->assertNull($otherArticleActionLog->fresh()->price);

        // a later price change only applies to new bookings
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData(['name' => 'Bier', 'price' => 220]))
            ->assertSessionHas('success', 'Der Artikel <strong>Bier</strong> wurde gespeichert.');

        $this->assertSame(180, $articleActionLogs[0]->fresh()->price);
    }

    public function test_article_management_contains_the_price(): void
    {
        Article::factory()->create(['price' => 120]);
        Article::factory()->withoutPrice()->create();

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.index', ['sort' => 'price', 'direction' => 'desc']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('articles.data', 2)
                ->where('articles.data.0.price', 120)
                ->where('articles.data.1.price', null)
            );
    }

    public function test_admin_can_deactivate_an_article_via_the_form(): void
    {
        $article = Article::factory()->create(['name' => 'Bier']);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData(['name' => 'Bier', 'is_active' => false]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($article->fresh()->is_active);
    }

    public function test_admin_can_toggle_an_article_with_bookings(): void
    {
        $article = Article::factory()->create();
        ArticleActionLog::factory()->for($article)->create();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.toggleArticleActive', $article))
            ->assertRedirect(route('dashboard.admin.strichlistensystem.index'))
            ->assertSessionHas('success');

        $this->assertFalse($article->fresh()->is_active);
        $this->assertSame(1, $article->articleActionLogs()->count());

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.toggleArticleActive', $article))
            ->assertSessionHas('success');

        $this->assertTrue($article->fresh()->is_active);
    }

    public function test_user_without_permission_can_not_toggle_an_article(): void
    {
        $article = Article::factory()->create();
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->post(route('dashboard.admin.strichlistensystem.toggleArticleActive', $article))
            ->assertRedirect(route('dashboard.index'));

        $this->assertTrue($article->fresh()->is_active);
    }

    public function test_inactive_articles_are_listed_in_the_admin_and_the_export(): void
    {
        $article = Article::factory()->inactive()->create(['name' => 'Alt', 'price' => 410]);
        $user = User::factory()->create();
        ArticleActionLog::factory()->count(2)->for($user)->for($article)->create();

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('articles.data', 1)
                ->where('articles.data.0.is_active', false)
            );

        $csv = $this->actingAsAdmin()->get(route('dashboard.admin.strichlistensystem.export'))->streamedContent();

        $this->assertStringContainsString(';Anz_Alt', $csv);
        $this->assertStringContainsString("{$user->lastname};{$user->firstname};2;8,20;8,20;0,00;8,20\n", $csv);
    }

    public function test_admin_can_delete_an_article_without_bookings(): void
    {
        $article = Article::factory()->create();

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.strichlistensystem.deleteArticle', $article))
            ->assertSessionHas('success');

        $this->assertModelMissing($article);
    }

    public function test_article_with_bookings_can_not_be_deleted(): void
    {
        $article = Article::factory()->create();
        $articleActionLog = ArticleActionLog::factory()->for($article)->create();
        $articleActionLog->delete();

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.strichlistensystem.deleteArticle', $article))
            ->assertSessionHas('error');

        $this->assertModelExists($article);
    }

    public function test_admin_can_export_the_bookings_as_csv(): void
    {
        $beer = Article::factory()->create(['name' => 'Bier', 'show_order' => 1, 'price' => 150]);
        $water = Article::factory()->create(['name' => 'Wasser', 'show_order' => 2, 'price' => 50]);
        $user = User::factory()->create(['firstname' => 'Jörg', 'lastname' => 'Älter']);
        ArticleActionLog::factory()->count(3)->for($user)->for($beer)->create();
        ArticleActionLog::factory()->for($user)->for($water)->create();
        ArticleActionLog::factory()->for($user)->for($water)->create()->delete();
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 200]);
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 1000])->delete();

        $response = $this->actingAsAdmin()->get(route('dashboard.admin.strichlistensystem.export'));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBFNachname;Vorname;Anz_Bier;Anz_Wasser;Betrag_Bier;Betrag_Wasser;Gesamt;Bezahlt;Offen\n", $csv);
        $this->assertStringContainsString("Älter;Jörg;3;1;4,50;0,50;5,00;2,00;3,00\n", $csv);
        $this->assertStringContainsString("{$this->admin->lastname};{$this->admin->firstname};0;0;0,00;0,00;0,00;0,00;0,00\n", $csv);
    }

    public function test_export_uses_the_price_at_the_time_of_the_booking(): void
    {
        $beer = Article::factory()->create(['name' => 'Bier', 'price' => 150]);
        $user = User::factory()->create(['firstname' => 'Jörg', 'lastname' => 'Älter']);
        ArticleActionLog::factory()->for($user)->for($beer)->create();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $beer), $this->articleData(['name' => 'Bier', 'price' => 200]))
            ->assertSessionHasNoErrors();

        ArticleActionLog::factory()->for($user)->for($beer)->create();

        $csv = $this->actingAsAdmin()->get(route('dashboard.admin.strichlistensystem.export'))->streamedContent();

        $this->assertStringContainsString("Älter;Jörg;2;3,50;3,50;0,00;3,50\n", $csv);
    }

    public function test_export_formats_a_negative_open_amount(): void
    {
        $beer = Article::factory()->create(['name' => 'Bier', 'price' => 150]);
        $user = User::factory()->create(['firstname' => 'Jörg', 'lastname' => 'Älter']);
        ArticleActionLog::factory()->for($user)->for($beer)->create();
        ArticleActionLog::factory()->for($user)->for($beer)->create()->delete();
        StrichlistensystemPayment::factory()->for($user)->create(['amount' => 300]);

        $csv = $this->actingAsAdmin()->get(route('dashboard.admin.strichlistensystem.export'))->streamedContent();

        $this->assertStringContainsString("Älter;Jörg;1;1,50;1,50;3,00;-1,50\n", $csv);
    }

    public function test_admin_can_set_and_remove_the_pin_of_a_user(): void
    {
        $user = User::factory()->withoutPin()->create();
        $data = [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'email_confirm' => $user->email,
            'course_id' => $user->course_id,
            'is_disabled' => false,
        ];

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.editUser', $user), $data + ['pin' => '0815'])
            ->assertSessionHasNoErrors();
        $this->assertSame('0815', $user->fresh()->pin);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.editUser', $user), $data + ['pin' => '81'])
            ->assertSessionHasErrors('pin');
        $this->assertSame('0815', $user->fresh()->pin);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.editUser', $user), $data + ['pin' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->pin);
    }

    public function test_user_management_api_contains_the_pin(): void
    {
        $user = User::factory()->create(['pin' => '4711']);

        $this->actingAsAdmin()
            ->getJson(route('api.users'))
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id, 'pin' => '4711']);
    }

    public function test_public_api_does_not_contain_the_pin(): void
    {
        config(['app.public_api_secret' => 'public-secret']);
        User::factory()->create(['pin' => '4711']);

        $response = $this->withHeaders(['Authorization' => 'public-secret'])->getJson(route('apiV1.users'));

        $response->assertOk();
        $this->assertStringNotContainsString('4711', $response->getContent());
        $this->assertArrayNotHasKey('pin', $response->json('users.0'));
    }
}
