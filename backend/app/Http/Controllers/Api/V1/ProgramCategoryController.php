<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProgramCategoryResource;
use App\Models\ProgramCategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProgramCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyProgramCategories->value);

        return ApiResponse::data(ProgramCategoryResource::collection(ProgramCategory::orderBy('name')->get()));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageProgramCategories->value);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:program_categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $category = ProgramCategory::create($data);

        return ApiResponse::data(new ProgramCategoryResource($category), status: 201);
    }

    public function update(Request $request, ProgramCategory $programCategory): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageProgramCategories->value);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:program_categories,name,'.$programCategory->id],
            'description' => ['nullable', 'string'],
        ]);

        $programCategory->update($data);

        return ApiResponse::data(new ProgramCategoryResource($programCategory));
    }
}
