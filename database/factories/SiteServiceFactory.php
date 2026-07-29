<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteService>
 */
class SiteServiceFactory extends Factory
{
    public function definition(): array
    {
        $icons = ['emerald', 'blue', 'teal', 'amber', 'rose', 'indigo'];

        return [
            'title'       => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'icon'        => $this->faker->randomElement($icons),
            'sort_order'  => $this->faker->numberBetween(1, 20),
            'is_active'   => true,
        ];
    }
}
