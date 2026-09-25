<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            DemoAdminSeeder::class,
            OrganizationSeeder::class,
            DepartmentSeeder::class,
            BranchSeeder::class,
            ProgramCategorySeeder::class,
        ]);
    }
}
