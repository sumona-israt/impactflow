<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Thin wrapper around PhpSpreadsheet, used directly rather than through the
 * maatwebsite/excel wrapper package — we only need read access, and
 * IOFactory::load() already handles both .csv and .xlsx transparently.
 */
class DataImportSpreadsheetReader
{
    /**
     * @return list<string>
     */
    public function headers(string $absolutePath): array
    {
        return array_values($this->headerMap($this->loadRows($absolutePath)));
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public function rows(string $absolutePath): \Generator
    {
        $sheet = $this->loadRows($absolutePath);
        $headerMap = $this->headerMap($sheet);
        $rowNumber = 0;

        foreach (array_slice($sheet, 1) as $rawRow) {
            $rowNumber++;
            $values = [];
            $hasData = false;

            foreach ($headerMap as $columnIndex => $header) {
                $value = $rawRow[$columnIndex] ?? null;

                if ($value !== null && $value !== '') {
                    $hasData = true;
                }

                $values[$header] = is_string($value) ? trim($value) : $value;
            }

            if ($hasData) {
                yield $rowNumber => $values;
            }
        }
    }

    /**
     * @return list<list<mixed>>
     */
    private function loadRows(string $absolutePath): array
    {
        return IOFactory::load($absolutePath)->getActiveSheet()->toArray(null, true, true, false);
    }

    /**
     * @param  list<list<mixed>>  $sheet
     * @return array<int, string>
     */
    private function headerMap(array $sheet): array
    {
        $map = [];

        foreach ($sheet[0] ?? [] as $columnIndex => $value) {
            $header = trim((string) $value);
            if ($header !== '') {
                $map[$columnIndex] = $header;
            }
        }

        return $map;
    }
}
