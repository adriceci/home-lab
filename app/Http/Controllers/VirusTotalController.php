<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ScannedUrl;
use App\Services\VirusTotalService;
use App\Services\QuarantineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class VirusTotalController extends Controller
{
    private VirusTotalService $virusTotalService;
    private QuarantineService $quarantineService;

    public function __construct(VirusTotalService $virusTotalService, QuarantineService $quarantineService)
    {
        $this->virusTotalService = $virusTotalService;
        $this->quarantineService = $quarantineService;
    }

    /**
     * Scan a URL with VirusTotal
     */
    public function scanUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:2048'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $url = $request->url;
            $domain = ScannedUrl::extractDomain($url);

            // Check if URL already exists (not deleted)
            $scannedUrl = ScannedUrl::where('url', $url)
                ->whereNull('deleted_at')
                ->first();

            // Create or update scanned URL record
            if (!$scannedUrl) {
                $scannedUrl = ScannedUrl::create([
                    'url' => $url,
                    'domain' => $domain,
                    'virustotal_scan_id' => null,
                    'virustotal_status' => 'pending',
                    'virustotal_results' => null,
                    'is_malicious' => false,
                ]);
            }

            // Scan URL with VirusTotal
            $result = $this->virusTotalService->scanUrl($url);

            // Update scanned URL record with scan ID
            $scannedUrl->update([
                'virustotal_scan_id' => $result['data']['id'] ?? null,
                'virustotal_status' => 'scanning',
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'scanned_url_id' => $scannedUrl->id,
                'message' => 'URL scan initiated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to scan URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get URL analysis report
     */
    public function getUrlReport(string $id): JsonResponse
    {
        try {
            $result = $this->virusTotalService->getUrlReport($id);

            // Update scanned URL record if it exists
            $scannedUrl = ScannedUrl::where('virustotal_scan_id', $id)->first();
            if ($scannedUrl) {
                // Check if URL is malicious
                $isMalicious = $this->isUrlMalicious($result);
                
                if ($isMalicious) {
                    // Handle malicious URL: mark and log
                    $this->quarantineService->handleMaliciousUrl($scannedUrl, $result);
                    
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                        'message' => 'URL report retrieved successfully. URL was flagged as malicious.',
                        'malicious' => true
                    ]);
                } else {
                    // URL is safe
                    $scannedUrl->update([
                        'virustotal_status' => 'completed',
                        'virustotal_results' => $result,
                        'virustotal_scanned_at' => now(),
                        'is_malicious' => false,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'URL report retrieved successfully',
                'malicious' => false
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get URL report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan a file with VirusTotal (for files < 32MB)
     */
    public function scanFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:32768' // 32MB in KB
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $file = $request->file('file');
            
            // Store file in quarantine first
            $storagePath = $file->storeAs('uploads', $file->getClientOriginalName(), 'quarantine');
            
            // Create file record in quarantine
            $fileRecord = File::create([
                'name' => $file->getClientOriginalName(),
                'path' => $storagePath,
                'size' => $file->getSize(),
                'type' => 'scan',
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'storage_disk' => 'quarantine',
                'quarantined_at' => now(),
                'virustotal_scan_id' => null,
                'virustotal_status' => 'pending',
                'virustotal_results' => null
            ]);

            // Scan file from quarantine using Storage path
            $result = $this->virusTotalService->scanFile($storagePath, 'quarantine');

            // Update file record with scan ID
            $fileRecord->update([
                'virustotal_scan_id' => $result['data']['id'] ?? null,
                'virustotal_status' => 'scanning',
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'file_id' => $fileRecord->id,
                'message' => 'File scan initiated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to scan file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get large file upload URL for files > 32MB
     */
    public function getLargeFileUploadUrl(): JsonResponse
    {
        try {
            $result = $this->virusTotalService->getLargeFileUploadUrl();

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Large file upload URL retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get large file upload URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload large file to VirusTotal
     */
    public function uploadLargeFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'upload_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $file = $request->file('file');
            $uploadUrl = $request->upload_url;
            
            // Store file in quarantine first
            $storagePath = $file->storeAs('uploads', $file->getClientOriginalName(), 'quarantine');
            
            // Create file record in quarantine
            $fileRecord = File::create([
                'name' => $file->getClientOriginalName(),
                'path' => $storagePath,
                'size' => $file->getSize(),
                'type' => 'scan',
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'storage_disk' => 'quarantine',
                'quarantined_at' => now(),
                'virustotal_scan_id' => null,
                'virustotal_status' => 'pending',
                'virustotal_results' => null
            ]);

            // Upload file from quarantine using Storage path
            $result = $this->virusTotalService->uploadLargeFile($storagePath, $uploadUrl, 'quarantine');

            // Update file record with scan ID
            $fileRecord->update([
                'virustotal_scan_id' => $result['data']['id'] ?? null,
                'virustotal_status' => 'scanning',
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'file_id' => $fileRecord->id,
                'message' => 'Large file uploaded successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload large file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file analysis report
     */
    public function getFileReport(string $id): JsonResponse
    {
        try {
            $result = $this->virusTotalService->getFileReport($id);

            // Update file record if it exists
            $fileRecord = File::where('virustotal_scan_id', $id)->first();
            if ($fileRecord) {
                // Check if file is malicious
                $isMalicious = $this->isFileMalicious($result);
                
                if ($isMalicious) {
                    // Handle malicious file: delete and log
                    $this->quarantineService->handleMaliciousFile($fileRecord, $result);
                    
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                        'message' => 'File report retrieved successfully. File was rejected due to security threats.',
                        'rejected' => true
                    ]);
                } else {
                    // File is clean, move from quarantine to normal storage
                    if ($fileRecord->storage_disk === 'quarantine') {
                        $this->quarantineService->moveToStorage($fileRecord, 'local');
                    }
                    
                    $fileRecord->update([
                        'virustotal_status' => 'completed',
                        'virustotal_results' => $result,
                        'virustotal_scanned_at' => now(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'File report retrieved successfully',
                'rejected' => false
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file report: ' . $e->getMessage()
            ], 500);
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
            
            // If any security vendor flagged it as malicious or suspicious, reject it
            if (isset($stats['malicious']) && $stats['malicious'] > 0) {
                return true;
            }
            
            // Optional: also reject if suspicious (you may want to adjust this policy)
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
            
            // Optional: also reject if suspicious (you may want to adjust this policy)
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
     * Get domain information
     */
    public function getDomainInfo(string $domain): JsonResponse
    {
        $validator = Validator::make(['domain' => $domain], [
            'domain' => 'required|string|max:255|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $result = $this->virusTotalService->getDomainInfo($domain);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Domain information retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get domain information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get IP address information
     */
    public function getIpInfo(string $ip): JsonResponse
    {
        $validator = Validator::make(['ip' => $ip], [
            'ip' => 'required|ip'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $result = $this->virusTotalService->getIpInfo($ip);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'IP information retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get IP information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file analysis by hash
     */
    public function getFileAnalysis(string $hash): JsonResponse
    {
        $validator = Validator::make(['hash' => $hash], [
            'hash' => 'required|string|size:64|regex:/^[a-f0-9]+$/i' // SHA-256
        ]);

        if ($validator->fails()) {
            // Try MD5 or SHA-1
            $validator = Validator::make(['hash' => $hash], [
                'hash' => 'required|string|regex:/^[a-f0-9]+$/i'
            ]);

            if ($validator->fails() || !in_array(strlen($hash), [32, 40, 64])) {
                throw new ValidationException($validator);
            }
        }

        try {
            $result = $this->virusTotalService->getFileAnalysis($hash);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'File analysis retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file analysis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get API quota information
     */
    public function getQuotaInfo(): JsonResponse
    {
        try {
            $result = $this->virusTotalService->getQuotaInfo();

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Quota information retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get quota information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if VirusTotal is configured
     */
    public function isConfigured(): JsonResponse
    {
        $isConfigured = $this->virusTotalService->isConfigured();

        return response()->json([
            'success' => true,
            'configured' => $isConfigured,
            'message' => $isConfigured ? 'VirusTotal is configured' : 'VirusTotal is not configured'
        ]);
    }
}
