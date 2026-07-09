<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type'     => $this->faker->randomElement(['income', 'expense']),
            'category' => $this->faker->randomElement(['Feed & Fodder', 'Veterinary & Medicine', 'Labor / Wages']),
            'amount'   => $this->faker->randomFloat(2, 100, 10000),
            'date'     => $this->faker->date(),
        ];
    }
}
