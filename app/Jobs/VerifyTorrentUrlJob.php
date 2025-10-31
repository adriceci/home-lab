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

            // Initiate URL scan
            $scanResult = $virusTotalService->scanUrl($this->url);

            // Update scanned URL with scan ID
            $scanId = $scanResult['data']['id'] ?? null;
            $scannedUrl->update([
                'virustotal_scan_id' => $scanId,
                'virustotal_status' => 'scanning',
            ]);

            // Get URL report (may need to wait if scan is still processing)
            // Note: VirusTotal scans may take time, so we might need to poll
            // For now, we'll try to get the report immediately
            // If it's still processing, we can implement polling in a separate job
            try {
                $reportResult = $virusTotalService->getUrlReport($scanId);
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
                // Report might not be ready yet, need to poll
                // For now, we'll dispatch a delayed job to check again
                // Or we can proceed if scan was just initiated (status might be 'queued')
                Log::warning('Could not get URL report immediately, scan may still be processing', [
                    'url' => $this->url,
                    'scan_id' => $scanId,
                    'error' => $e->getMessage(),
                ]);

                // Dispatch a delayed job to check the report again in 30 seconds
                VerifyTorrentUrlJob::dispatch(
                    $this->url,
                    $this->magnetLink,
                    $this->torrentLink,
                    $this->fileId,
                    $this->metadata
                )->delay(now()->addSeconds(30));
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

