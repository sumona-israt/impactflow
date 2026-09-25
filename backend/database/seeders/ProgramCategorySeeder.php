<?php

namespace Database\Seeders;

use App\Models\ProgramCategory;
use Illuminate\Database\Seeder;

class ProgramCategorySeeder extends Seeder
{
    /**
     * A real, if illustrative, NGO program taxonomy — not placeholder text.
     */
    private const CATEGORIES = [
        'Education' => 'Formal and non-formal education, literacy, and school support programs.',
        'Health' => 'Primary healthcare, maternal and child health, and nutrition programs.',
        'Livelihoods' => 'Income generation, vocational training, and microenterprise support.',
        'WASH' => 'Water, sanitation, and hygiene infrastructure and behavior-change programs.',
        'Protection' => 'Child protection, gender-based violence response, and safeguarding.',
        'Emergency Response' => 'Disaster relief and humanitarian response programs.',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name => $description) {
            ProgramCategory::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
