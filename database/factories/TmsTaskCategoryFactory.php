<?php

namespace Database\Factories;

use App\Models\TmsTaskCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TmsTaskCategory>
 */
class TmsTaskCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CATEGORY_###'),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'icon_key' => 'square-check-big',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
