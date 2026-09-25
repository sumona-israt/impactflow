<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
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
    });
});
