<?php

namespace App\Jobs;

use App\Services\QuarantineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupQuarantineJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of days after which unverified files should be deleted
     */
    private int $days;

    /**
     * Create a new job instance.
     */
    public function __construct(int $days = 10)
    {
        $this->days = $days;
    }

    /**
     * Execute the job.
     */
    public function handle(QuarantineService $quarantineService): void
    {
        Log::info("Starting quarantine cleanup job", ['days_threshold' => $this->days]);
        
        $deletedCount = $quarantineService->cleanupOldFiles($this->days);
        
        Log::info("Quarantine cleanup job completed", [
            'deleted_count' => $deletedCount,
            'days_threshold' => $this->days
        ]);
    }
}
