<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = [
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'sort_order' => 100,
            ],
        ];

        foreach ($pages as $pageData) {
            // check if page with name exists
            $page = Page::where('slug', $pageData['slug'])->first();
            if ($page) {
                return;
            }

            // create a new page
            $page = new Page();
            $page->title = $pageData['title'];
            $page->slug = $papageDatage['slug'];
            $page->sort_order = $pageData['sort_order'];
            $page->content = implode('\n', file(__DIR__ . '/pages/'.$pageData['slug'].'.html'));

            // save the page
            $page->save();
        }
    }
}
