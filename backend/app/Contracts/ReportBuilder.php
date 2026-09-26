<?php

namespace App\Contracts;

use App\Services\Reports\ReportDataset;

/**
 * One implementation per App\Enums\ReportType, resolved by
 * App\Services\Reports\ReportBuilderFactory (see docs/database-design.md §11).
 */
interface ReportBuilder
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function build(array $parameters): ReportDataset;
}
