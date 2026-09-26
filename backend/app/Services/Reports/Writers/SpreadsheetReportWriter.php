<?php

namespace App\Services\Reports\Writers;

use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Services\Reports\ReportDataset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Writer-side counterpart to Services\Import\DataImportSpreadsheetReader —
 * same package (phpoffice/phpspreadsheet), used directly rather than through
 * maatwebsite/excel, now for the write direction.
 */
class SpreadsheetReportWriter
{
    public function write(ReportType $type, ReportFormat $format, ReportDataset $dataset): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // setCellValueExplicit(..., TYPE_STRING) rather than fromArray(), so
        // PhpSpreadsheet's numeric auto-detection can't reformat a builder's
        // pre-formatted string (e.g. "111.50" losing its trailing zero).
        $this->writeRow($sheet, 1, array_values($dataset->columns));

        $rowNumber = 2;
        foreach ($dataset->rows as $row) {
            $this->writeRow($sheet, $rowNumber, array_values($row));
            $rowNumber++;
        }

        $writer = IOFactory::createWriter($spreadsheet, $format === ReportFormat::Csv ? 'Csv' : 'Xlsx');

        $path = 'reports/'.$type->value.'-'.Str::uuid().'.'.$format->value;
        $absolutePath = Storage::disk('local')->path($path);
        Storage::disk('local')->makeDirectory('reports');
        $writer->save($absolutePath);

        return $path;
    }

    /**
     * @param  list<string>  $values
     */
    private function writeRow(Worksheet $sheet, int $rowNumber, array $values): void
    {
        foreach ($values as $index => $value) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValueExplicit("{$column}{$rowNumber}", $value, DataType::TYPE_STRING);
        }
    }
}
