<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Lorem',
                'slug' => 'lorem',
                'sort_order' => 100,
            ],
            [
                'title' => 'Ipsum',
                'slug' => 'ipsum',
                'sort_order' => 200,
            ],
        ];

        foreach ($pages as $pageData) {
            // check if page with slug exists
            $page = Page::where('slug', $pageData['slug'])->first();

            if ($page) {
                // update content
                $page->content = implode(PHP_EOL, file(__DIR__.'/pages/demo/'.$pageData['slug'].'.html'));

                // save the page
                $page->save();
            } else {
                // create a new page
                $page = new Page();
                $page->title = $pageData['title'];
                $page->slug = $pageData['slug'];
                $page->sort_order = $pageData['sort_order'];
                $page->content = implode(PHP_EOL, file(__DIR__.'/pages/demo/'.$pageData['slug'].'.html'));

                // save the page
                $page->save();
            }
        }
    }
}
