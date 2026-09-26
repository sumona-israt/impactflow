<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BeneficiaryController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DataImportController;
use App\Http\Controllers\Api\V1\DataQualityIssueController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OdooController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ProgramCategoryController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VolunteerController;
use App\Http\Controllers\Api\V1\WorkflowController;
use App\Http\Controllers\Api\V1\WorkflowInstanceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update']);
        Route::put('/users/{user}/roles', [UserController::class, 'syncRoles']);
        Route::patch('/users/{user}/active', [UserController::class, 'toggleActive']);

        Route::get('/roles', [RoleController::class, 'index']);
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions']);

        Route::get('/permissions', [PermissionController::class, 'index']);

        Route::get('/audit-logs', [AuditLogController::class, 'index']);

        // Organization lookups
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::put('/departments/{department}', [DepartmentController::class, 'update']);

        Route::get('/branches', [BranchController::class, 'index']);
        Route::post('/branches', [BranchController::class, 'store']);
        Route::put('/branches/{branch}', [BranchController::class, 'update']);

        Route::get('/program-categories', [ProgramCategoryController::class, 'index']);
        Route::post('/program-categories', [ProgramCategoryController::class, 'store']);
        Route::put('/program-categories/{programCategory}', [ProgramCategoryController::class, 'update']);

        // Programs
        Route::apiResource('programs', ProgramController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('/programs/{program}/status', [ProgramController::class, 'updateStatus']);
        Route::get('/programs/{program}/beneficiaries', [ProgramController::class, 'beneficiaries']);
        Route::post('/programs/{program}/beneficiaries', [ProgramController::class, 'enroll']);
        Route::delete('/programs/{program}/beneficiaries/{enrollment}', [ProgramController::class, 'unenroll']);
        Route::get('/programs/{program}/activities', [ProgramController::class, 'activities']);
        Route::post('/programs/{program}/activities', [ActivityController::class, 'store']);

        // Beneficiaries
        Route::apiResource('beneficiaries', BeneficiaryController::class)->only(['index', 'store', 'show', 'update']);
        Route::get('/beneficiaries/{beneficiary}/enrollments', [BeneficiaryController::class, 'enrollments']);

        // Staff & volunteers
        Route::apiResource('employees', EmployeeController::class)->only(['index', 'store', 'update']);
        Route::apiResource('volunteers', VolunteerController::class)->only(['index', 'store', 'update']);

        // Activities
        Route::get('/activities/{activity}', [ActivityController::class, 'show']);
        Route::put('/activities/{activity}', [ActivityController::class, 'update']);
        Route::get('/activities/{activity}/attendance', [ActivityController::class, 'attendance']);
        Route::post('/activities/{activity}/attendance', [ActivityController::class, 'recordAttendance']);

        // Expenses
        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index']);
        Route::post('/expense-categories', [ExpenseCategoryController::class, 'store']);
        Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update']);

        Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('/expenses/{expense}/submit', [ExpenseController::class, 'submit']);
        Route::post('/expenses/{expense}/attachments', [ExpenseController::class, 'uploadAttachment']);
        Route::get('/expenses/{expense}/attachments/{attachment}/download', [ExpenseController::class, 'downloadAttachment']);

        // Assets
        Route::apiResource('assets', AssetController::class)->only(['index', 'store', 'update']);
        Route::post('/assets/{asset}/assign', [AssetController::class, 'assign']);
        Route::post('/assets/{asset}/return', [AssetController::class, 'returnAsset']);

        // Workflow engine (generic — see docs/database-design.md §8)
        Route::get('/workflows', [WorkflowController::class, 'index']);
        Route::post('/workflow-instances/{instance}/actions', [WorkflowInstanceController::class, 'act']);

        // Data imports & quality (see docs/database-design.md §9)
        Route::post('/imports', [DataImportController::class, 'store']);
        Route::get('/imports', [DataImportController::class, 'index']);
        Route::get('/imports/{dataImport}', [DataImportController::class, 'show']);
        Route::put('/imports/{dataImport}/mapping', [DataImportController::class, 'updateMapping']);
        Route::post('/imports/{dataImport}/preview', [DataImportController::class, 'preview']);
        Route::get('/imports/{dataImport}/preview', [DataImportController::class, 'showPreview']);
        Route::post('/imports/{dataImport}/commit', [DataImportController::class, 'commit']);

        Route::get('/data-quality/issues', [DataQualityIssueController::class, 'index']);
        Route::post('/data-quality/issues/{dataQualityIssue}/resolve', [DataQualityIssueController::class, 'resolve']);
        Route::post('/data-quality/issues/{dataQualityIssue}/ignore', [DataQualityIssueController::class, 'ignore']);
        Route::get('/data-quality/score', [DataQualityIssueController::class, 'score']);

        // Analytics: dashboard + reports (see docs/database-design.md §11)
        Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/{type}', [ReportController::class, 'generate']);
        Route::get('/reports/{report}/download', [ReportController::class, 'download']);

        // Odoo integration (see docs/database-design.md §10)
        Route::get('/odoo/status', [OdooController::class, 'status']);
        Route::get('/odoo/sync-logs', [OdooController::class, 'syncLogs']);
        Route::post('/odoo/sync/{entity}/{id}/retry', [OdooController::class, 'retry']);
        Route::get('/odoo/config', [OdooController::class, 'config']);
        Route::put('/odoo/config', [OdooController::class, 'updateConfig']);

        // Notifications (see docs/database-design.md §11)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    });
});
