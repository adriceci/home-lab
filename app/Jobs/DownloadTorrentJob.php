<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Jobs\ScanDownloadedFileJob;
use App\Models\File;
use App\Services\DownloadStatusService;
use App\Services\TorrentDownloadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class DownloadTorrentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 120; // Wait 2 minutes between retries for downloads

    private ?string $magnetLink;
    private ?string $torrentLink;
    private ?string $fileId;
    private array $metadata;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ?string $magnetLink = null,
        ?string $torrentLink = null,
        ?string $fileId = null,
        array $metadata = []
    ) {
        $this->magnetLink = $magnetLink;
        $this->torrentLink = $torrentLink;
        $this->fileId = $fileId;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(
        TorrentDownloadService $torrentDownloadService,
        DownloadStatusService $statusService
    ): void {
        Log::info('Starting torrent download', [
            'file_id' => $this->fileId,
            'has_magnet' => !empty($this->magnetLink),
            'has_torrent_link' => !empty($this->torrentLink),
        ]);

        try {
            // Get or create file record
            $file = $this->fileId ? File::find($this->fileId) : null;

            // Update status to downloading if we have a file record
            if ($file) {
                $statusService->updateStatus($file, DownloadStatus::DOWNLOADING, 'Downloading torrent file');
            }

            // Generate destination path in quarantine
            $fileName = $this->metadata['title'] ?? 'torrent_' . uniqid();
            // Sanitize filename
            $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $fileName = mb_substr($fileName, 0, 200); // Limit length
            
            // Add appropriate extension
            if (!empty($this->torrentLink)) {
                // If we have a torrent link, it's likely a .torrent file
                $extension = pathinfo(parse_url($this->torrentLink, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (empty($extension)) {
                    $extension = 'torrent';
                }
            } else {
                // Magnet link, we'll determine extension after download
                $extension = 'torrent';
            }
            
            $destinationPath = 'torrents/' . date('Y/m/d') . '/' . $fileName . '.' . $extension;

            // Download torrent using placeholder method
            // This will throw an exception until the torrent manager is configured
            try {
                $downloadedPath = $torrentDownloadService->downloadTorrentFile(
                    $this->magnetLink,
                    $this->torrentLink,
                    $destinationPath
                );

                // Ensure the file exists
                if (!Storage::disk('quarantine')->exists($downloadedPath)) {
                    throw new Exception("Downloaded file not found at path: {$downloadedPath}");
                }

                // Get file size
                $fileSize = Storage::disk('quarantine')->size($downloadedPath);

                // Get file info
                $mimeType = Storage::disk('quarantine')->mimeType($downloadedPath);
                $extension = pathinfo($downloadedPath, PATHINFO_EXTENSION);

                // Create or update file record
                if (!$file) {
                    $file = File::create([
                        'name' => $this->metadata['title'] ?? basename($downloadedPath),
                        'path' => $downloadedPath,
                        'size' => $fileSize,
                        'type' => 'torrent',
                        'mime_type' => $mimeType,
                        'extension' => $extension,
                        'storage_disk' => 'quarantine',
                        'quarantined_at' => now(),
                        'virustotal_status' => 'pending',
                    ]);
                } else {
                    $file->update([
                        'name' => $this->metadata['title'] ?? basename($downloadedPath),
                        'path' => $downloadedPath,
                        'size' => $fileSize,
                        'mime_type' => $mimeType,
                        'extension' => $extension,
                        'quarantined_at' => now(),
                        'virustotal_status' => 'pending',
                    ]);
                }

                Log::info('Torrent downloaded successfully', [
                    'file_id' => $file->id,
                    'path' => $downloadedPath,
                    'size' => $fileSize,
                ]);

                // Update status to download completed
                $statusService->updateStatus($file, DownloadStatus::DOWNLOAD_COMPLETED, 'Torrent download completed');

                // Dispatch file scanning job
                ScanDownloadedFileJob::dispatch($file->id);

            } catch (Exception $e) {
                // If it's the placeholder exception, log it clearly
                if (str_contains($e->getMessage(), 'not yet configured')) {
                    Log::warning('Torrent download manager not configured', [
                        'error' => $e->getMessage(),
                        'file_id' => $this->fileId,
                    ]);

                    // Update file status if we have a record
                    if ($file) {
                        $statusService->markAsFailed($file, 'Torrent download manager not configured');
                    }

                    // Don't retry if it's a configuration issue
                    $this->fail($e);
                    return;
                }

                // For other errors, allow retries
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to download torrent', [
                'file_id' => $this->fileId,
                'error' => $e->getMessage(),
            ]);

            // Update file status if we have a record
            if ($file ?? null) {
                $statusService->markAsFailed($file, $e->getMessage());
            }

            throw $e;
        }
    }
}

