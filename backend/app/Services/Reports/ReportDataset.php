<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

/**
 * The shared shape every report builder produces and every writer consumes
 * (see docs/database-design.md §11). Rows are pre-formatted to display
 * strings by the builder, so the csv/xlsx/pdf writers stay dumb formatters
 * with no per-report-type branching.
 */
class ReportDataset
{
    /**
     * @param  array<string, string>  $columns  ordered column key => display label
     * @param  Collection<int, array<string, string>>  $rows  each keyed the same as $columns
     */
    public function __construct(
        public readonly string $title,
        public readonly array $columns,
        public readonly Collection $rows,
    ) {}
}
