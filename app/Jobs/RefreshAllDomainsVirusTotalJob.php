<?php

namespace App\Jobs;

use App\Models\Domain;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RefreshAllDomainsVirusTotalJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting mass refresh of VirusTotal information for all active domains');

        // Get all active domains
        $domains = Domain::where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        $count = $domains->count();
        
        Log::info('Found active domains to refresh', [
            'count' => $count,
        ]);

        // Dispatch UpdateDomainVirusTotalInfoJob for each domain
        foreach ($domains as $domain) {
            UpdateDomainVirusTotalInfoJob::dispatch($domain->id);
        }

        Log::info('Dispatched VirusTotal update jobs for all active domains', [
            'domains_processed' => $count,
        ]);
    }
}
