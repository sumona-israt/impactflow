<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'title' => fake()->words(4, true),
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 months'),
            'location' => fake()->city(),
            'status' => ActivityStatus::Scheduled,
        ];
    }
}
