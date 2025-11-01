<?php

namespace App\Services;

use App\Exceptions\VirusTotalException;
use App\Models\File;
use App\Services\VirusTotal\VirusTotalErrorCodes;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class VirusTotalService
{
    private ?string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct()
    {
        $this->apiKey = config('services.virustotal.api_key') ?: null;
        $this->baseUrl = config('services.virustotal.base_url', 'https://www.virustotal.com/api/v3');
        $this->timeout = (int) config('services.virustotal.timeout', 30);
        $this->maxRetries = (int) config('services.virustotal.max_retries', 3);
        $this->retryDelay = (int) config('services.virustotal.retry_delay', 1000);
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

        throw $this->parseErrorResponse($response, 'Failed to scan URL');
    }

    /**
     * Get analysis status by analysis ID
     * This method retrieves the status of an analysis using the Analysis ID returned from scanUrl or scanFile
     */
    public function getAnalysis(string $analysisId): array
    {
        $this->logRequest('get_analysis', "Getting analysis status for ID: {$analysisId}");

        $response = $this->makeRequest('GET', "/analyses/{$analysisId}");

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_analysis_success', "Analysis status retrieved successfully for ID: {$analysisId}");
            return $data;
        }

        throw $this->parseErrorResponse($response, 'Failed to get analysis');
    }

    /**
     * Get URL analysis report by URL ID (base64 encoded URL)
     * Use this after the analysis is completed to get the full report
     * @param string $urlId The base64 encoded URL ID (not the Analysis ID)
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

        throw $this->parseErrorResponse($response, 'Failed to get URL report');
    }

    /**
     * Encode URL to base64 URL ID format required by VirusTotal API
     */
    public function encodeUrlId(string $url): string
    {
        // VirusTotal uses base64url encoding (RFC 4648) without padding
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    /**
     * Scan a file with VirusTotal (for files < 32MB)
     * Accepts storage path from quarantine disk (e.g., 'path/to/file.ext')
     */
    public function scanFile(string $storagePath, string $disk = 'quarantine'): array
    {
        $this->logRequest('scan_file', "Scanning file from {$disk}: {$storagePath}");

        // Ensure we're only accessing quarantine disk
        if ($disk !== 'quarantine') {
            throw new Exception("Files can only be scanned from quarantine disk. Requested disk: {$disk}");
        }

        if (!Storage::disk($disk)->exists($storagePath)) {
            throw new Exception("File not found in {$disk}: {$storagePath}");
        }

        // Get file size using Storage (safe method that doesn't execute the file)
        $fileSize = Storage::disk($disk)->size($storagePath);
        if ($fileSize > 32 * 1024 * 1024) { // 32MB
            throw new Exception("File too large for direct upload. Use scanLargeFile method instead.");
        }

        // Get file content using Storage facade (safe - doesn't execute file)
        $fileContent = Storage::disk($disk)->get($storagePath);
        $fileName = basename($storagePath);

        $response = $this->makeRequestWithContent('POST', '/files', [], $fileContent, $fileName);

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('scan_file_success', "File scan initiated successfully for: {$storagePath}");
            return $data;
        }

        throw $this->parseErrorResponse($response, 'Failed to scan file');
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

        throw $this->parseErrorResponse($response, 'Failed to get large file upload URL');
    }

    /**
     * Upload large file to VirusTotal
     * Accepts storage path from quarantine disk (e.g., 'path/to/file.ext')
     */
    public function uploadLargeFile(string $storagePath, string $uploadUrl, string $disk = 'quarantine'): array
    {
        $this->logRequest('upload_large_file', "Uploading large file from {$disk}: {$storagePath}");

        // Ensure we're only accessing quarantine disk
        if ($disk !== 'quarantine') {
            throw new Exception("Files can only be uploaded from quarantine disk. Requested disk: {$disk}");
        }

        if (!Storage::disk($disk)->exists($storagePath)) {
            throw new Exception("File not found in {$disk}: {$storagePath}");
        }

        // Get file content using Storage facade (safe - doesn't execute file)
        $fileContent = Storage::disk($disk)->get($storagePath);
        $fileName = basename($storagePath);

        $this->ensureApiKeyConfigured();

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'x-apikey' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->attach('file', $fileContent, $fileName)
            ->post($uploadUrl);

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('upload_large_file_success', "Large file uploaded successfully: {$storagePath}");
            return $data;
        }

        throw $this->parseErrorResponse($response, 'Failed to upload large file');
    }

    /**
     * Get file analysis report by file hash (SHA-256, MD5, or SHA-1)
     * Use this after the analysis is completed to get the full report
     * @param string $fileHash The file hash (SHA-256, MD5, or SHA-1), not the Analysis ID
     */
    public function getFileReport(string $fileHash): array
    {
        $this->logRequest('get_file_report', "Getting file report for hash: {$fileHash}");

        $response = $this->makeRequest('GET', "/files/{$fileHash}");

        if ($response->successful()) {
            $data = $response->json();
            $this->logRequest('get_file_report_success', "File report retrieved successfully for hash: {$fileHash}");
            return $data;
        }

        throw $this->parseErrorResponse($response, 'Failed to get file report');
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

        throw $this->parseErrorResponse($response, 'Failed to get domain info');
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

        throw $this->parseErrorResponse($response, 'Failed to get IP info');
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

        throw $this->parseErrorResponse($response, 'Failed to get file analysis');
    }

    /**
     * Make HTTP request to VirusTotal API with retry logic
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], ?string $filePath = null): Response
    {
        $this->ensureApiKeyConfigured();

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
                    // DEPRECATED: Use makeRequestWithContent instead for files from Storage
                    $request = $request->attach('file', file_get_contents($filePath), basename($filePath));
                }

                $response = match (strtoupper($method)) {
                    'GET' => $request->get($url, $data),
                    'POST' => $filePath
                        ? $request->post($url)
                        : ($data ? $request->asForm()->post($url, $data) : $request->post($url)),
                    'PUT' => $request->put($url, $data),
                    'DELETE' => $request->delete($url),
                    default => throw new Exception("Unsupported HTTP method: {$method}")
                };

                // Check for rate limiting (429)
                if ($response->status() === VirusTotalErrorCodes::HTTP_TOO_MANY_REQUESTS) {
                    $retryAfter = $response->header('Retry-After') ?? 60;
                    $this->logRequest('rate_limit', "Rate limited. Waiting {$retryAfter} seconds before retry.");
                    
                    // Try to parse error for better logging
                    try {
                        $error = $this->parseErrorResponse($response, 'Rate limited');
                        if ($error->isRetryable()) {
                            sleep($retryAfter);
                            $attempt++;
                            continue;
                        }
                    } catch (Exception $e) {
                        // If parsing fails, use default behavior
                        sleep($retryAfter);
                        $attempt++;
                        continue;
                    }
                }

                // If successful, return response
                if ($response->successful()) {
                    return $response;
                }

                // Handle client errors (4xx) - parse and throw appropriate exception
                if ($response->clientError()) {
                    // For some client errors, we might want to retry (e.g., NotAvailableYet)
                    $parsedError = $this->parseErrorResponse($response, 'Client error');
                    if ($parsedError->isRetryable() && $attempt < $this->maxRetries - 1) {
                        $attempt++;
                        $delay = $this->retryDelay * $attempt;
                        $this->logRequest('retry', "Retryable client error. Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries})");
                        usleep($delay * 1000);
                        continue;
                    }
                    // For non-retryable client errors, throw immediately
                    throw $parsedError;
                }

                // Server error (5xx), retry
                if ($response->serverError()) {
                    $parsedError = $this->parseErrorResponse($response, 'Server error');
                    $attempt++;
                    if ($attempt < $this->maxRetries && $parsedError->isRetryable()) {
                        $delay = $this->retryDelay * $attempt;
                        $this->logRequest('retry', "Server error. Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries})");
                        usleep($delay * 1000);
                        continue;
                    }
                    // Max retries exceeded or not retryable, throw error
                    throw $parsedError;
                }
            } catch (VirusTotalException $e) {
                // VirusTotal-specific exceptions should be rethrown immediately if not retryable
                if (!$e->isRetryable() || $attempt >= $this->maxRetries - 1) {
                    throw $e;
                }
                $attempt++;
                $delay = $this->retryDelay * $attempt;
                $this->logRequest('retry', "VirusTotal error (retryable). Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries}): " . $e->getMessage());
                usleep($delay * 1000);
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
     * Make HTTP request with file content (from Storage) to VirusTotal API with retry logic
     */
    private function makeRequestWithContent(string $method, string $endpoint, array $data = [], ?string $fileContent = null, ?string $fileName = null): Response
    {
        $this->ensureApiKeyConfigured();

        $url = $this->baseUrl . $endpoint;
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $request = Http::timeout($this->timeout)
                    ->withHeaders([
                        'x-apikey' => $this->apiKey,
                        'Accept' => 'application/json',
                    ]);

                if ($fileContent && $fileName) {
                    $request = $request->attach('file', $fileContent, $fileName);
                }

                $response = match (strtoupper($method)) {
                    'GET' => $request->get($url, $data),
                    'POST' => $fileContent ? $request->post($url) : $request->post($url, $data),
                    'PUT' => $request->put($url, $data),
                    'DELETE' => $request->delete($url),
                    default => throw new Exception("Unsupported HTTP method: {$method}")
                };

                // Check for rate limiting (429)
                if ($response->status() === VirusTotalErrorCodes::HTTP_TOO_MANY_REQUESTS) {
                    $retryAfter = $response->header('Retry-After') ?? 60;
                    $this->logRequest('rate_limit', "Rate limited. Waiting {$retryAfter} seconds before retry.");
                    
                    // Try to parse error for better logging
                    try {
                        $error = $this->parseErrorResponse($response, 'Rate limited');
                        if ($error->isRetryable()) {
                            sleep($retryAfter);
                            $attempt++;
                            continue;
                        }
                    } catch (Exception $e) {
                        // If parsing fails, use default behavior
                        sleep($retryAfter);
                        $attempt++;
                        continue;
                    }
                }

                // If successful, return response
                if ($response->successful()) {
                    return $response;
                }

                // Handle client errors (4xx) - parse and throw appropriate exception
                if ($response->clientError()) {
                    // For some client errors, we might want to retry (e.g., NotAvailableYet)
                    $parsedError = $this->parseErrorResponse($response, 'Client error');
                    if ($parsedError->isRetryable() && $attempt < $this->maxRetries - 1) {
                        $attempt++;
                        $delay = $this->retryDelay * $attempt;
                        $this->logRequest('retry', "Retryable client error. Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries})");
                        usleep($delay * 1000);
                        continue;
                    }
                    // For non-retryable client errors, throw immediately
                    throw $parsedError;
                }

                // Server error (5xx), retry
                if ($response->serverError()) {
                    $parsedError = $this->parseErrorResponse($response, 'Server error');
                    $attempt++;
                    if ($attempt < $this->maxRetries && $parsedError->isRetryable()) {
                        $delay = $this->retryDelay * $attempt;
                        $this->logRequest('retry', "Server error. Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries})");
                        usleep($delay * 1000);
                        continue;
                    }
                    // Max retries exceeded or not retryable, throw error
                    throw $parsedError;
                }
            } catch (VirusTotalException $e) {
                // VirusTotal-specific exceptions should be rethrown immediately if not retryable
                if (!$e->isRetryable() || $attempt >= $this->maxRetries - 1) {
                    throw $e;
                }
                $attempt++;
                $delay = $this->retryDelay * $attempt;
                $this->logRequest('retry', "VirusTotal error (retryable). Retrying in {$delay}ms (attempt {$attempt}/{$this->maxRetries}): " . $e->getMessage());
                usleep($delay * 1000);
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
     * Parse VirusTotal API error response and create appropriate exception
     * 
     * @param Response $response
     * @param string $defaultMessage
     * @return VirusTotalException
     */
    private function parseErrorResponse(Response $response, string $defaultMessage = 'VirusTotal API error'): VirusTotalException
    {
        $httpCode = $response->status();
        $errorCode = '';
        $errorMessage = $defaultMessage;
        $errorData = null;

        try {
            $jsonData = $response->json();
            $errorData = $jsonData;

            // VirusTotal error format: {"error": {"code": "ErrorCode", "message": "Error message"}}
            if (isset($jsonData['error'])) {
                $error = $jsonData['error'];
                $errorCode = $error['code'] ?? '';
                $errorMessage = $error['message'] ?? $defaultMessage;
            } elseif (isset($jsonData['code'])) {
                // Alternative format where error code is at root level
                $errorCode = $jsonData['code'];
                $errorMessage = $jsonData['message'] ?? $defaultMessage;
            }
        } catch (Exception $e) {
            // If JSON parsing fails, try to get error from response body
            $body = $response->body();
            if (!empty($body)) {
                $errorMessage = $body;
            }
        }

        // If we couldn't determine the error code, try to infer from HTTP status
        if (empty($errorCode) && $httpCode > 0) {
            $errorCode = $this->inferErrorCodeFromHttpStatus($httpCode);
        }

        // Get description for the error code
        $description = VirusTotalErrorCodes::getErrorDescription($errorCode);
        if (!empty($description) && $errorMessage === $defaultMessage) {
            $errorMessage = $description;
        }

        return new VirusTotalException(
            $errorMessage,
            $httpCode,
            $errorCode,
            $errorData
        );
    }

    /**
     * Infer error code from HTTP status code when not provided in response
     * 
     * @param int $httpCode
     * @return string
     */
    private function inferErrorCodeFromHttpStatus(int $httpCode): string
    {
        return match ($httpCode) {
            VirusTotalErrorCodes::HTTP_BAD_REQUEST => VirusTotalErrorCodes::ERROR_BAD_REQUEST,
            VirusTotalErrorCodes::HTTP_UNAUTHORIZED => VirusTotalErrorCodes::ERROR_AUTHENTICATION_REQUIRED,
            VirusTotalErrorCodes::HTTP_FORBIDDEN => VirusTotalErrorCodes::ERROR_FORBIDDEN,
            VirusTotalErrorCodes::HTTP_NOT_FOUND => VirusTotalErrorCodes::ERROR_NOT_FOUND,
            VirusTotalErrorCodes::HTTP_CONFLICT => VirusTotalErrorCodes::ERROR_ALREADY_EXISTS,
            VirusTotalErrorCodes::HTTP_FAILED_DEPENDENCY => VirusTotalErrorCodes::ERROR_FAILED_DEPENDENCY,
            VirusTotalErrorCodes::HTTP_TOO_MANY_REQUESTS => VirusTotalErrorCodes::ERROR_TOO_MANY_REQUESTS,
            VirusTotalErrorCodes::HTTP_SERVICE_UNAVAILABLE => VirusTotalErrorCodes::ERROR_TRANSIENT,
            VirusTotalErrorCodes::HTTP_GATEWAY_TIMEOUT => VirusTotalErrorCodes::ERROR_DEADLINE_EXCEEDED,
            default => '',
        };
    }

    /**
     * Get status value for database based on VirusTotal error
     * 
     * @param VirusTotalException $exception
     * @return string
     */
    public function getStatusFromError(VirusTotalException $exception): string
    {
        return VirusTotalErrorCodes::errorCodeToStatus($exception->getErrorCode());
    }

    /**
     * Log request to audit log
     */
    private function logRequest(string $action, string $description): void
    {
        // Audit logging removed - will be handled by audit-center package middleware
    }

    /**
     * Ensure API key is configured, throw exception if not
     */
    private function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new Exception('VirusTotal API key is not configured. Please set VIRUSTOTAL_API_KEY in your .env file.');
        }
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
