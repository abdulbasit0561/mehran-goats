<?php

namespace Database\Factories;

use App\Models\Investor;
use App\Models\ProfitDistribution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfitDistributionFactory extends Factory
{
    protected $model = ProfitDistribution::class;

    public function definition(): array
    {
        return [
            'investor_id'  => Investor::factory(),
            'period_start' => $this->faker->dateTimeThisYear()->format('Y-m-d'),
            'period_end'   => $this->faker->dateTimeThisYear()->format('Y-m-d'),
            'total_profit' => $this->faker->randomFloat(2, 1000, 50000),
            'share_pct'    => $this->faker->randomFloat(2, 1, 50),
            'amount'       => $this->faker->randomFloat(2, 100, 10000),
            'status'       => $this->faker->randomElement(['pending', 'paid']),
            'notes'        => null,
        ];
    }
}
