<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'submitted_by' => User::factory(),
            'amount' => fake()->randomFloat(2, 500, 20000),
            'currency' => 'BDT',
            'expense_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'description' => fake()->sentence(),
            'status' => ExpenseStatus::Draft,
        ];
    }
}
