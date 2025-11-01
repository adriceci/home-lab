<?php

namespace App\Jobs;

use App\Exceptions\VirusTotalException;
use App\Models\Domain;
use App\Models\ScannedUrl;
use App\Services\VirusTotalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateDomainVirusTotalInfoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 120; // Wait 2 minutes between retries

    private string $domainId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $domainId)
    {
        $this->domainId = $domainId;
    }

    /**
     * Execute the job.
     */
    public function handle(VirusTotalService $virusTotalService): void
    {
        Log::info('Updating VirusTotal information for domain', [
            'domain_id' => $this->domainId,
        ]);

        try {
            $domain = Domain::find($this->domainId);

            if (!$domain) {
                throw new Exception("Domain not found: {$this->domainId}");
            }

            // Update status to indicate we're checking
            $domain->update([
                'virustotal_status' => 'scanning',
            ]);

            // Validate that domain has a URL
            if (empty($domain->url)) {
                throw new Exception("Domain '{$domain->name}' does not have a URL configured. Cannot analyze with VirusTotal.");
            }

            // Extract the actual domain from the URL (e.g., "1.piratebays.to" from "https://1.piratebays.to")
            $domainName = ScannedUrl::extractDomain($domain->url);

            // Get domain information from VirusTotal
            $domainInfo = $virusTotalService->getDomainInfo($domainName);
            
            // Get votes separately
            $votesData = [];
            try {
                $votesData = $virusTotalService->getDomainVotes($domainName);
            } catch (Exception $e) {
                // Votes endpoint might not be available or might fail, but we can continue with domain info
                Log::warning('Failed to get domain votes, continuing with domain info only', [
                    'domain_id' => $this->domainId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Extract information from domain info response
            $attributes = $domainInfo['data']['attributes'] ?? [];
            
            // Extract votes - try from votes endpoint first, then from domain info
            $votesHarmless = 0;
            $votesMalicious = 0;
            
            if (!empty($votesData['data'])) {
                // Count votes from votes endpoint
                foreach ($votesData['data'] as $vote) {
                    $verdict = $vote['attributes']['verdict'] ?? '';
                    if ($verdict === 'harmless') {
                        $votesHarmless++;
                    } elseif ($verdict === 'malicious') {
                        $votesMalicious++;
                    }
                }
            }
            
            // Fallback: also check total_votes from domain attributes if available
            if (isset($attributes['total_votes'])) {
                if ($votesHarmless === 0) {
                    $votesHarmless = $attributes['total_votes']['harmless'] ?? 0;
                }
                if ($votesMalicious === 0) {
                    $votesMalicious = $attributes['total_votes']['malicious'] ?? 0;
                }
            }

            // Extract other information
            $reputation = $attributes['reputation'] ?? null;
            $lastAnalysisDate = isset($attributes['last_analysis_date']) 
                ? date('Y-m-d H:i:s', $attributes['last_analysis_date']) 
                : null;
            
            $lastAnalysisStats = $attributes['last_analysis_stats'] ?? null;
            $categories = $attributes['categories'] ?? null;
            
            // Extract WHOIS if available
            $whois = null;
            if (isset($attributes['whois'])) {
                $whois = is_string($attributes['whois']) 
                    ? $attributes['whois'] 
                    : json_encode($attributes['whois']);
            }

            // Extract subdomains if available
            $subdomains = null;
            if (isset($attributes['subdomains'])) {
                $subdomains = $attributes['subdomains'];
            }

            // Update domain with all information
            $domain->update([
                'virustotal_reputation' => $reputation,
                'virustotal_votes_harmless' => $votesHarmless,
                'virustotal_votes_malicious' => $votesMalicious,
                'virustotal_last_analysis_date' => $lastAnalysisDate,
                'virustotal_last_analysis_stats' => $lastAnalysisStats,
                'virustotal_categories' => $categories,
                'virustotal_whois' => $whois,
                'virustotal_subdomains' => $subdomains,
                'virustotal_last_checked_at' => now(),
                'virustotal_status' => 'checked',
            ]);

            Log::info('VirusTotal information updated successfully for domain', [
                'domain_id' => $this->domainId,
                'domain_name' => $domain->name,
                'domain_url' => $domain->url,
                'virustotal_domain' => $domainName,
                'reputation' => $reputation,
                'votes_harmless' => $votesHarmless,
                'votes_malicious' => $votesMalicious,
            ]);

        } catch (VirusTotalException $e) {
            // VirusTotal-specific error - update status accordingly
            Log::error('VirusTotal error while updating domain information', [
                'domain_id' => $this->domainId,
                'error_code' => $e->getErrorCode(),
                'http_code' => $e->getHttpCode(),
                'error' => $e->getMessage(),
            ]);

            $domain = Domain::find($this->domainId);
            if ($domain) {
                $errorStatus = $virusTotalService->getStatusFromError($e);
                $domain->update([
                    'virustotal_status' => $errorStatus,
                    'virustotal_last_checked_at' => now(),
                ]);
            }

            // Re-throw if not retryable to let Laravel handle retries
            if (!$e->isRetryable() || $this->attempts() >= $this->tries) {
                throw $e;
            }

            // For retryable errors, throw to trigger retry
            throw $e;

        } catch (Exception $e) {
            Log::error('Failed to update VirusTotal information for domain', [
                'domain_id' => $this->domainId,
                'error' => $e->getMessage(),
            ]);

            $domain = Domain::find($this->domainId);
            if ($domain) {
                $domain->update([
                    'virustotal_status' => 'error',
                    'virustotal_last_checked_at' => now(),
                ]);
            }

            throw $e;
        }
    }
}
