<?php

namespace App\Services\Reports\Builders;

use App\Contracts\ReportBuilder;
use App\Models\DataQualityIssue;
use App\Services\Reports\ReportDataset;

class DataQualityReportBuilder implements ReportBuilder
{
    public function build(array $parameters): ReportDataset
    {
        $issues = DataQualityIssue::query()
            ->with('resolver')
            ->when($parameters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($parameters['issue_type'] ?? null, fn ($q, $type) => $q->where('issue_type', $type))
            ->orderByDesc('detected_at')
            ->get();

        $rows = $issues->map(fn (DataQualityIssue $issue) => [
            'entity_type' => class_basename($issue->entity_type),
            'entity_id' => (string) $issue->entity_id,
            'issue_type' => $issue->issue_type,
            'severity' => $issue->severity,
            'description' => $issue->description ?? '—',
            'status' => $issue->status->value,
            'detected_at' => $issue->detected_at?->format('Y-m-d H:i') ?? '—',
            'resolved_at' => $issue->resolved_at?->format('Y-m-d H:i') ?? '—',
            'resolved_by' => $issue->resolver?->name ?? '—',
        ]);

        return new ReportDataset(
            title: 'Data Quality Report',
            columns: [
                'entity_type' => 'Entity type',
                'entity_id' => 'Entity ID',
                'issue_type' => 'Issue type',
                'severity' => 'Severity',
                'description' => 'Description',
                'status' => 'Status',
                'detected_at' => 'Detected at',
                'resolved_at' => 'Resolved at',
                'resolved_by' => 'Resolved by',
            ],
            rows: $rows,
        );
    }
}
