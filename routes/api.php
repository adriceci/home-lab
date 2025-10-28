<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\TestController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Audit Log routes (protected)
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::get('/audit-logs-stats', [AuditLogController::class, 'stats']);

    // Test routes to demonstrate middleware functionality
    Route::get('/test', [TestController::class, 'index']);
    Route::post('/test', [TestController::class, 'store']);
    Route::put('/test/{user}', [TestController::class, 'update']);
    Route::delete('/test/{user}', [TestController::class, 'destroy']);
});
