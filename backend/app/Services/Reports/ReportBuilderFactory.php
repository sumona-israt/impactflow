<?php

namespace App\Services\Reports;

use App\Contracts\ReportBuilder;
use App\Enums\ReportType;
use App\Services\Reports\Builders\BeneficiariesReportBuilder;
use App\Services\Reports\Builders\DataQualityReportBuilder;
use App\Services\Reports\Builders\FinancialReportBuilder;
use App\Services\Reports\Builders\ProgramPerformanceReportBuilder;

class ReportBuilderFactory
{
    public function __construct(
        private readonly ProgramPerformanceReportBuilder $programPerformance,
        private readonly BeneficiariesReportBuilder $beneficiaries,
        private readonly FinancialReportBuilder $financial,
        private readonly DataQualityReportBuilder $dataQuality,
    ) {}

    public function make(ReportType $type): ReportBuilder
    {
        return match ($type) {
            ReportType::ProgramPerformance => $this->programPerformance,
            ReportType::Beneficiaries => $this->beneficiaries,
            ReportType::Financial => $this->financial,
            ReportType::DataQuality => $this->dataQuality,
        };
    }
}
