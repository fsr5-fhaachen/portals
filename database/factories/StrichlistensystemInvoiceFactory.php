<?php

namespace Database\Factories;

use App\Models\StrichlistensystemInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StrichlistensystemInvoice>
 */
class StrichlistensystemInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = $this->faker->unique()->numberBetween(1, 99999);
        $subtotal = $this->faker->numberBetween(100, 5000);

        return [
            'sequence' => $sequence,
            'number' => 'RE-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'created_by_id' => null,
            'title' => 'Abrechnung',
            'recipient' => [
                'name' => $this->faker->name(),
                'street' => $this->faker->streetAddress(),
                'postal_code' => $this->faker->postcode(),
                'city' => $this->faker->city(),
            ],
            'issuer' => [],
            'items' => [['name' => 'Bier', 'quantity' => 1, 'unit_price' => $subtotal, 'total' => $subtotal]],
            'subtotal' => $subtotal,
            'paid' => 0,
            'amount_due' => $subtotal,
            'issued_on' => now(),
            'due_on' => now()->addDays(14),
            'cancelled_at' => null,
        ];
    }

    /**
     * The invoice was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => ['cancelled_at' => now()]);
    }
}
