<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    private const BRANCHES = [
        ['name' => 'Dhaka Head Office', 'district' => 'Dhaka', 'upazila' => 'Dhanmondi'],
        ['name' => 'Chattogram Field Office', 'district' => 'Chattogram', 'upazila' => 'Pahartali'],
        ['name' => 'Rajshahi Field Office', 'district' => 'Rajshahi', 'upazila' => 'Boalia'],
    ];

    public function run(): void
    {
        $organizationId = Organization::current()->id;

        foreach (self::BRANCHES as $branch) {
            Branch::firstOrCreate(
                ['organization_id' => $organizationId, 'name' => $branch['name']],
                ['district' => $branch['district'], 'upazila' => $branch['upazila']],
            );
        }
    }
}
