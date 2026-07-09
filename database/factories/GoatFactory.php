<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GoatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tag_id'          => $this->faker->unique()->bothify('G-####'),
            'name'            => $this->faker->optional()->firstName(),
            'breed_id'        => null, // must be set by caller
            'gender'          => $this->faker->randomElement(['male', 'female']),
            'dob'             => $this->faker->optional()->date(),
            'weight_kg'       => $this->faker->randomFloat(2, 10, 80),
            'status'          => $this->faker->randomElement(['kid', 'grower', 'breeder']),
            'purchase_price'  => $this->faker->randomFloat(2, 1000, 50000),
            'estimated_value' => $this->faker->randomFloat(2, 1000, 60000),
            'notes'           => $this->faker->optional()->sentence(),
        ];
    }
}
