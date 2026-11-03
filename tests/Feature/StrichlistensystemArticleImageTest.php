<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class StrichlistensystemArticleImageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);
        Storage::fake('s3');

        Module::where('key', 'strichlistensystem')->update(['active' => true]);

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
     * @return array<string, mixed>
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

    /**
     * Create a fake image file. The content starts with the real file signature, so the mime type is detected
     * like for a real image (UploadedFile::fake()->image() needs the gd extension).
     */
    private function fakeImage(string $fileName, int $paddingBytes = 0): UploadedFile
    {
        $signatures = [
            // 1x1 transparent png
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='),
            'jpg' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9",
            'webp' => "RIFF\x1A\x00\x00\x00WEBPVP8L\x0D\x00\x00\x00\x2F\x00\x00\x00\x10\x07\x10\x11\x11\x88\x88\xFE\x07\x00",
        ];

        return UploadedFile::fake()->createWithContent(
            $fileName,
            $signatures[pathinfo($fileName, PATHINFO_EXTENSION)].str_repeat("\0", $paddingBytes),
        );
    }

    /**
     * Create an article with an uploaded image.
     */
    private function articleWithImage(): Article
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData([
                'image' => $this->fakeImage('mate.png'),
            ]))
            ->assertSessionHasNoErrors();

        return Article::sole();
    }

    public function test_admin_can_create_an_article_with_an_image(): void
    {
        $article = $this->articleWithImage();

        $this->assertStringStartsWith('articles/', $article->image);
        $this->assertStringEndsWith('.png', $article->image);
        Storage::disk('s3')->assertExists($article->image);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('articles.data.0.image_url', $article->image_url)
            );
    }

    public function test_article_without_image_has_no_image_url(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData())
            ->assertSessionHasNoErrors();

        $this->assertNull(Article::sole()->image);
        $this->assertNull(Article::sole()->image_url);
    }

    public function test_jpg_and_webp_images_are_accepted(): void
    {
        foreach (['bier.jpg', 'wasser.webp'] as $index => $fileName) {
            $this->actingAsAdmin()
                ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData([
                    'name' => 'Artikel '.$index,
                    'image' => $this->fakeImage($fileName),
                ]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(2, Article::whereNotNull('image')->count());
    }

    public function test_invalid_images_are_rejected(): void
    {
        foreach ([
            UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->create('bild.gif', 1, 'image/gif'),
            UploadedFile::fake()->create('text.pdf', 1, 'application/pdf'),
            $this->fakeImage('gross.png', 3 * 1024 * 1024),
        ] as $file) {
            $this->actingAsAdmin()
                ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData(['image' => $file]))
                ->assertSessionHasErrors('image');
        }

        $this->assertSame(0, Article::count());
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    public function test_invalid_images_show_a_german_message(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData([
                'image' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
            ]))
            ->assertSessionHasErrors(['image' => 'Das Bild muss eine PNG-, JPG- oder WebP-Datei sein.']);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData([
                'image' => $this->fakeImage('gross.png', 3 * 1024 * 1024),
            ]))
            ->assertSessionHasErrors(['image' => 'Das Bild darf höchstens 2 MB groß sein.']);
    }

    public function test_failed_upload_is_reported_and_the_article_is_not_saved(): void
    {
        // the s3 disk returns false if the upload fails, e.g. without configuration
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->andReturnFalse();
        Storage::set('s3', $disk);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData([
                'image' => $this->fakeImage('mate.png'),
            ]))
            ->assertSessionHasErrors(['image' => 'Das Bild konnte nicht gespeichert werden. Bitte versuche es erneut.']);

        $this->assertSame(0, Article::count());
    }

    public function test_upload_error_is_reported_and_the_article_is_not_updated(): void
    {
        $article = Article::factory()->create(['name' => 'Mate']);

        // a disk that can not write: the folder "articles" is a file
        $root = sys_get_temp_dir().'/strichlistensystem-'.uniqid();
        mkdir($root);
        touch($root.'/articles');
        config(['filesystems.disks.s3' => ['driver' => 'local', 'root' => $root, 'throw' => false]]);
        Storage::forgetDisk('s3');

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData([
                'name' => 'Club Mate',
                'image' => $this->fakeImage('mate.png'),
            ]))
            ->assertSessionHasErrors(['image' => 'Das Bild konnte nicht gespeichert werden. Bitte versuche es erneut.']);

        $this->assertSame('Mate', $article->fresh()->name);
        $this->assertNull($article->fresh()->image);

        unlink($root.'/articles');
        rmdir($root);
    }

    public function test_uploaded_image_is_deleted_if_creating_the_article_fails(): void
    {
        Article::creating(fn () => throw new RuntimeException('Saving failed'));

        $this->expectException(RuntimeException::class);

        try {
            $this->withoutExceptionHandling()
                ->actingAsAdmin()
                ->post(route('dashboard.admin.strichlistensystem.storeArticle'), $this->articleData([
                    'image' => $this->fakeImage('mate.png'),
                ]));
        } finally {
            $this->assertSame([], Storage::disk('s3')->allFiles('articles'));
            $this->assertSame(0, Article::count());
        }
    }

    public function test_uploaded_image_is_deleted_and_the_old_one_kept_if_updating_the_article_fails(): void
    {
        $article = $this->articleWithImage();
        $oldImage = $article->image;
        Article::updating(fn () => throw new RuntimeException('Saving failed'));

        $this->expectException(RuntimeException::class);

        try {
            $this->withoutExceptionHandling()
                ->actingAsAdmin()
                ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData([
                    'image' => $this->fakeImage('neu.png'),
                ]));
        } finally {
            $this->assertSame([$oldImage], Storage::disk('s3')->allFiles('articles'));
            $this->assertSame($oldImage, $article->fresh()->image);
        }
    }

    public function test_new_image_replaces_and_deletes_the_old_image(): void
    {
        $article = $this->articleWithImage();
        $oldImage = $article->image;

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData([
                'image' => $this->fakeImage('neu.png'),
            ]))
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertNotSame($oldImage, $article->image);
        Storage::disk('s3')->assertMissing($oldImage);
        Storage::disk('s3')->assertExists($article->image);
    }

    public function test_update_without_image_keeps_the_image(): void
    {
        $article = $this->articleWithImage();
        $image = $article->image;

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData(['name' => 'Club Mate']))
            ->assertSessionHasNoErrors();

        $this->assertSame($image, $article->fresh()->image);
        Storage::disk('s3')->assertExists($image);
    }

    public function test_admin_can_remove_the_image(): void
    {
        $article = $this->articleWithImage();
        $image = $article->image;

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData(['remove_image' => true]))
            ->assertSessionHasNoErrors();

        $this->assertNull($article->fresh()->image);
        Storage::disk('s3')->assertMissing($image);
    }

    public function test_deleting_an_article_deletes_the_image(): void
    {
        $article = $this->articleWithImage();

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.strichlistensystem.deleteArticle', $article))
            ->assertRedirect(route('dashboard.admin.strichlistensystem.index'));

        $this->assertModelMissing($article);
        Storage::disk('s3')->assertMissing($article->image);
    }

    public function test_image_is_delivered_with_a_long_cache(): void
    {
        $article = $this->articleWithImage();

        $response = $this->get($article->image_url);

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
    }

    public function test_image_url_changes_with_a_new_image(): void
    {
        $article = $this->articleWithImage();
        $oldImageUrl = $article->image_url;

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.strichlistensystem.updateArticle', $article), $this->articleData([
                'image' => $this->fakeImage('neu.png'),
            ]));

        $this->assertNotSame($oldImageUrl, $article->fresh()->image_url);
    }

    public function test_article_without_image_returns_not_found(): void
    {
        $article = Article::factory()->create();

        $this->get(route('strichlistensystem.articleImage', $article))->assertNotFound();
    }

    public function test_image_is_not_delivered_if_the_module_is_inactive(): void
    {
        $article = $this->articleWithImage();
        Module::where('key', 'strichlistensystem')->update(['active' => false]);

        $this->get($article->image_url)->assertRedirect(route('app.index'));
    }

    public function test_self_service_contains_the_image_url(): void
    {
        $article = $this->articleWithImage();

        $this->actingAs(User::factory()->create())
            ->get(route('strichlistensystem.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('articles.0.image_url', $article->image_url)
            );
    }
}
