<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyExpenseCategories->value);

        return ApiResponse::data(ExpenseCategoryResource::collection(ExpenseCategory::orderBy('name')->get()));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageExpenseCategories->value);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
        ]);

        $category = ExpenseCategory::create($data);

        return ApiResponse::data(new ExpenseCategoryResource($category), status: 201);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageExpenseCategories->value);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:expense_categories,name,'.$expenseCategory->id],
        ]);

        $expenseCategory->update($data);

        return ApiResponse::data(new ExpenseCategoryResource($expenseCategory));
    }
}
