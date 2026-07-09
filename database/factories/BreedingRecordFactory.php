<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BreedingRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sire_id'       => null,
            'dam_id'        => null,
            'breeding_date' => $this->faker->date(),
            'litter_size'   => 0,
        ];
    }
}
