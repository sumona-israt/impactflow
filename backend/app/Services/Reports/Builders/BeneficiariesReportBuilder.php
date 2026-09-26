<?php

namespace App\Services\Reports\Builders;

use App\Contracts\ReportBuilder;
use App\Models\Beneficiary;
use App\Services\Reports\ReportDataset;

class BeneficiariesReportBuilder implements ReportBuilder
{
    public function build(array $parameters): ReportDataset
    {
        $beneficiaries = Beneficiary::query()
            ->with('programs')
            ->when(
                $parameters['program_id'] ?? null,
                fn ($q, $id) => $q->whereHas('programs', fn ($q) => $q->where('programs.id', $id)),
            )
            ->when($parameters['district'] ?? null, fn ($q, $district) => $q->where('district', $district))
            ->when($parameters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('full_name')
            ->get();

        $rows = $beneficiaries->map(fn (Beneficiary $beneficiary) => [
            'full_name' => $beneficiary->full_name,
            'gender' => $beneficiary->gender ?? '—',
            'district' => $beneficiary->district ?? '—',
            'upazila' => $beneficiary->upazila ?? '—',
            'status' => $beneficiary->status->value,
            'registration_date' => $beneficiary->registration_date?->format('Y-m-d') ?? '—',
            'enrolled_programs' => $beneficiary->programs->pluck('name')->implode(', ') ?: '—',
        ]);

        return new ReportDataset(
            title: 'Beneficiaries Report',
            columns: [
                'full_name' => 'Full name',
                'gender' => 'Gender',
                'district' => 'District',
                'upazila' => 'Upazila',
                'status' => 'Status',
                'registration_date' => 'Registration date',
                'enrolled_programs' => 'Enrolled programs',
            ],
            rows: $rows,
        );
    }
}
