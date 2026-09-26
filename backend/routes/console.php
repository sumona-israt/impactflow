<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Odoo -> ImpactFlow polling (see docs/odoo-integration.md §6).
Schedule::command('odoo:poll')->everyFiveMinutes();
