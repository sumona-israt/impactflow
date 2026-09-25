<?php

namespace Database\Factories;

use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerStatus;
use App\Models\Volunteer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Volunteer>
 */
class VolunteerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('01#########'),
            'skills' => fake()->randomElements(['teaching', 'first-aid', 'logistics', 'counseling'], 2),
            'availability' => fake()->randomElement(VolunteerAvailability::cases())->value,
            'status' => VolunteerStatus::Active,
        ];
    }
}
