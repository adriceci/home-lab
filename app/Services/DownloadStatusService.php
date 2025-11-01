<?php

namespace App\Services;

use App\Enums\DownloadStatus;
use App\Exceptions\VirusTotalException;
use App\Models\File;
use Illuminate\Support\Facades\Log;
use Exception;

class DownloadStatusService
{
    /**
     * Update download status for a file
     * Also updates virustotal_status when the download status is related to VirusTotal operations
     */
    public function updateStatus(File $file, DownloadStatus $status, ?string $message = null, ?VirusTotalException $virusTotalException = null): void
    {
        try {
            $oldStatus = $file->download_status;

            // Determine if we should also update virustotal_status
            $virustotalStatus = $this->getVirusTotalStatusFromDownloadStatus($status, $virusTotalException);

            $updateData = [
                'download_status' => $status->value,
            ];

            // Only update virustotal_status if it's related to VirusTotal operations
            // and the status is different from the current one (avoid unnecessary updates)
            if ($virustotalStatus !== null && $file->virustotal_status !== $virustotalStatus) {
                $updateData['virustotal_status'] = $virustotalStatus;
            }

            $file->update($updateData);

            Log::info('Download status updated', [
                'file_id' => $file->id,
                'file_name' => $file->name,
                'old_status' => $oldStatus,
                'new_status' => $status->value,
                'virustotal_status' => $virustotalStatus,
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
     * Get corresponding VirusTotal status from DownloadStatus
     * Returns null if the status is not related to VirusTotal
     * 
     * @param DownloadStatus $downloadStatus
     * @param VirusTotalException|null $exception
     * @return string|null
     */
    private function getVirusTotalStatusFromDownloadStatus(DownloadStatus $downloadStatus, ?VirusTotalException $exception = null): ?string
    {
        // If we have a VirusTotal exception, use its status
        if ($exception !== null) {
            $virusTotalService = app(VirusTotalService::class);
            return $virusTotalService->getStatusFromError($exception);
        }

        // Map DownloadStatus to VirusTotal status for VirusTotal-related operations
        return match ($downloadStatus) {
            DownloadStatus::VERIFYING_URL => 'pending', // URL verification starts as pending
            DownloadStatus::SCANNING_FILE => 'scanning', // File scanning is in progress
            DownloadStatus::URL_VERIFIED => 'completed', // URL was verified successfully
            DownloadStatus::FILE_VERIFIED => 'completed', // File was verified successfully
            DownloadStatus::URL_REJECTED => 'completed', // URL scan completed (but malicious)
            DownloadStatus::FILE_REJECTED => 'completed', // File scan completed (but malicious)
            DownloadStatus::FAILED => 'error', // Generic error if no exception provided
            default => null, // Other statuses are not directly related to VirusTotal
        };
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
