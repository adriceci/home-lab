<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Models\File;
use App\Services\DownloadStatusService;
use App\Services\QuarantineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class MoveFileToStorageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60; // Wait 1 minute between retries

    private string $fileId;
    private string $destinationDisk;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fileId, string $destinationDisk = 'local')
    {
        $this->fileId = $fileId;
        $this->destinationDisk = $destinationDisk;
    }

    /**
     * Execute the job.
     */
    public function handle(
        QuarantineService $quarantineService,
        DownloadStatusService $statusService
    ): void {
        Log::info('Moving file from quarantine to storage', [
            'file_id' => $this->fileId,
            'destination_disk' => $this->destinationDisk,
        ]);

        try {
            $file = File::find($this->fileId);

            if (!$file) {
                throw new Exception("File not found: {$this->fileId}");
            }

            if ($file->storage_disk !== 'quarantine') {
                Log::warning('File is not in quarantine, skipping move', [
                    'file_id' => $file->id,
                    'current_disk' => $file->storage_disk,
                ]);
                return;
            }

            // Update status to moving to storage
            $statusService->updateStatus($file, DownloadStatus::MOVING_TO_STORAGE, 'Moving file to storage');

            // Move file from quarantine to storage
            $quarantineService->moveToStorage($file, $this->destinationDisk);

            // Update status to completed
            $statusService->updateStatus($file, DownloadStatus::COMPLETED, 'Download and verification completed successfully');

            Log::info('File moved to storage successfully', [
                'file_id' => $file->id,
                'file_name' => $file->name,
                'destination_disk' => $this->destinationDisk,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to move file to storage', [
                'file_id' => $this->fileId,
                'error' => $e->getMessage(),
            ]);

            // Update file status to failed
            $file = File::find($this->fileId);
            if ($file) {
                $statusService->markAsFailed($file, $e->getMessage());
            }

            throw $e;
        }
    }
}

