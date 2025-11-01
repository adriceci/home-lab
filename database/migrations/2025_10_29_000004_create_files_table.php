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
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary UUID for the file');
            $table->string('name')->comment('File name');
            $table->string('path')->comment('File path');
            $table->enum('storage_disk', ['quarantine', 'local', 'public'])->default('quarantine')->comment('Storage disk where the file is stored');
            $table->timestamp('quarantined_at')->nullable()->comment('Timestamp when file was placed in quarantine');
            $table->unsignedBigInteger('size')->default(0)->comment('File size in bytes');
            $table->string('type')->comment('File type: movie, series, episode, scan, etc.');
            // Download status
            $table->enum('download_status', [
                'pending',
                'verifying_url',
                'url_verified',
                'url_rejected',
                'downloading',
                'download_completed',
                'scanning_file',
                'file_verified',
                'file_rejected',
                'moving_to_storage',
                'completed',
                'failed',
                'cancelled',
            ])->nullable()->comment('Download process status');
            $table->string('mime_type')->nullable()->comment('MIME type of the file');
            $table->string('extension')->nullable()->comment('File extension');

            // VirusTotal fields
            $table->string('virustotal_scan_id')->nullable()->comment('VirusTotal scan ID');
            $table->enum('virustotal_status', [
                'pending',
                'scanning',
                'completed',
                'error',
                'quota_exceeded',
                'authentication_error',
                'not_found',
                'forbidden',
                'timeout',
                'transient_error',
                'dependency_error',
                'already_exists',
                'bad_request',
                'not_available',
            ])->nullable()->comment('VirusTotal scan status');
            $table->json('virustotal_results')->nullable()->comment('VirusTotal scan results');
            $table->timestamp('virustotal_scanned_at')->nullable()->comment('When the file was scanned');

            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');

            // Indexes for better performance
            $table->index('storage_disk');
            $table->index('quarantined_at');
            $table->index('virustotal_scan_id');
            $table->index('virustotal_status');
            $table->index('type');
            $table->index('download_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
