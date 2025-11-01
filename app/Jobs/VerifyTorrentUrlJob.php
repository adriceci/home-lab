<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Jobs\DownloadTorrentJob;
use App\Models\File;
use App\Models\ScannedUrl;
use App\Services\DownloadStatusService;
use App\Services\QuarantineService;
use App\Services\VirusTotalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class VerifyTorrentUrlJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60; // Wait 60 seconds between retries

    private string $url;
    private ?string $magnetLink;
    private ?string $torrentLink;
    private ?string $fileId;
    private array $metadata;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $url,
        ?string $magnetLink = null,
        ?string $torrentLink = null,
        ?string $fileId = null,
        array $metadata = []
    ) {
        $this->url = $url;
        $this->magnetLink = $magnetLink;
        $this->torrentLink = $torrentLink;
        $this->fileId = $fileId;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(
        VirusTotalService $virusTotalService,
        QuarantineService $quarantineService,
        DownloadStatusService $statusService
    ): void {
        Log::info('Verifying torrent URL with VirusTotal', [
            'url' => $this->url,
            'file_id' => $this->fileId,
        ]);

        try {
            // Create or find ScannedUrl record
            $scannedUrl = ScannedUrl::where('url', $this->url)
                ->whereNull('deleted_at')
                ->first();

            if (!$scannedUrl) {
                $domain = ScannedUrl::extractDomain($this->url);
                $scannedUrl = ScannedUrl::create([
                    'url' => $this->url,
                    'domain' => $domain,
                    'virustotal_status' => 'pending',
                    'is_malicious' => false,
                ]);
            }

            // Update file status if we have a file record
            $file = $this->fileId ? File::find($this->fileId) : null;
            if ($file) {
                $statusService->updateStatus($file, DownloadStatus::VERIFYING_URL, 'Verifying URL with VirusTotal');
            }

            // If URL was previously scanned and marked as malicious, abort
            if ($scannedUrl->is_malicious) {
                Log::warning('URL was previously marked as malicious, aborting download', [
                    'url' => $this->url,
                    'scanned_url_id' => $scannedUrl->id,
                ]);

                if ($file) {
                    $statusService->updateStatus($file, DownloadStatus::URL_REJECTED, 'URL previously marked as malicious');
                }

                $this->handleMaliciousUrl($scannedUrl, $quarantineService);
                return;
            }

            // If URL was already scanned and is safe, proceed directly to download
            if ($scannedUrl->virustotal_status === 'completed' && !$scannedUrl->is_malicious) {
                Log::info('URL was previously verified as safe, proceeding to download', [
                    'url' => $this->url,
                    'scanned_url_id' => $scannedUrl->id,
                ]);

                if ($file) {
                    $statusService->updateStatus($file, DownloadStatus::URL_VERIFIED, 'URL verified as safe');
                }

                $this->proceedToDownload($statusService);
                return;
            }

            // Determine if we need to initiate a new scan or check existing one
            $scanId = null;
            $needsNewScan = false;

            if ($scannedUrl->virustotal_status === 'scanning' && $scannedUrl->virustotal_scan_id) {
                // Scan already in progress, use existing scan_id
                $scanId = $scannedUrl->virustotal_scan_id;
                Log::info('Using existing scan ID', [
                    'url' => $this->url,
                    'scan_id' => $scanId,
                    'scanned_url_id' => $scannedUrl->id,
                ]);
            } elseif ($scannedUrl->virustotal_status === 'pending' || !$scannedUrl->virustotal_scan_id) {
                // Need to initiate a new scan
                $needsNewScan = true;
                Log::info('Initiating new URL scan', [
                    'url' => $this->url,
                    'scanned_url_id' => $scannedUrl->id,
                ]);
            } else {
                // Unexpected status, treat as if we need a new scan
                $needsNewScan = true;
                Log::warning('Unexpected scan status, initiating new scan', [
                    'url' => $this->url,
                    'status' => $scannedUrl->virustotal_status,
                    'scanned_url_id' => $scannedUrl->id,
                ]);
            }

            // Initiate URL scan only if needed
            if ($needsNewScan) {
                $scanResult = $virusTotalService->scanUrl($this->url);
                $scanId = $scanResult['data']['id'] ?? null;
                
                if (!$scanId) {
                    throw new Exception('Failed to get scan ID from VirusTotal');
                }

                // Update scanned URL with scan ID
                $scannedUrl->update([
                    'virustotal_scan_id' => $scanId,
                    'virustotal_status' => 'scanning',
                ]);
            }

            // Check analysis status first, then get full report using URL ID
            try {
                // Get analysis status using the Analysis ID
                $analysisResult = $virusTotalService->getAnalysis($scanId);
                
                // Check if analysis is complete
                $analysisStatus = $analysisResult['data']['attributes']['status'] ?? null;
                
                if ($analysisStatus !== 'completed') {
                    // Analysis not ready yet, check if we should retry or timeout
                    $retryDelay = 30; // seconds
                    $maxRetryAge = now()->subMinutes(10); // Don't retry if scan is older than 10 minutes
                    $scanInitiatedAt = $scannedUrl->virustotal_status === 'scanning' && $scannedUrl->virustotal_scan_id
                        ? $scannedUrl->updated_at
                        : now();
                    
                    if ($scanInitiatedAt && $scanInitiatedAt->lt($maxRetryAge)) {
                        Log::warning('Analysis taking too long, aborting verification', [
                            'url' => $this->url,
                            'analysis_id' => $scanId,
                            'status' => $analysisStatus,
                            'scan_initiated_at' => $scanInitiatedAt,
                        ]);
                        
                        if ($file) {
                            $statusService->updateStatus($file, DownloadStatus::FAILED, 'URL verification timeout');
                        }
                        
                        $scannedUrl->update([
                            'virustotal_status' => 'timeout',
                        ]);
                        return;
                    }

                    // Refresh to get latest state before scheduling retry
                    $scannedUrl->fresh();
                    
                    // Only schedule retry if status is still scanning (prevents duplicate retries)
                    if ($scannedUrl->virustotal_status !== 'scanning') {
                        Log::info('Scan status changed, not scheduling retry', [
                            'url' => $this->url,
                            'current_status' => $scannedUrl->virustotal_status,
                        ]);
                        return;
                    }

                    Log::info('Analysis not ready yet, scheduling retry', [
                        'url' => $this->url,
                        'analysis_id' => $scanId,
                        'status' => $analysisStatus,
                        'scanned_url_id' => $scannedUrl->id,
                        'retry_delay' => $retryDelay,
                    ]);

                    // Schedule a single retry job with delay
                    VerifyTorrentUrlJob::dispatch(
                        $this->url,
                        $this->magnetLink,
                        $this->torrentLink,
                        $this->fileId,
                        $this->metadata
                    )->delay(now()->addSeconds($retryDelay));
                    return;
                }
                
                // Analysis is complete, get the URL ID (base64 encoded) to retrieve full report
                $urlId = $virusTotalService->encodeUrlId($this->url);
                
                // Get full URL report using the URL ID
                $reportResult = $virusTotalService->getUrlReport($urlId);
                
                // Check if the report has complete analysis results
                $hasCompleteResults = $this->hasCompleteAnalysisResults($reportResult);
                
                if (!$hasCompleteResults) {
                    // This shouldn't happen if analysis is completed, but handle it just in case
                    Log::warning('Analysis completed but report incomplete, retrying', [
                        'url' => $this->url,
                        'analysis_id' => $scanId,
                        'url_id' => $urlId,
                    ]);
                    
                    // Retry after a delay
                    VerifyTorrentUrlJob::dispatch(
                        $this->url,
                        $this->magnetLink,
                        $this->torrentLink,
                        $this->fileId,
                        $this->metadata
                    )->delay(now()->addSeconds(30));
                    return;
                }

                // Report is ready, process it
                $isMalicious = $this->isUrlMalicious($reportResult);

                // Update scanned URL with results
                $scannedUrl->update([
                    'virustotal_status' => 'completed',
                    'virustotal_results' => $reportResult,
                    'virustotal_scanned_at' => now(),
                    'is_malicious' => $isMalicious,
                ]);

                if ($isMalicious) {
                    Log::warning('URL flagged as malicious by VirusTotal', [
                        'url' => $this->url,
                        'scanned_url_id' => $scannedUrl->id,
                    ]);

                    if ($file) {
                        $statusService->updateStatus($file, DownloadStatus::URL_REJECTED, 'URL flagged as malicious by VirusTotal');
                    }

                    $quarantineService->handleMaliciousUrl($scannedUrl, $reportResult);
                    return;
                }

                // URL is safe, proceed to download
                Log::info('URL verified as safe, proceeding to download', [
                    'url' => $this->url,
                    'scanned_url_id' => $scannedUrl->id,
                ]);

                if ($file) {
                    $statusService->updateStatus($file, DownloadStatus::URL_VERIFIED, 'URL verified as safe');
                }

                $this->proceedToDownload($statusService);
            } catch (Exception $e) {
                // Error getting analysis or report - could be API issue or analysis not ready
                Log::warning('Error getting analysis or URL report, may need to retry', [
                    'url' => $this->url,
                    'analysis_id' => $scanId,
                    'error' => $e->getMessage(),
                ]);

                // Check if scan was just initiated (should allow some time)
                $retryDelay = 30;
                $maxRetryAge = now()->subMinutes(10);
                $scanInitiatedAt = $scannedUrl->virustotal_status === 'scanning' && $scannedUrl->virustotal_scan_id
                    ? $scannedUrl->updated_at
                    : now();
                
                if ($scanInitiatedAt && $scanInitiatedAt->lt($maxRetryAge)) {
                    Log::error('Failed to get analysis or URL report after multiple attempts', [
                        'url' => $this->url,
                        'analysis_id' => $scanId,
                        'scan_initiated_at' => $scanInitiatedAt,
                    ]);
                    
                    if ($file) {
                        $statusService->updateStatus($file, DownloadStatus::FAILED, 'Failed to verify URL');
                    }
                    
                    $scannedUrl->update([
                        'virustotal_status' => 'error',
                    ]);
                    
                    throw $e;
                }

                // Only schedule retry if status is still scanning (don't retry if it changed)
                $scannedUrl->fresh();
                if ($scannedUrl->virustotal_status === 'scanning') {
                    // Schedule a retry only if scan is still recent
                    VerifyTorrentUrlJob::dispatch(
                        $this->url,
                        $this->magnetLink,
                        $this->torrentLink,
                        $this->fileId,
                        $this->metadata
                    )->delay(now()->addSeconds($retryDelay));
                } else {
                    // Status changed (maybe completed by another job), don't retry
                    Log::info('Scan status changed, not scheduling retry', [
                        'url' => $this->url,
                        'current_status' => $scannedUrl->virustotal_status,
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to verify torrent URL', [
                'url' => $this->url,
                'error' => $e->getMessage(),
            ]);

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

        // If we have reputation or other analysis data, consider it complete
        return isset($attributes['reputation']) || 
               isset($attributes['last_analysis_date']) ||
               isset($attributes['last_modification_date']);
    }

    /**
     * Check if URL is malicious based on VirusTotal response
     */
    private function isUrlMalicious(array $virusTotalResponse): bool
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

    /**
     * Handle malicious URL
     */
    private function handleMaliciousUrl(ScannedUrl $scannedUrl, QuarantineService $quarantineService): void
    {
        // If we have a file record, mark it as failed
        if ($this->fileId) {
            $file = File::find($this->fileId);
            if ($file) {
                $file->update([
                    'virustotal_status' => 'error',
                ]);
            }
        }

        // QuarantineService will handle logging and marking the URL
        if ($scannedUrl->virustotal_results) {
            $quarantineService->handleMaliciousUrl($scannedUrl, $scannedUrl->virustotal_results);
        }
    }

    /**
     * Proceed to download step
     */
    private function proceedToDownload(DownloadStatusService $statusService): void
    {
        // Update status before dispatching download job
        if ($this->fileId) {
            $file = File::find($this->fileId);
            if ($file) {
                $statusService->updateStatus($file, DownloadStatus::DOWNLOADING, 'Starting torrent download');
            }
        }

        DownloadTorrentJob::dispatch(
            $this->magnetLink,
            $this->torrentLink,
            $this->fileId,
            $this->metadata
        );

        Log::info('DownloadTorrentJob dispatched', [
            'file_id' => $this->fileId,
            'has_magnet' => !empty($this->magnetLink),
            'has_torrent_link' => !empty($this->torrentLink),
        ]);
    }
}

