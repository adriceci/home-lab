<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\VirusTotalController;
use App\Http\Controllers\TorrentSearchController;
use App\Http\Controllers\DomainController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // VirusTotal API routes
    Route::prefix('virustotal')->group(function () {
        // URL scanning
        Route::post('/scan-url', [VirusTotalController::class, 'scanUrl']);
        Route::get('/analysis/{id}', [VirusTotalController::class, 'getAnalysis']);
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

    // Torrent Search API routes
    Route::prefix('torrents')->group(function () {
        Route::post('/search', [TorrentSearchController::class, 'search']);
        Route::post('/download', [TorrentSearchController::class, 'download']);
    });

    // File routes
    Route::prefix('files')->group(function () {
        Route::get('/', [FileController::class, 'index']);
        Route::get('/{fileId}/download-status', [FileController::class, 'downloadStatus']);
    });

    // Domain/Torrent Sites API routes
    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'index']);
        Route::post('/', [DomainController::class, 'store']);
        Route::get('/{domain}', [DomainController::class, 'show']);
        Route::put('/{domain}', [DomainController::class, 'update']);
        Route::delete('/{domain}', [DomainController::class, 'destroy']);
        
        // VirusTotal routes for domains
        Route::get('/{domain}/virustotal', [DomainController::class, 'getVirusTotalInfo']);
        Route::post('/{domain}/refresh-virustotal', [DomainController::class, 'refreshVirusTotalInfo']);
    });
});
