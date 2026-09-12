<?php

namespace Database\Factories;

use App\Models\TmsBusinessUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TmsBusinessUnit>
 */
class TmsBusinessUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('BU-###'),
            'name' => fake()->unique()->city().' Unit',
            'city' => fake()->city(),
            'state' => fake()->state(),
            'region' => fake()->randomElement(['North', 'South', 'East', 'West']),
            'is_active' => true,
        ];
    }
}
