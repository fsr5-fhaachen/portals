<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleGerolsteinSeeder extends Seeder
{
    /**
     * Run the database seeds. The prices are in cents, water is free but is booked anyway.
     */
    public function run(): void
    {
        $articles = [
            [
                'name' => 'Bier',
                'icon' => 'beer-mug-empty',
                'show_order' => 1,
                'max_order_amount' => 8,
                'price' => 100,
            ],
            [
                'name' => 'Radler',
                'icon' => 'lemon',
                'show_order' => 2,
                'max_order_amount' => 8,
                'price' => 100,
            ],
            [
                'name' => 'Weißwein (Flasche)',
                'icon' => 'wine-glass',
                'show_order' => 3,
                'max_order_amount' => 1,
                'price' => 550,
            ],
            [
                'name' => 'Rosé (Flasche)',
                'icon' => 'wine-glass',
                'show_order' => 4,
                'max_order_amount' => 1,
                'price' => 550,
            ],
            [
                'name' => 'Cola',
                'icon' => 'wine-bottle',
                'show_order' => 5,
                'max_order_amount' => 8,
                'price' => 50,
            ],
            [
                'name' => 'Spezi',
                'icon' => 'glass-water',
                'show_order' => 6,
                'max_order_amount' => 8,
                'price' => 50,
            ],
            [
                'name' => 'Limo',
                'icon' => 'martini-glass-citrus',
                'show_order' => 7,
                'max_order_amount' => 8,
                'price' => 50,
            ],
            [
                'name' => 'Mate',
                'icon' => 'bottle-water',
                'show_order' => 8,
                'max_order_amount' => 8,
                'price' => 50,
            ],
            [
                'name' => 'Wasser',
                'icon' => 'faucet',
                'show_order' => 9,
                'max_order_amount' => 2,
                'price' => 0,
            ],
            [
                'name' => 'Katjes',
                'icon' => 'candy-cane',
                'show_order' => 10,
                'max_order_amount' => 5,
                'price' => 50,
                // food has the reduced vat rate, drinks the regular one (default)
                'vat_rate' => 7,
            ],
            [
                'name' => 'Doppelkeks',
                'icon' => 'cookie',
                'show_order' => 11,
                'max_order_amount' => 5,
                'price' => 100,
                // food has the reduced vat rate, drinks the regular one (default)
                'vat_rate' => 7,
            ],
            [
                'name' => 'Pringles',
                'icon' => 'bowl-food',
                'show_order' => 12,
                'max_order_amount' => 5,
                'price' => 50,
                // food has the reduced vat rate, drinks the regular one (default)
                'vat_rate' => 7,
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
