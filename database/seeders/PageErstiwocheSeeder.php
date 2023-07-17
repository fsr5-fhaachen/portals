<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageErstiwocheSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Übersicht',
                'slug' => 'overview',
                'sort_order' => 100,
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'sort_order' => 200,
            ],
        ];

        foreach ($pages as $pageData) {
            // check if page with slug exists
            $page = Page::where('slug', $pageData['slug'])->first();

            if ($page) {
                // update content
                $page->content = implode(PHP_EOL, file(__DIR__.'/pages/erstiwoche/'.$pageData['slug'].'.html'));

                // save the page
                $page->save();
            } else {
                // create a new page
                $page = new Page();
                $page->title = $pageData['title'];
                $page->slug = $pageData['slug'];
                $page->sort_order = $pageData['sort_order'];
                $page->content = implode(PHP_EOL, file(__DIR__.'/pages/erstiwoche/'.$pageData['slug'].'.html'));

                // save the page
                $page->save();
            }
        }
    }
}
