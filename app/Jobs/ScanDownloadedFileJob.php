<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Exceptions\VirusTotalException;
use App\Jobs\MoveFileToStorageJob;
use App\Models\File;
use App\Services\DownloadStatusService;
use App\Services\QuarantineService;
use App\Services\VirusTotalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ScanDownloadedFileJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 120; // Wait 2 minutes between retries

    private string $fileId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fileId)
    {
        $this->fileId = $fileId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        VirusTotalService $virusTotalService,
        QuarantineService $quarantineService,
        DownloadStatusService $statusService
    ): void {
        Log::info('Scanning downloaded file with VirusTotal', [
            'file_id' => $this->fileId,
        ]);

        try {
            $file = File::find($this->fileId);

            if (!$file) {
                throw new Exception("File not found: {$this->fileId}");
            }

            if ($file->storage_disk !== 'quarantine') {
                throw new Exception("File is not in quarantine. Current disk: {$file->storage_disk}");
            }

            if (!Storage::disk('quarantine')->exists($file->path)) {
                throw new Exception("File not found in quarantine: {$file->path}");
            }

            // Check if file was already scanned and completed
            if ($file->virustotal_status === 'completed') {
                // Check if it was malicious
                if ($file->virustotal_results && $this->isFileMalicious($file->virustotal_results)) {
                    Log::info('File was previously flagged as malicious', [
                        'file_id' => $file->id,
                        'file_name' => $file->name,
                    ]);
                    $statusService->updateStatus($file, DownloadStatus::FILE_REJECTED, 'File previously flagged as malicious');
                    return;
                }

                // File was already verified as safe, proceed to move
                Log::info('File was previously verified as safe, proceeding to move', [
                    'file_id' => $file->id,
                    'file_name' => $file->name,
                ]);
                $statusService->updateStatus($file, DownloadStatus::FILE_VERIFIED, 'File verified as safe');
                MoveFileToStorageJob::dispatch($file->id);
                return;
            }

            // Update status to scanning file
            // This will also automatically set virustotal_status to 'scanning' via status mapping
            $statusService->updateStatus($file, DownloadStatus::SCANNING_FILE, 'Scanning downloaded file with VirusTotal');

            // Determine if we need to initiate a new scan or check existing one
            $scanId = null;
            $needsNewScan = false;

            if ($file->virustotal_status === 'scanning' && $file->virustotal_scan_id) {
                // Scan already in progress, use existing scan_id
                $scanId = $file->virustotal_scan_id;
                Log::info('Using existing scan ID', [
                    'file_id' => $file->id,
                    'scan_id' => $scanId,
                ]);
            } elseif ($file->virustotal_status === 'pending' || !$file->virustotal_scan_id) {
                // Need to initiate a new scan
                $needsNewScan = true;
                Log::info('Initiating new file scan', [
                    'file_id' => $file->id,
                ]);
            } else {
                // Unexpected status, treat as if we need a new scan
                $needsNewScan = true;
                Log::warning('Unexpected scan status, initiating new scan', [
                    'file_id' => $file->id,
                    'status' => $file->virustotal_status,
                ]);
            }

            // Initiate file scan only if needed
            if ($needsNewScan) {
                // Check file size to determine scan method
                $fileSize = Storage::disk('quarantine')->size($file->path);
                $maxDirectUploadSize = 32 * 1024 * 1024; // 32MB

                if ($fileSize <= $maxDirectUploadSize) {
                    // Use direct file scan for files <= 32MB
                    Log::info('Scanning file directly (size <= 32MB)', [
                        'file_id' => $file->id,
                        'size' => $fileSize,
                    ]);

                    $scanResult = $virusTotalService->scanFile($file->path, 'quarantine');
                    $scanId = $scanResult['data']['id'] ?? null;

                    if (!$scanId) {
                        throw new Exception('Failed to get scan ID from VirusTotal');
                    }

                    // Update file with scan ID and status
                    // The virustotal_status will be automatically updated by updateStatus when we set SCANNING_FILE
                    $file->update([
                        'virustotal_scan_id' => $scanId,
                    ]);

                    // Update status - this will also update virustotal_status to 'scanning' via the status mapping
                    $statusService->updateStatus($file, DownloadStatus::SCANNING_FILE, 'File scan initiated');
                } else {
                    // Use large file upload for files > 32MB
                    Log::info('Using large file upload method (size > 32MB)', [
                        'file_id' => $file->id,
                        'size' => $fileSize,
                    ]);

                    // Get upload URL
                    $uploadUrlResult = $virusTotalService->getLargeFileUploadUrl();
                    $uploadUrl = $uploadUrlResult['data'] ?? null;

                    if (empty($uploadUrl)) {
                        throw new Exception('Failed to get large file upload URL');
                    }

                    // Upload large file
                    $scanResult = $virusTotalService->uploadLargeFile($file->path, $uploadUrl, 'quarantine');
                    $scanId = $scanResult['data']['id'] ?? null;

                    if (!$scanId) {
                        throw new Exception('Failed to get scan ID from VirusTotal');
                    }

                    // Update file with scan ID
                    // The virustotal_status will be automatically updated by updateStatus when we set SCANNING_FILE
                    $file->update([
                        'virustotal_scan_id' => $scanId,
                    ]);

                    // Update status - this will also update virustotal_status to 'scanning' via the status mapping
                    $statusService->updateStatus($file, DownloadStatus::SCANNING_FILE, 'Large file scan initiated');
                }
            }

            // Check analysis status first, then get full report using file hash
            try {
                // Get analysis status using the Analysis ID
                $analysisResult = $virusTotalService->getAnalysis($scanId);

                // Check if analysis is complete
                $analysisStatus = $analysisResult['data']['attributes']['status'] ?? null;

                if ($analysisStatus !== 'completed') {
                    // Analysis not ready yet, check if we should retry or timeout
                    $retryDelay = 30; // seconds
                    $maxRetryAge = now()->subMinutes(10); // Don't retry if scan is older than 10 minutes
                    $scanInitiatedAt = $file->virustotal_status === 'scanning' && $file->virustotal_scan_id
                        ? $file->updated_at
                        : now();

                    if ($scanInitiatedAt && $scanInitiatedAt->lt($maxRetryAge)) {
                        Log::warning('Analysis taking too long, aborting verification', [
                            'file_id' => $file->id,
                            'analysis_id' => $scanId,
                            'status' => $analysisStatus,
                            'scan_initiated_at' => $scanInitiatedAt,
                        ]);

                        // Update file with timeout status
                        $file->update([
                            'virustotal_status' => 'timeout',
                        ]);

                        $statusService->updateStatus($file, DownloadStatus::FAILED, 'File scan timeout');
                        return;
                    }

                    // Refresh to get latest state before scheduling retry
                    $file->fresh();

                    // Only schedule retry if status is still scanning (prevents duplicate retries)
                    if ($file->virustotal_status !== 'scanning') {
                        Log::info('Scan status changed, not scheduling retry', [
                            'file_id' => $file->id,
                            'current_status' => $file->virustotal_status,
                        ]);
                        return;
                    }

                    Log::info('Analysis not ready yet, scheduling retry', [
                        'file_id' => $file->id,
                        'analysis_id' => $scanId,
                        'status' => $analysisStatus,
                        'retry_delay' => $retryDelay,
                    ]);

                    // Schedule a single retry job with delay
                    ScanDownloadedFileJob::dispatch($this->fileId)
                        ->delay(now()->addSeconds($retryDelay));
                    return;
                }

                // Analysis is complete, get the file hash to retrieve full report
                // Extract hash from analysis response if available, otherwise calculate it
                $fileHash = null;

                // Try to get hash from analysis metadata
                if (isset($analysisResult['data']['attributes']['meta']['file_info']['sha256'])) {
                    $fileHash = $analysisResult['data']['attributes']['meta']['file_info']['sha256'];
                } else {
                    // Calculate SHA-256 hash of the file locally
                    $fileContent = Storage::disk('quarantine')->get($file->path);
                    $fileHash = hash('sha256', $fileContent);
                    Log::info('Calculated file hash locally', [
                        'file_id' => $file->id,
                        'hash' => $fileHash,
                    ]);
                }

                if (!$fileHash) {
                    throw new Exception('Failed to determine file hash for report retrieval');
                }

                // Get full file report using the hash
                $reportResult = $virusTotalService->getFileReport($fileHash);

                // Check if the report has complete analysis results
                $hasCompleteResults = $this->hasCompleteAnalysisResults($reportResult);

                if (!$hasCompleteResults) {
                    // This shouldn't happen if analysis is completed, but handle it just in case
                    Log::warning('Analysis completed but report incomplete, retrying', [
                        'file_id' => $file->id,
                        'analysis_id' => $scanId,
                        'file_hash' => $fileHash,
                    ]);

                    // Retry after a delay
                    ScanDownloadedFileJob::dispatch($this->fileId)
                        ->delay(now()->addSeconds(30));
                    return;
                }

                // Report is ready, process it
                $isMalicious = $this->isFileMalicious($reportResult);

                // Update file with scan results
                $file->update([
                    'virustotal_status' => 'completed',
                    'virustotal_results' => $reportResult,
                    'virustotal_scanned_at' => now(),
                ]);

                if ($isMalicious) {
                    Log::warning('File flagged as malicious by VirusTotal', [
                        'file_id' => $file->id,
                        'file_name' => $file->name,
                    ]);

                    $statusService->updateStatus($file, DownloadStatus::FILE_REJECTED, 'File flagged as malicious by VirusTotal');

                    $quarantineService->handleMaliciousFile($file, $reportResult);
                    return;
                }

                // File is safe, proceed to move to storage
                Log::info('File verified as safe, proceeding to move to storage', [
                    'file_id' => $file->id,
                    'file_name' => $file->name,
                ]);

                $statusService->updateStatus($file, DownloadStatus::FILE_VERIFIED, 'File verified as safe');

                MoveFileToStorageJob::dispatch($file->id);
            } catch (VirusTotalException $e) {
                // VirusTotal-specific error - update virustotal_status based on error type
                Log::warning('VirusTotal error while getting analysis or file report', [
                    'file_id' => $file->id,
                    'analysis_id' => $scanId,
                    'error_code' => $e->getErrorCode(),
                    'http_code' => $e->getHttpCode(),
                    'error' => $e->getMessage(),
                    'retryable' => $e->isRetryable(),
                ]);

                // Check if scan was just initiated (should allow some time)
                $retryDelay = 30;
                $maxRetryAge = now()->subMinutes(10);
                $scanInitiatedAt = $file->virustotal_status === 'scanning' && $file->virustotal_scan_id
                    ? $file->updated_at
                    : now();

                if ($scanInitiatedAt && $scanInitiatedAt->lt($maxRetryAge) || !$e->isRetryable()) {
                    Log::error('Failed to get analysis or file report after multiple attempts', [
                        'file_id' => $file->id,
                        'analysis_id' => $scanId,
                        'scan_initiated_at' => $scanInitiatedAt,
                        'error_code' => $e->getErrorCode(),
                    ]);

                    // Update status with VirusTotal exception to automatically set virustotal_status
                    $statusService->updateStatus($file, DownloadStatus::FAILED, 'Failed to verify file', $e);

                    throw $e;
                }

                // Retry if error is retryable and scan is still recent
                if ($e->isRetryable()) {
                    $file->fresh();
                    if ($file->virustotal_status === 'scanning') {
                        ScanDownloadedFileJob::dispatch($this->fileId)
                            ->delay(now()->addSeconds($retryDelay));
                    }
                }
                return;
            } catch (Exception $e) {
                // Generic error getting analysis or report
                Log::warning('Error getting analysis or file report, may need to retry', [
                    'file_id' => $file->id,
                    'analysis_id' => $scanId,
                    'error' => $e->getMessage(),
                ]);

                // Check if scan was just initiated (should allow some time)
                $retryDelay = 30;
                $maxRetryAge = now()->subMinutes(10);
                $scanInitiatedAt = $file->virustotal_status === 'scanning' && $file->virustotal_scan_id
                    ? $file->updated_at
                    : now();

                if ($scanInitiatedAt && $scanInitiatedAt->lt($maxRetryAge)) {
                    Log::error('Failed to get analysis or file report after multiple attempts', [
                        'file_id' => $file->id,
                        'analysis_id' => $scanId,
                        'scan_initiated_at' => $scanInitiatedAt,
                    ]);

                    // Update file with generic error status
                    $file->update([
                        'virustotal_status' => 'error',
                    ]);

                    $statusService->updateStatus($file, DownloadStatus::FAILED, 'Failed to verify file');

                    throw $e;
                }

                // Only schedule retry if status is still scanning (don't retry if it changed)
                $file->fresh();
                if ($file->virustotal_status === 'scanning') {
                    // Schedule a retry only if scan is still recent
                    ScanDownloadedFileJob::dispatch($this->fileId)
                        ->delay(now()->addSeconds($retryDelay));
                } else {
                    // Status changed (maybe completed by another job), don't retry
                    Log::info('Scan status changed, not scheduling retry', [
                        'file_id' => $file->id,
                        'current_status' => $file->virustotal_status,
                    ]);
                }
            } catch (VirusTotalException $e) {
                // VirusTotal error - update status accordingly
                Log::error('VirusTotal error during file scan', [
                    'file_id' => $file->id,
                    'error_code' => $e->getErrorCode(),
                    'http_code' => $e->getHttpCode(),
                    'error' => $e->getMessage(),
                ]);

                // Update status with VirusTotal exception to automatically set virustotal_status
                $statusService->updateStatus($file, DownloadStatus::FAILED, $e->getMessage(), $e);

                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to scan downloaded file', [
                'file_id' => $this->fileId,
                'error' => $e->getMessage(),
            ]);

            // Update file status
            $file = File::find($this->fileId);
            if ($file) {
                $statusService->markAsFailed($file, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Check if VirusTotal report has complete analysis results
     */
    private function hasCompleteAnalysisResults(array $virusTotalResponse): bool
    {
        // Check if the response has the expected structure
        if (!isset($virusTotalResponse['data']['attributes'])) {
            return false;
        }

        $attributes = $virusTotalResponse['data']['attributes'];

        // Check if we have analysis stats (indicates scan has been processed)
        if (isset($attributes['last_analysis_stats'])) {
            $stats = $attributes['last_analysis_stats'];

            // If we have stats with at least some results, consider it complete
            $totalResults = ($stats['harmless'] ?? 0) +
                ($stats['malicious'] ?? 0) +
                ($stats['suspicious'] ?? 0) +
                ($stats['undetected'] ?? 0);

            // Report is considered complete if we have at least some analysis results
            // or if it has a reputation score (which indicates it was analyzed)
            return $totalResults > 0 || isset($attributes['reputation']);
        }

        // For files, also check if status indicates completion
        // VirusTotal file reports might have a 'status' field
        if (isset($attributes['status'])) {
            // Status can be 'queued', 'in-progress', 'completed'
            return $attributes['status'] === 'completed' || $attributes['status'] === 'finished';
        }

        // If we have reputation or other analysis data, consider it complete
        return isset($attributes['reputation']) ||
            isset($attributes['last_analysis_date']) ||
            isset($attributes['last_modification_date']);
    }

    /**
     * Check if file is malicious based on VirusTotal response
     */
    private function isFileMalicious(array $virusTotalResponse): bool
    {
        // Check analysis stats
        if (isset($virusTotalResponse['data']['attributes']['last_analysis_stats'])) {
            $stats = $virusTotalResponse['data']['attributes']['last_analysis_stats'];

            // If any security vendor flagged it as malicious, reject it
            if (isset($stats['malicious']) && $stats['malicious'] > 0) {
                return true;
            }

            // Also reject if suspicious
            if (isset($stats['suspicious']) && $stats['suspicious'] > 0) {
                return true;
            }
        }

        // Check reputation
        if (isset($virusTotalResponse['data']['attributes']['reputation'])) {
            $reputation = $virusTotalResponse['data']['attributes']['reputation'];
            // Reputation < 0 typically means malicious
            if ($reputation < 0) {
                return true;
            }
        }

        return false;
    }
}
