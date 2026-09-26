<?php

use App\Enums\PermissionEnum;
use App\Enums\ReportType;

test('each report type maps to the correct permission', function () {
    expect(ReportType::ProgramPerformance->permission())->toBe(PermissionEnum::ViewAnyPrograms);
    expect(ReportType::Beneficiaries->permission())->toBe(PermissionEnum::ViewBeneficiary);
    expect(ReportType::Financial->permission())->toBe(PermissionEnum::ViewAnyExpenses);
    expect(ReportType::DataQuality->permission())->toBe(PermissionEnum::ViewAnyDataQualityIssues);
});

test('every report type case has a permission mapping', function (ReportType $type) {
    expect($type->permission())->toBeInstanceOf(PermissionEnum::class);
})->with(ReportType::cases());
