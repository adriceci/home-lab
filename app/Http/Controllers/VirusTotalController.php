<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\VirusTotalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class VirusTotalController extends Controller
{
    private VirusTotalService $virusTotalService;

    public function __construct(VirusTotalService $virusTotalService)
    {
        $this->virusTotalService = $virusTotalService;
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
            $result = $this->virusTotalService->scanUrl($request->url);

            return response()->json([
                'success' => true,
                'data' => $result,
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

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'URL report retrieved successfully'
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
            $filePath = $file->getRealPath();

            $result = $this->virusTotalService->scanFile($filePath);

            // Store file information in database
            $fileRecord = File::create([
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('uploads'),
                'size' => $file->getSize(),
                'type' => 'scan',
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'virustotal_scan_id' => $result['data']['id'] ?? null,
                'virustotal_status' => 'pending',
                'virustotal_results' => null
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
            $filePath = $file->getRealPath();

            $result = $this->virusTotalService->uploadLargeFile($filePath, $uploadUrl);

            // Store file information in database
            $fileRecord = File::create([
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('uploads'),
                'size' => $file->getSize(),
                'type' => 'scan',
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'virustotal_scan_id' => $result['data']['id'] ?? null,
                'virustotal_status' => 'pending',
                'virustotal_results' => null
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
                $fileRecord->update([
                    'virustotal_status' => 'completed',
                    'virustotal_results' => $result
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'File report retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file report: ' . $e->getMessage()
            ], 500);
        }
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
