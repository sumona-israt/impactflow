<?php

namespace App\Services\Reports\Builders;

use App\Contracts\ReportBuilder;
use App\Models\Program;
use App\Services\Reports\ReportDataset;

class ProgramPerformanceReportBuilder implements ReportBuilder
{
    public function build(array $parameters): ReportDataset
    {
        $programs = Program::query()
            ->with(['category', 'branch'])
            ->when($parameters['program_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->when($parameters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($parameters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('name')
            ->get();

        $rows = $programs->map(fn (Program $program) => [
            'name' => $program->name,
            'category' => $program->category?->name ?? '—',
            'branch' => $program->branch?->name ?? '—',
            'status' => $program->status->value,
            'start_date' => $program->start_date?->format('Y-m-d') ?? '—',
            'end_date' => $program->end_date?->format('Y-m-d') ?? '—',
            'budget' => $program->budget !== null ? number_format((float) $program->budget, 2) : '—',
            'target_beneficiaries' => $program->target_beneficiaries !== null ? (string) $program->target_beneficiaries : '—',
            'actual_beneficiaries' => (string) $program->actual_beneficiaries,
            'progress' => $program->progress !== null ? "{$program->progress}%" : '—',
        ]);

        return new ReportDataset(
            title: 'Program Performance Report',
            columns: [
                'name' => 'Name',
                'category' => 'Category',
                'branch' => 'Branch',
                'status' => 'Status',
                'start_date' => 'Start date',
                'end_date' => 'End date',
                'budget' => 'Budget',
                'target_beneficiaries' => 'Target beneficiaries',
                'actual_beneficiaries' => 'Actual beneficiaries',
                'progress' => 'Progress',
            ],
            rows: $rows,
        );
    }
}
