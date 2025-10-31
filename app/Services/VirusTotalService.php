<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class VirusTotalService
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct()
    {
        $this->apiKey = config('services.virustotal.api_key');
        $this->baseUrl = config('services.virustotal.base_url');
        $this->timeout = config('services.virustotal.timeout');
        $this->maxRetries = config('services.virustotal.max_retries');
        $this->retryDelay = config('services.virustotal.retry_delay');
    }

    /**
     * Scan a URL with VirusTotal
     */
    public function scanUrl(string $url): array
    {
        $this->logRequest('scan_url', "Scanning URL: {$url}");

        $response = $this->makeRequest('POST', '/urls', [
            'url' => $url
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('scan_url_success', "URL scan initiated successfully for: {$url}");
            return $data;
        }

        throw new Exception('Failed to scan URL: ' . $response->body());
    }

    /**
     * Get URL analysis report
     */
    public function getUrlReport(string $urlId): array
    {
        $this->logRequest('get_url_report', "Getting URL report for ID: {$urlId}");

        $response = $this->makeRequest('GET', "/urls/{$urlId}");

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_url_report_success', "URL report retrieved successfully for ID: {$urlId}");
            return $data;
        }

        throw new Exception('Failed to get URL report: ' . $response->body());
    }

    /**
     * Scan a file with VirusTotal (for files < 32MB)
     */
    public function scanFile(string $filePath): array
    {
        $this->logRequest('scan_file', "Scanning file: {$filePath}");

        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize > 32 * 1024 * 1024) { // 32MB
            throw new Exception("File too large for direct upload. Use scanLargeFile method instead.");
        }

        $response = $this->makeRequest('POST', '/files', [], $filePath);

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('scan_file_success', "File scan initiated successfully for: {$filePath}");
            return $data;
        }

        throw new Exception('Failed to scan file: ' . $response->body());
    }

    /**
     * Get large file upload URL for files > 32MB
     */
    public function getLargeFileUploadUrl(): array
    {
        $this->logRequest('get_large_file_upload_url', "Getting large file upload URL");

        $response = $this->makeRequest('GET', '/files/upload_url');

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_large_file_upload_url_success', "Large file upload URL retrieved successfully");
            return $data;
        }

        throw new Exception('Failed to get large file upload URL: ' . $response->body());
    }

    /**
     * Upload large file to VirusTotal
     */
    public function uploadLargeFile(string $filePath, string $uploadUrl): array
    {
        $this->logRequest('upload_large_file', "Uploading large file: {$filePath}");

        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $response = Http::timeout($this->timeout)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post($uploadUrl);

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('upload_large_file_success', "Large file uploaded successfully: {$filePath}");
            return $data;
        }

        throw new Exception('Failed to upload large file: ' . $response->body());
    }

    /**
     * Get file analysis report
     */
    public function getFileReport(string $fileId): array
    {
        $this->logRequest('get_file_report', "Getting file report for ID: {$fileId}");

        $response = $this->makeRequest('GET', "/files/{$fileId}");

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_file_report_success', "File report retrieved successfully for ID: {$fileId}");
            return $data;
        }

        throw new Exception('Failed to get file report: ' . $response->body());
    }

    /**
     * Get domain information
     */
    public function getDomainInfo(string $domain): array
    {
        $this->logRequest('get_domain_info', "Getting domain info for: {$domain}");

        $response = $this->makeRequest('GET', "/domains/{$domain}");

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_domain_info_success', "Domain info retrieved successfully for: {$domain}");
            return $data;
        }

        throw new Exception('Failed to get domain info: ' . $response->body());
    }

    /**
     * Get IP address information
     */
    public function getIpInfo(string $ip): array
    {
        $this->logRequest('get_ip_info', "Getting IP info for: {$ip}");

        $response = $this->makeRequest('GET', "/ip_addresses/{$ip}");

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_ip_info_success', "IP info retrieved successfully for: {$ip}");
            return $data;
        }

        throw new Exception('Failed to get IP info: ' . $response->body());
    }

    /**
     * Get file analysis by hash (SHA-256, MD5, or SHA-1)
     */
    public function getFileAnalysis(string $hash): array
    {
        $this->logRequest('get_file_analysis', "Getting file analysis for hash: {$hash}");

        $response = $this->makeRequest('GET', "/files/{$hash}");

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_file_analysis_success', "File analysis retrieved successfully for hash: {$hash}");
            return $data;
        }

        throw new Exception('Failed to get file analysis: ' . $response->body());
    }

    /**
     * Make HTTP request to VirusTotal API with retry logic
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], ?string $filePath = null): Response
    {
        $url = $this->baseUrl . $endpoint;
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $request = Http::timeout($this->timeout)
                    ->withHeaders([
                        'x-apikey' => $this->apiKey,
                        'Accept' => 'application/json',
                    ]);

                if ($filePath) {
                    $request = $request->attach('file', file_get_contents($filePath), basename($filePath));
                }

                $response = match (strtoupper($method)) {
                    'GET' => $request->get($url, $data),
                    'POST' => $filePath ? $request->post($url) : $request->post($url, $data),
                    'PUT' => $request->put($url, $data),
                    'DELETE' => $request->delete($url),
                    default => throw new Exception("Unsupported HTTP method: {$method}")
                };

                // Check for rate limiting
                if ($response->status() === 429) {
                    $retryAfter = $response->header('Retry-After') ?? 60;
                    $this->logRequest('rate_limit', "Rate limited. Waiting {$retryAfter} seconds before retry.");
                    sleep($retryAfter);
                    $attempt++;
                    continue;
                }

                // If successful or client error (4xx), return response
                if ($response->successful() || $response->clientError()) {
                    return $response;
                }

                // Server error (5xx), retry
                if ($response->serverError()) {
                    $attempt++;
                    if ($attempt < $this->maxRetries) {
                        $delay = $this->retryDelay * $attempt;
                        $this->logRequest('retry', "Server error. Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries})");
                        usleep($delay * 1000);
                    }
                }
            } catch (Exception $e) {
                $attempt++;
                if ($attempt >= $this->maxRetries) {
                    $this->logRequest('max_retries_exceeded', "Max retries exceeded. Last error: " . $e->getMessage());
                    throw $e;
                }

                $delay = $this->retryDelay * $attempt;
                $this->logRequest('retry', "Request failed. Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries}): " . $e->getMessage());
                usleep($delay * 1000);
            }
        }

        throw new Exception("Max retries exceeded for {$method} {$endpoint}");
    }

    /**
     * Log request to audit log
     */
    private function logRequest(string $action, string $description): void
    {
        // Audit logging removed - will be handled by audit-center package middleware
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get API quota information (if available)
     */
    public function getQuotaInfo(): array
    {
        try {
            $response = $this->makeRequest('GET', '/user');
            if ($response->successful()) {
                return $response->json();
            }
        } catch (Exception $e) {
            Log::error("Failed to get quota info: " . $e->getMessage());
        }

        return [];
    }
}
