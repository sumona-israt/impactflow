<?php

use App\Models\Beneficiary;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Program;

return [

    /*
    |--------------------------------------------------------------------------
    | Odoo connection
    |--------------------------------------------------------------------------
    |
    | See docs/odoo-integration.md. mock mode (the default) needs no live
    | server — a FakeOdooClient simulates responses instead. Credentials are
    | never hardcoded/committed; real values stay in .env.
    |
    */

    'mode' => env('ODOO_MODE', 'mock'),
    'base_url' => env('ODOO_BASE_URL'),
    'database' => env('ODOO_DATABASE'),
    'username' => env('ODOO_USERNAME'),
    'password' => env('ODOO_PASSWORD'),

    // 0-1, mock mode only — simulates transport failures so retry/recovery
    // is demoable without touching infrastructure.
    'mock_failure_rate' => (float) env('ODOO_MOCK_FAILURE_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Entity mappings
    |--------------------------------------------------------------------------
    |
    | One entry per synced entity: the Odoo model it syncs to and a flat
    | local-attribute => Odoo-field map. Config-driven rather than hardcoded
    | per entity, so adapting to a different Odoo version/module set is a
    | config change (see docs/odoo-integration.md §4).
    |
    */

    'mappings' => [
        Program::class => [
            'slug' => 'program',
            'model' => 'project.project',
            'fields' => [
                'name' => 'name',
                'description' => 'description',
                'district' => 'x_district',
            ],
        ],
        Employee::class => [
            'slug' => 'employee',
            'model' => 'hr.employee',
            'fields' => [
                'name' => 'name',
                'position' => 'job_title',
            ],
        ],
        Beneficiary::class => [
            'slug' => 'beneficiary',
            'model' => 'res.partner',
            'fields' => [
                'full_name' => 'name',
                'phone' => 'phone',
                'address' => 'street',
            ],
        ],
        Expense::class => [
            'slug' => 'expense',
            'model' => 'hr.expense',
            'fields' => [
                'description' => 'name',
                'amount' => 'total_amount_currency',
            ],
        ],
    ],

];
