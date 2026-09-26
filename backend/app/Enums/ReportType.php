<?php

namespace App\Enums;

enum ReportType: string
{
    case ProgramPerformance = 'program-performance';
    case Beneficiaries = 'beneficiaries';
    case Financial = 'financial';
    case DataQuality = 'data-quality';

    /** The permission required to generate this report — see docs/database-design.md §11. */
    public function permission(): PermissionEnum
    {
        return match ($this) {
            self::ProgramPerformance => PermissionEnum::ViewAnyPrograms,
            self::Beneficiaries => PermissionEnum::ViewBeneficiary,
            self::Financial => PermissionEnum::ViewAnyExpenses,
            self::DataQuality => PermissionEnum::ViewAnyDataQualityIssues,
        };
    }
}
