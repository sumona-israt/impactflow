<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BeneficiaryController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ProgramCategoryController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VolunteerController;
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
    });
});
