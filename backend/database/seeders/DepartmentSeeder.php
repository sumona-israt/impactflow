<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    private const DEPARTMENTS = ['Programs', 'Finance', 'Human Resources', 'Monitoring & Evaluation'];

    public function run(): void
    {
        $organizationId = Organization::current()->id;

        foreach (self::DEPARTMENTS as $name) {
            Department::firstOrCreate(['organization_id' => $organizationId, 'name' => $name]);
        }
    }
}
