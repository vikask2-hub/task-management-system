<?php

namespace Database\Factories;

use App\Models\TmsBusinessUnit;
use App\Models\TmsHospital;
use App\Models\TmsUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TmsHospital>
 */
class TmsHospitalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Hospital',
            'business_unit_id' => TmsBusinessUnit::factory(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'pincode' => fake()->numerify('######'),
            'account_type' => 'Hospital',
            'primary_contact_name' => fake()->name(),
            'primary_contact_designation' => 'Procurement Head',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'is_active' => true,
            'created_by_id' => TmsUser::factory()->generalManager(),
        ];
    }
}
