<?php

namespace Database\Factories;

use App\Models\TmsBusinessUnit;
use App\Models\TmsTask;
use App\Models\TmsTaskCategory;
use App\Models\TmsUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TmsTask>
 */
class TmsTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_number' => fake()->unique()->bothify('TMS-########-######'),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'category_id' => TmsTaskCategory::factory(),
            'priority' => 'MEDIUM',
            'status' => 'TODO',
            'business_unit_id' => TmsBusinessUnit::factory(),
            'assignee_id' => TmsUser::factory(),
            'assignee_role_snapshot' => 'BDE',
            'created_by_id' => TmsUser::factory()->assistantManager(),
            'planned_at' => now()->startOfHour(),
            'due_at' => now()->addDay()->startOfHour(),
            'verification_required' => true,
            'attachment_required' => false,
            'geo_requested' => false,
            'source' => 'MANUAL',
        ];
    }
}
