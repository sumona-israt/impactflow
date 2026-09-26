<?php

namespace App\Services\Reports\Writers;

use App\Enums\ReportType;
use App\Services\Reports\ReportDataset;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Renders one generic Blade view (resources/views/reports/pdf.blade.php) to
 * HTML, then feeds it to dompdf directly — composer.json only requires the
 * core dompdf/dompdf package, not the barryvdh/laravel-dompdf wrapper, so
 * there's no facade to call here.
 */
class PdfReportWriter
{
    public function write(ReportType $type, ReportDataset $dataset): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.pdf', ['dataset' => $dataset])->render());
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        $path = 'reports/'.$type->value.'-'.Str::uuid().'.pdf';
        Storage::disk('local')->makeDirectory('reports');
        Storage::disk('local')->put($path, $dompdf->output());

        return $path;
    }
}
