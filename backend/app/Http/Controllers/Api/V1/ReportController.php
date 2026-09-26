<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Reports\GenerateReportAction;
use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $allowedTypes = collect(ReportType::cases())
            ->filter(fn (ReportType $type) => Gate::allows($type->permission()->value))
            ->map(fn (ReportType $type) => $type->value);

        abort_if($allowedTypes->isEmpty(), 403);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $reports = Report::query()
            ->whereIn('type', $allowedTypes)
            ->with('generator')
            ->orderByDesc('generated_at')
            ->paginate($perPage);

        return ApiResponse::data(
            ReportResource::collection($reports->items()),
            ['page' => $reports->currentPage(), 'per_page' => $reports->perPage(), 'total' => $reports->total()],
        );
    }

    public function generate(Request $request, ReportType $type, GenerateReportAction $action)
    {
        Gate::authorize($type->permission()->value);

        $format = ReportFormat::from($request->string('format', 'csv')->toString());
        $parameters = $request->except(['format']);

        $report = $action->execute($type, $format, $parameters);

        return Storage::disk('local')->download(
            $report->file_path,
            "{$type->value}.{$format->value}",
            ['Content-Type' => $format->mimeType()],
        );
    }

    public function download(Report $report)
    {
        Gate::authorize($report->type->permission()->value);

        return Storage::disk('local')->download(
            $report->file_path,
            "{$report->type->value}.{$report->format->value}",
            ['Content-Type' => $report->format->mimeType()],
        );
    }
}
