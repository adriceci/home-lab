<?php

namespace App\Services;

use App\Enums\DownloadStatus;
use App\Jobs\DownloadTorrentJob;
use App\Jobs\VerifyTorrentUrlJob;
use App\Models\File;
use App\Services\DownloadStatusService;
use Illuminate\Support\Facades\Log;
use Exception;

class TorrentDownloadService
{
    public function __construct(
        private DownloadStatusService $statusService
    ) {
    }
    /**
     * Initiate download process for a torrent
     * 
     * @param string|null $magnetLink Magnet link (magnet:...)
     * @param string|null $torrentLink Direct torrent file URL
     * @param string|null $sourceUrl Source page URL (for verification if no torrent_link)
     * @param array $metadata Additional metadata (title, size, etc.)
     * @return array
     */
    public function initiateDownload(
        ?string $magnetLink = null,
        ?string $torrentLink = null,
        ?string $sourceUrl = null,
        array $metadata = []
    ): array {
        // Validate that at least one link is provided
        if (empty($magnetLink) && empty($torrentLink)) {
            throw new Exception('Either magnet_link or torrent_link must be provided');
        }

        // Determine which URL to verify
        // Priority: torrent_link > source_url (if only magnet_link available)
        $urlToVerify = $torrentLink;
        if (empty($urlToVerify) && !empty($sourceUrl)) {
            $urlToVerify = $sourceUrl;
        }

        // If no URL to verify (magnet only without source_url), we can still proceed
        // but skip URL verification step
        if (empty($urlToVerify)) {
            Log::warning('No URL to verify for torrent download', [
                'magnet_link' => $magnetLink,
                'has_torrent_link' => !empty($torrentLink),
            ]);
        }

        // Create initial file record in quarantine state if needed
        // This will be updated as the process progresses
        $file = null;
        if (!empty($metadata['title'])) {
            try {
                $file = File::create([
                    'name' => $metadata['title'] ?? 'unknown_torrent',
                    'path' => '', // Will be set after download
                    'size' => 0, // Will be set after download
                    'type' => 'torrent',
                    'storage_disk' => 'quarantine',
                    'quarantined_at' => now(),
                    'virustotal_status' => 'pending',
                    'download_status' => DownloadStatus::PENDING->value,
                ]);

                // Set initial status
                $this->statusService->updateStatus($file, DownloadStatus::PENDING, 'Download process initiated');
            } catch (Exception $e) {
                Log::error('Failed to create file record for torrent download', [
                    'error' => $e->getMessage(),
                    'metadata' => $metadata,
                ]);
            }
        }

        // Dispatch URL verification job if we have a URL to verify
        if (!empty($urlToVerify)) {
            // Update status to verifying URL
            if ($file) {
                $this->statusService->updateStatus($file, DownloadStatus::VERIFYING_URL, 'Verifying download URL');
            }

            VerifyTorrentUrlJob::dispatch(
                $urlToVerify,
                $magnetLink,
                $torrentLink,
                $file?->id,
                $metadata
            );
        } else {
            // If no URL to verify (e.g., magnet link only without source_url),
            // proceed directly to download step
            // Note: This is less secure but allows magnet-only downloads
            Log::info('Skipping URL verification, proceeding directly to download', [
                'magnet_link' => $magnetLink,
                'has_torrent_link' => !empty($torrentLink),
            ]);

            // Update status to downloading (skipping URL verification)
            if ($file) {
                $this->statusService->updateStatus($file, DownloadStatus::DOWNLOADING, 'Starting download (URL verification skipped)');
            }

            DownloadTorrentJob::dispatch(
                $magnetLink,
                $torrentLink,
                $file?->id,
                $metadata
            );
        }

        return [
            'success' => true,
            'message' => 'Download process initiated',
            'file_id' => $file?->id,
            'url_to_verify' => $urlToVerify,
        ];
    }

    /**
     * Download torrent file using torrent manager (placeholder)
     * 
     * This method should be implemented once the torrent manager is configured.
     * It accepts either a magnet link or torrent file URL and downloads
     * the content to the quarantine directory.
     * 
     * @param string|null $magnetLink Magnet link
     * @param string|null $torrentLink Torrent file URL
     * @param string $destinationPath Path in quarantine disk where file should be stored
     * @return string Path to downloaded file in quarantine
     * @throws Exception
     */
    public function downloadTorrentFile(
        ?string $magnetLink = null,
        ?string $torrentLink = null,
        string $destinationPath = ''
    ): string {
        // Placeholder implementation
        throw new Exception(
            'Torrent download manager is not yet configured. ' .
            'Please implement the torrent manager integration in TorrentDownloadService::downloadTorrentFile().'
        );

        // Expected implementation:
        // 1. If torrent_link is provided, download the .torrent file from URL
        // 2. If only magnet_link is provided, use torrent client to download from magnet
        // 3. Save the downloaded file(s) to the quarantine disk at $destinationPath
        // 4. Return the path to the downloaded file in quarantine
        // 
        // Example structure:
        // if (!empty($torrentLink)) {
        //     // Download .torrent file
        //     $content = Http::get($torrentLink)->body();
        //     Storage::disk('quarantine')->put($destinationPath, $content);
        //     return $destinationPath;
        // } elseif (!empty($magnetLink)) {
        //     // Use torrent client to download from magnet
        //     // ... torrent client integration ...
        //     return $destinationPath;
        // }
    }
}

