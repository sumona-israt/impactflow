<?php

use App\Enums\ReportFormat;

test('each format maps to the correct mime type', function () {
    expect(ReportFormat::Csv->mimeType())->toBe('text/csv');
    expect(ReportFormat::Xlsx->mimeType())->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect(ReportFormat::Pdf->mimeType())->toBe('application/pdf');
});

test('every format case has a non-empty mime type', function (ReportFormat $format) {
    expect($format->mimeType())->not->toBeEmpty();
})->with(ReportFormat::cases());
