<?php

namespace Database\Factories;

use App\Models\TmsBusinessUnit;
use App\Models\TmsUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<TmsUser>
 */
class TmsUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'employee_code' => fake()->unique()->bothify('BDE-####'),
            'phone' => fake()->phoneNumber(),
            'role' => 'BDE',
            'is_active' => true,
            'default_business_unit_id' => TmsBusinessUnit::factory(),
            'timezone' => 'Asia/Kolkata',
            'avatar_color' => '#2563eb',
            'password' => Hash::make('password'),
        ];
    }

    public function generalManager(): static
    {
        return $this->state(fn (): array => ['role' => 'GM', 'employee_code' => fake()->unique()->bothify('GM-###')]);
    }

    public function assistantManager(): static
    {
        return $this->state(fn (): array => ['role' => 'AM', 'employee_code' => fake()->unique()->bothify('AM-###')]);
    }

    public function reportingTo(TmsUser $manager): static
    {
        return $this->state(fn (): array => [
            'direct_manager_id' => $manager->id,
            'default_business_unit_id' => $manager->default_business_unit_id,
        ]);
    }
}
