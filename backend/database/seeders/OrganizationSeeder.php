<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Single-row seeder — see App\Models\Organization::current() and
 * docs/database-design.md §2.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::firstOrCreate(
            ['name' => 'ImpactFlow Development Foundation'],
            ['registration_number' => 'NGO-BD-2024-00142', 'address' => 'House 12, Road 5, Dhanmondi, Dhaka'],
        );
    }
}
