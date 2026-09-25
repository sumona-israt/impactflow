<?php

namespace Database\Factories;

use App\Enums\BeneficiaryStatus;
use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-5 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'phone' => fake()->numerify('01#########'),
            'district' => fake()->randomElement(['Dhaka', 'Chattogram', 'Rajshahi', 'Khulna']),
            'status' => BeneficiaryStatus::Active,
            'registration_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
