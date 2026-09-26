<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DataQualityIssueStatus;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\DataQualityIssueResource;
use App\Models\Beneficiary;
use App\Models\DataQualityIssue;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DataQualityIssueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyDataQualityIssues->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $issues = DataQualityIssue::query()
            ->with('entity')
            ->when($request->filled('filter.status'), fn ($q) => $q->where('status', $request->input('filter.status')))
            ->orderByDesc('detected_at')
            ->paginate($perPage);

        return ApiResponse::data(
            DataQualityIssueResource::collection($issues->items()),
            ['page' => $issues->currentPage(), 'per_page' => $issues->perPage(), 'total' => $issues->total()],
        );
    }

    public function resolve(DataQualityIssue $dataQualityIssue): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageDataQualityIssues->value);

        $dataQualityIssue->update([
            'status' => DataQualityIssueStatus::Resolved,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        return ApiResponse::data(new DataQualityIssueResource($dataQualityIssue));
    }

    public function ignore(DataQualityIssue $dataQualityIssue): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageDataQualityIssues->value);

        $dataQualityIssue->update([
            'status' => DataQualityIssueStatus::Ignored,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        return ApiResponse::data(new DataQualityIssueResource($dataQualityIssue));
    }

    public function score(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyDataQualityIssues->value);

        $totalBeneficiaries = Beneficiary::count();
        $openIssues = DataQualityIssue::where('status', DataQualityIssueStatus::Open)->count();

        // See docs/database-design.md §9 for the formula's reasoning.
        $score = round(100 * (1 - $openIssues / max($totalBeneficiaries, 1)), 1);

        return ApiResponse::data([
            'score' => max(0.0, min(100.0, $score)),
            'total_beneficiaries' => $totalBeneficiaries,
            'open_issues' => $openIssues,
        ]);
    }
}
