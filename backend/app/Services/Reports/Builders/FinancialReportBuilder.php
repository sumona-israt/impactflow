<?php

namespace App\Services\Reports\Builders;

use App\Contracts\ReportBuilder;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Services\Reports\ReportDataset;

class FinancialReportBuilder implements ReportBuilder
{
    public function build(array $parameters): ReportDataset
    {
        $expenses = Expense::query()
            ->with(['program', 'category', 'submitter'])
            ->when($parameters['program_id'] ?? null, fn ($q, $id) => $q->where('program_id', $id))
            ->when($parameters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            // A financial report means realized spend unless a status filter says otherwise.
            ->where('status', $parameters['status'] ?? ExpenseStatus::Approved->value)
            ->when($parameters['date_from'] ?? null, fn ($q, $date) => $q->where('expense_date', '>=', $date))
            ->when($parameters['date_to'] ?? null, fn ($q, $date) => $q->where('expense_date', '<=', $date))
            ->orderByDesc('expense_date')
            ->get();

        $rows = $expenses->map(fn (Expense $expense) => [
            'program' => $expense->program?->name ?? '—',
            'category' => $expense->category?->name ?? '—',
            'amount' => number_format((float) $expense->amount, 2),
            'currency' => $expense->currency,
            'expense_date' => $expense->expense_date?->format('Y-m-d') ?? '—',
            'status' => $expense->status->value,
            'submitted_by' => $expense->submitter?->name ?? '—',
        ]);

        return new ReportDataset(
            title: 'Financial Report',
            columns: [
                'program' => 'Program',
                'category' => 'Category',
                'amount' => 'Amount',
                'currency' => 'Currency',
                'expense_date' => 'Expense date',
                'status' => 'Status',
                'submitted_by' => 'Submitted by',
            ],
            rows: $rows,
        );
    }
}
