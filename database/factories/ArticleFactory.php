<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'icon' => $this->faker->randomElement(config('strichlistensystem.icons')),
            'show_order' => $this->faker->numberBetween(1, 100),
            'max_order_amount' => $this->faker->numberBetween(1, 8),
            'price' => $this->faker->numberBetween(0, 500),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the article has no price yet.
     */
    public function withoutPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
        ]);
    }

    /**
     * Indicate that the article is deactivated.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
