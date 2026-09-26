<?php

namespace Database\Seeders;

use App\Models\OdooConnection;
use Illuminate\Database\Seeder;

/**
 * Single-row seeder — base_url/database/mode/credentials stay env-only (see
 * docs/database-design.md §10); this row only carries the SuperAdmin-mutable
 * is_active pause/resume toggle.
 */
class OdooConnectionSeeder extends Seeder
{
    public function run(): void
    {
        OdooConnection::firstOrCreate(
            ['name' => 'primary'],
            ['is_active' => true],
        );
    }
}
