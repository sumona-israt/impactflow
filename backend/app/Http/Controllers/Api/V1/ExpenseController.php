<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Expenses\CreateExpenseAction;
use App\Actions\Expenses\SubmitExpenseAction;
use App\Actions\Expenses\UpdateExpenseAction;
use App\Actions\Expenses\UploadExpenseAttachmentAction;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\StoreExpenseRequest;
use App\Http\Requests\Expenses\UpdateExpenseRequest;
use App\Http\Requests\Expenses\UploadExpenseAttachmentRequest;
use App\Http\Resources\ExpenseAttachmentResource;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyExpenses->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $expenses = Expense::query()
            ->with(['program', 'category', 'submitter'])
            ->when($request->filled('filter.status'), fn ($q) => $q->where('status', $request->input('filter.status')))
            ->when($request->filled('filter.program_id'), fn ($q) => $q->where('program_id', $request->input('filter.program_id')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::data(
            ExpenseResource::collection($expenses->items()),
            ['page' => $expenses->currentPage(), 'per_page' => $expenses->perPage(), 'total' => $expenses->total()],
        );
    }

    public function store(StoreExpenseRequest $request, CreateExpenseAction $action): JsonResponse
    {
        $expense = $action->execute($request->validated());

        return ApiResponse::data(new ExpenseResource($expense->load(['program', 'category', 'submitter'])), status: 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewExpense->value);

        $expense->load(['program', 'category', 'submitter', 'attachments', 'workflowInstances.currentStep', 'workflowInstances.actions.step', 'workflowInstances.actions.actor']);

        return ApiResponse::data(new ExpenseResource($expense));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, UpdateExpenseAction $action): JsonResponse
    {
        $expense = $action->execute($expense, $request->validated());

        return ApiResponse::data(new ExpenseResource($expense->load(['program', 'category', 'submitter'])));
    }

    public function submit(Expense $expense, SubmitExpenseAction $action): JsonResponse
    {
        Gate::authorize(PermissionEnum::UpdateExpense->value);
        abort_unless($expense->submitted_by === Auth::id() || Auth::user()->hasRole(RoleEnum::SuperAdmin->value), 403);

        $expense = $action->execute($expense);

        return ApiResponse::data(new ExpenseResource($expense->load(['program', 'category', 'submitter'])));
    }

    public function uploadAttachment(UploadExpenseAttachmentRequest $request, Expense $expense, UploadExpenseAttachmentAction $action): JsonResponse
    {
        $attachment = $action->execute($expense, $request->file('file'));

        return ApiResponse::data(new ExpenseAttachmentResource($attachment), status: 201);
    }

    public function downloadAttachment(Expense $expense, ExpenseAttachment $attachment)
    {
        Gate::authorize(PermissionEnum::ViewExpense->value);

        abort_if($attachment->expense_id !== $expense->id, 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name);
    }
}
