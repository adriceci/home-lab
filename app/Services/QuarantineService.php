<?php

namespace App\Services;

use App\Models\File;
use App\Models\ScannedUrl;
use AdriCeci\AuditCenter\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class QuarantineService
{
    /**
     * Move file from quarantine to normal storage
     */
    public function moveToStorage(File $file, string $destinationDisk = 'local'): bool
    {
        if ($file->storage_disk !== 'quarantine') {
            throw new Exception("File is not in quarantine. Current disk: {$file->storage_disk}");
        }

        if (!Storage::disk('quarantine')->exists($file->path)) {
            throw new Exception("File not found in quarantine: {$file->path}");
        }

        try {
            // Determine destination path (maintain same path structure)
            $destinationPath = $file->path;

            // Laravel Storage move doesn't work across disks
            // We need to copy and then delete
            $content = Storage::disk('quarantine')->get($file->path);
            Storage::disk($destinationDisk)->put($destinationPath, $content);
            Storage::disk('quarantine')->delete($file->path);

            // Update file record
            $file->update([
                'storage_disk' => $destinationDisk,
                'quarantined_at' => null,
                'virustotal_scanned_at' => now(),
            ]);

            Log::info("File moved from quarantine to {$destinationDisk}", [
                'file_id' => $file->id,
                'file_name' => $file->name,
                'path' => $destinationPath,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error("Failed to move file from quarantine", [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle malicious file: delete and log to audit
     */
    public function handleMaliciousFile(File $file, array $virusTotalResponse): void
    {
        if ($file->storage_disk !== 'quarantine') {
            throw new Exception("File is not in quarantine. Current disk: {$file->storage_disk}");
        }

        try {
            // Get file information before deletion
            $fileInfo = [
                'file_id' => $file->id,
                'name' => $file->name,
                'size' => $file->size,
                'extension' => $file->extension,
                'mime_type' => $file->mime_type,
                'type' => $file->type,
                'virustotal_scan_id' => $file->virustotal_scan_id,
                'path' => $file->path,
            ];

            // Extract threat information from VirusTotal response
            $threatInfo = $this->extractThreatInfo($virusTotalResponse);

            // Delete physical file
            if (Storage::disk('quarantine')->exists($file->path)) {
                Storage::disk('quarantine')->delete($file->path);
            }

            // Update file status before logging
            $file->update([
                'virustotal_status' => 'error',
                'virustotal_results' => $virusTotalResponse,
            ]);

            // Create audit log entry
            AuditLog::log(
                action: 'file_rejected_by_virustotal',
                description: "File '{$file->name}' was rejected by VirusTotal. " . ($threatInfo['reason'] ?? 'Threats detected'),
                userId: auth()->id(),
                modelType: File::class,
                modelId: $file->id,
                newValues: array_merge($fileInfo, [
                    'virustotal_response' => $virusTotalResponse,
                    'threats_detected' => $threatInfo['threats'] ?? [],
                    'rejection_reason' => $threatInfo['reason'] ?? 'Malicious file detected',
                ]),
            );

            // Delete file record (soft delete)
            $file->delete();

            Log::warning("Malicious file deleted and logged", [
                'file_id' => $file->id,
                'file_name' => $file->name,
                'threats' => $threatInfo['threats'] ?? [],
            ]);
        } catch (Exception $e) {
            Log::error("Failed to handle malicious file", [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle malicious URL: mark and log to audit
     */
    public function handleMaliciousUrl(ScannedUrl $scannedUrl, array $virusTotalResponse): void
    {
        try {
            // Get URL information before updating
            $urlInfo = [
                'scanned_url_id' => $scannedUrl->id,
                'url' => $scannedUrl->url,
                'domain' => $scannedUrl->domain,
                'virustotal_scan_id' => $scannedUrl->virustotal_scan_id,
            ];

            // Extract threat information from VirusTotal response
            $threatInfo = $this->extractThreatInfo($virusTotalResponse);

            // Update scanned URL status and mark as malicious
            $scannedUrl->update([
                'virustotal_status' => 'completed',
                'virustotal_results' => $virusTotalResponse,
                'virustotal_scanned_at' => now(),
                'is_malicious' => true,
                'blocked_at' => now(),
            ]);

            // Create audit log entry
            AuditLog::log(
                action: 'url_rejected_by_virustotal',
                description: "URL '{$scannedUrl->url}' was flagged as malicious by VirusTotal. " . ($threatInfo['reason'] ?? 'Threats detected'),
                userId: auth()->id(),
                modelType: ScannedUrl::class,
                modelId: $scannedUrl->id,
                newValues: array_merge($urlInfo, [
                    'virustotal_response' => $virusTotalResponse,
                    'threats_detected' => $threatInfo['threats'] ?? [],
                    'rejection_reason' => $threatInfo['reason'] ?? 'Malicious URL detected',
                    'domain' => $scannedUrl->domain,
                    'blocked_at' => now()->toIso8601String(),
                ]),
            );

            Log::warning("Malicious URL marked and logged", [
                'scanned_url_id' => $scannedUrl->id,
                'url' => $scannedUrl->url,
                'domain' => $scannedUrl->domain,
                'threats' => $threatInfo['threats'] ?? [],
            ]);
        } catch (Exception $e) {
            Log::error("Failed to handle malicious URL", [
                'scanned_url_id' => $scannedUrl->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract threat information from VirusTotal response
     */
    private function extractThreatInfo(array $virusTotalResponse): array
    {
        $threats = [];
        $reason = 'Malicious file detected';

        // Check data attributes for threats
        if (isset($virusTotalResponse['data']['attributes']['last_analysis_stats'])) {
            $stats = $virusTotalResponse['data']['attributes']['last_analysis_stats'];

            if (isset($stats['malicious']) && $stats['malicious'] > 0) {
                $reason = "Detected as malicious by {$stats['malicious']} security vendors";
            }

            if (isset($stats['suspicious']) && $stats['suspicious'] > 0) {
                $reason = "Detected as suspicious by {$stats['suspicious']} security vendors";
            }
        }

        // Check for specific threat names
        if (isset($virusTotalResponse['data']['attributes']['popular_threat_classification'])) {
            $threats = $virusTotalResponse['data']['attributes']['popular_threat_classification'];
        }

        // Check for analysis results
        if (isset($virusTotalResponse['data']['attributes']['last_analysis_results'])) {
            $results = $virusTotalResponse['data']['attributes']['last_analysis_results'];
            foreach ($results as $engine => $result) {
                if (isset($result['category']) && in_array($result['category'], ['malicious', 'suspicious'])) {
                    $threats[] = [
                        'engine' => $engine,
                        'category' => $result['category'],
                        'result' => $result['result'] ?? null,
                    ];
                }
            }
        }

        return [
            'threats' => $threats,
            'reason' => $reason,
        ];
    }

    /**
     * Cleanup old files that haven't been verified after X days
     */
    public function cleanupOldFiles(int $days = 10): int
    {
        $cutoffDate = now()->subDays($days);

        // Find files in quarantine that haven't been scanned after cutoff date
        $oldFiles = File::where('storage_disk', 'quarantine')
            ->where('quarantined_at', '<=', $cutoffDate)
            ->where(function ($query) {
                $query->whereNull('virustotal_status')
                    ->orWhere('virustotal_status', 'pending')
                    ->orWhere('virustotal_status', 'scanning');
            })
            ->get();

        $deletedCount = 0;

        foreach ($oldFiles as $file) {
            try {
                // Delete physical file
                if (Storage::disk('quarantine')->exists($file->path)) {
                    Storage::disk('quarantine')->delete($file->path);
                }

                // Log deletion
                AuditLog::log(
                    action: 'file_deleted_quarantine_cleanup',
                    description: "File '{$file->name}' was deleted during quarantine cleanup after {$days} days without verification",
                    userId: null,
                    modelType: File::class,
                    modelId: $file->id,
                    newValues: [
                        'file_id' => $file->id,
                        'name' => $file->name,
                        'size' => $file->size,
                        'extension' => $file->extension,
                        'quarantined_at' => $file->quarantined_at?->toIso8601String(),
                        'virustotal_status' => $file->virustotal_status,
                        'cleanup_reason' => "Not verified after {$days} days",
                    ],
                );

                // Delete file record
                $file->delete();
                $deletedCount++;

                Log::info("Deleted old quarantine file during cleanup", [
                    'file_id' => $file->id,
                    'file_name' => $file->name,
                    'quarantined_at' => $file->quarantined_at?->toIso8601String(),
                ]);
            } catch (Exception $e) {
                Log::error("Failed to delete old quarantine file", [
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Quarantine cleanup completed", [
            'deleted_count' => $deletedCount,
            'days_threshold' => $days,
        ]);

        return $deletedCount;
    }
}
