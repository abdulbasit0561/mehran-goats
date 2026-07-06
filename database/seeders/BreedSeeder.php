<?php

namespace Database\Seeders;

use App\Models\Breed;
use Illuminate\Database\Seeder;

class BreedSeeder extends Seeder
{
    public function run(): void
    {
        $breeds = [
            ['name' => 'Boer',          'description' => 'Premium meat goat from South Africa; fast growth.'],
            ['name' => 'Beetal',        'description' => 'Large Indian dairy/meat dual-purpose breed.'],
            ['name' => 'Jamnapari',     'description' => 'Tall Indian breed known for milk and meat.'],
            ['name' => 'Sirohi',        'description' => 'Compact meat breed from Rajasthan, India.'],
            ['name' => 'Black Bengal',  'description' => 'Small, high-quality meat breed from Bangladesh.'],
            ['name' => 'Osmanabadi',    'description' => 'Hardy dual-purpose breed from Maharashtra, India.'],
            ['name' => 'Barbari',       'description' => 'Small meat/dairy breed with distinctive markings.'],
            ['name' => 'Jakhrana',      'description' => 'Milk breed from Rajasthan; large frame.'],
            ['name' => 'Kamori',        'description' => 'Premium dairy breed from Sindh, Pakistan; large frame.'],
            ['name' => 'Crossbred',     'description' => 'Mixed or hybrid breed.'],
        ];

        foreach ($breeds as $breed) {
            Breed::firstOrCreate(['name' => $breed['name']], $breed);
        }
    }
}
