<?php

namespace App\Services;

use App\Enums\DownloadStatus;
use App\Models\File;
use Illuminate\Support\Facades\Log;
use Exception;

class DownloadStatusService
{
    /**
     * Update download status for a file
     */
    public function updateStatus(File $file, DownloadStatus $status, ?string $message = null): void
    {
        try {
            $oldStatus = $file->download_status;
            
            $file->update([
                'download_status' => $status->value,
            ]);

            Log::info('Download status updated', [
                'file_id' => $file->id,
                'file_name' => $file->name,
                'old_status' => $oldStatus,
                'new_status' => $status->value,
                'message' => $message,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update download status', [
                'file_id' => $file->id,
                'status' => $status->value,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Update status by file ID
     */
    public function updateStatusById(string $fileId, DownloadStatus $status, ?string $message = null): void
    {
        $file = File::find($fileId);
        
        if (!$file) {
            throw new Exception("File not found: {$fileId}");
        }

        $this->updateStatus($file, $status, $message);
    }

    /**
     * Get status information for a file
     */
    public function getStatusInfo(File $file): array
    {
        $status = $file->download_status 
            ? DownloadStatus::tryFrom($file->download_status) 
            : DownloadStatus::PENDING;

        return [
            'status' => $status->value,
            'label' => $status->label(),
            'color_class' => $status->colorClass(),
            'progress' => $status->progress(),
            'is_terminal' => $status->isTerminal(),
            'is_error' => $status->isError(),
            'file_id' => $file->id,
            'file_name' => $file->name,
        ];
    }

    /**
     * Get status information by file ID
     */
    public function getStatusInfoById(string $fileId): ?array
    {
        $file = File::find($fileId);
        
        if (!$file) {
            return null;
        }

        return $this->getStatusInfo($file);
    }

    /**
     * Check if status transition is valid
     */
    public function isValidTransition(DownloadStatus $from, DownloadStatus $to): bool
    {
        // Terminal states can't transition (except to failed/cancelled for error handling)
        if ($from->isTerminal() && !in_array($to, [DownloadStatus::FAILED, DownloadStatus::CANCELLED])) {
            return false;
        }

        // Define valid transitions (for now, allow most transitions except terminal -> other states)
        // In production, you might want to be more strict
        return true;
    }

    /**
     * Mark download as failed with optional error message
     */
    public function markAsFailed(File $file, ?string $errorMessage = null): void
    {
        $this->updateStatus($file, DownloadStatus::FAILED, $errorMessage);
    }

    /**
     * Mark download as failed by file ID
     */
    public function markAsFailedById(string $fileId, ?string $errorMessage = null): void
    {
        $file = File::find($fileId);
        
        if (!$file) {
            throw new Exception("File not found: {$fileId}");
        }

        $this->markAsFailed($file, $errorMessage);
    }
}

