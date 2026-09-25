<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        'Travel & Transport', 'Venue & Logistics', 'Training Materials',
        'Staff Allowances', 'Office Supplies', 'Communication',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }
}
