<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BreedFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->words(2, true),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
