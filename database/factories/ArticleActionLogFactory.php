<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleActionLog>
 */
class ArticleActionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'article_id' => Article::factory(),
            // the price of the article at the time of the booking
            'price' => fn (array $attributes) => Article::find($attributes['article_id'])?->price,
            'ip' => $this->faker->ipv4(),
        ];
    }
}
