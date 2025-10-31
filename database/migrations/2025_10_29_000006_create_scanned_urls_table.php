<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scanned_urls', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary UUID for the scanned URL');
            $table->string('url')->comment('Full URL that was scanned');
            $table->string('domain')->comment('Domain extracted from URL');

            // VirusTotal fields
            $table->string('virustotal_scan_id')->nullable()->comment('VirusTotal scan ID');
            $table->enum('virustotal_status', ['pending', 'scanning', 'completed', 'error'])->nullable()->comment('VirusTotal scan status');
            $table->json('virustotal_results')->nullable()->comment('VirusTotal scan results');
            $table->timestamp('virustotal_scanned_at')->nullable()->comment('When the URL was scanned');

            // Security fields
            $table->boolean('is_malicious')->default(false)->comment('Whether the URL is flagged as malicious');
            $table->timestamp('blocked_at')->nullable()->comment('Timestamp when URL/domain was blocked');

            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');

            // Indexes for better performance
            $table->index('url');
            $table->index('domain');
            $table->index('virustotal_scan_id');
            $table->index('virustotal_status');
            $table->index('is_malicious');
            $table->index('blocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scanned_urls');
    }
};
