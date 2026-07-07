<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Investor>
 */
class InvestorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'            => $this->faker->name(),
            'role'            => $this->faker->randomElement(['investor', 'manager']),
            'expense_split_pct' => 33.33,
            'email'           => $this->faker->unique()->safeEmail(),
            'phone'           => $this->faker->phoneNumber(),
            'address'         => $this->faker->optional()->address(),
            'notes'           => $this->faker->optional()->sentence(),
        ];
    }
}
