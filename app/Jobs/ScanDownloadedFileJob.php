<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
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

            // Update status to scanning file
            $statusService->updateStatus($file, DownloadStatus::SCANNING_FILE, 'Scanning downloaded file with VirusTotal');

            // Check file size to determine scan method
            $fileSize = Storage::disk('quarantine')->size($file->path);
            $maxDirectUploadSize = 32 * 1024 * 1024; // 32MB

            $scanResult = null;
            $scanId = null;

            if ($fileSize <= $maxDirectUploadSize) {
                // Use direct file scan for files <= 32MB
                Log::info('Scanning file directly (size <= 32MB)', [
                    'file_id' => $file->id,
                    'size' => $fileSize,
                ]);

                $scanResult = $virusTotalService->scanFile($file->path, 'quarantine');
                $scanId = $scanResult['data']['id'] ?? null;

                $file->update([
                    'virustotal_scan_id' => $scanId,
                    'virustotal_status' => 'scanning',
                ]);
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

                $file->update([
                    'virustotal_scan_id' => $scanId,
                    'virustotal_status' => 'scanning',
                ]);
            }

            // Get file report
            // Note: VirusTotal scans may take time, so we might need to poll
            // For now, we'll try to get the report immediately
            // If it's still processing, we can implement polling in a separate job
            try {
                $reportResult = $virusTotalService->getFileReport($scanId);
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

            } catch (Exception $e) {
                // Report might not be ready yet, need to poll
                Log::warning('Could not get file report immediately, scan may still be processing', [
                    'file_id' => $file->id,
                    'scan_id' => $scanId,
                    'error' => $e->getMessage(),
                ]);

                // Dispatch a delayed job to check the report again in 30 seconds
                ScanDownloadedFileJob::dispatch($this->fileId)
                    ->delay(now()->addSeconds(30));
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

