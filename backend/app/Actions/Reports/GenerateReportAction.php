<?php

namespace App\Actions\Reports;

use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Models\Report;
use App\Services\Audit\AuditLogger;
use App\Services\Reports\ReportBuilderFactory;
use App\Services\Reports\Writers\PdfReportWriter;
use App\Services\Reports\Writers\SpreadsheetReportWriter;
use Illuminate\Support\Facades\Auth;

class GenerateReportAction
{
    public function __construct(
        private readonly ReportBuilderFactory $builders,
        private readonly SpreadsheetReportWriter $spreadsheetWriter,
        private readonly PdfReportWriter $pdfWriter,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function execute(ReportType $type, ReportFormat $format, array $parameters): Report
    {
        $dataset = $this->builders->make($type)->build($parameters);

        $path = match ($format) {
            ReportFormat::Csv, ReportFormat::Xlsx => $this->spreadsheetWriter->write($type, $format, $dataset),
            ReportFormat::Pdf => $this->pdfWriter->write($type, $dataset),
        };

        $report = Report::create([
            'type' => $type,
            'format' => $format,
            'parameters' => $parameters,
            'file_path' => $path,
            'generated_by' => Auth::id(),
            'generated_at' => now(),
        ]);

        $this->auditLogger->logCreated($report, 'report.generated');

        return $report;
    }
}
