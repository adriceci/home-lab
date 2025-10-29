<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\VirusTotalController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // VirusTotal API routes
    Route::prefix('virustotal')->group(function () {
        // URL scanning
        Route::post('/scan-url', [VirusTotalController::class, 'scanUrl']);
        Route::get('/url-report/{id}', [VirusTotalController::class, 'getUrlReport']);

        // File scanning
        Route::post('/scan-file', [VirusTotalController::class, 'scanFile']);
        Route::post('/scan-file-large', [VirusTotalController::class, 'uploadLargeFile']);
        Route::get('/file-report/{id}', [VirusTotalController::class, 'getFileReport']);
        Route::get('/file-analysis/{hash}', [VirusTotalController::class, 'getFileAnalysis']);

        // Large file upload
        Route::get('/large-file-upload-url', [VirusTotalController::class, 'getLargeFileUploadUrl']);

        // Domain and IP information
        Route::get('/domain/{domain}', [VirusTotalController::class, 'getDomainInfo']);
        Route::get('/ip/{ip}', [VirusTotalController::class, 'getIpInfo']);

        // Utility endpoints
        Route::get('/quota', [VirusTotalController::class, 'getQuotaInfo']);
        Route::get('/configured', [VirusTotalController::class, 'isConfigured']);
    });
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::get('/audit-logs-stats', [AuditLogController::class, 'stats']);
});
