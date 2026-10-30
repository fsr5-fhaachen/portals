<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'name' => 'Bier',
                'icon' => 'beer-mug-empty',
                'show_order' => 1,
                'max_order_amount' => 8,
            ],
            [
                'name' => 'Radler',
                'icon' => 'lemon',
                'show_order' => 2,
                'max_order_amount' => 8,
            ],
            [
                'name' => 'Cola',
                'icon' => 'wine-bottle',
                'show_order' => 3,
                'max_order_amount' => 8,
            ],
            [
                'name' => 'Spezi',
                'icon' => 'glass-water',
                'show_order' => 4,
                'max_order_amount' => 8,
            ],
            [
                'name' => 'Wasser',
                'icon' => 'faucet',
                'show_order' => 5,
                'max_order_amount' => 2,
            ],
            [
                'name' => 'Bacardi',
                'icon' => 'martini-glass-citrus',
                'show_order' => 6,
                'max_order_amount' => 1,
            ],
        ];

        foreach ($articles as $article) {
            // check if article with name already exists
            if (Article::where('name', $article['name'])->exists()) {
                continue;
            }

            Article::create($article);
        }
    }
}
