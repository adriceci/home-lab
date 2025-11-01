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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('Unique job identifier');
            $table->string('queue')->index()->comment('Queue name where the job belongs');
            $table->longText('payload')->comment('Serialized job data and parameters');
            $table->unsignedTinyInteger('attempts')->comment('Number of times the job has been attempted');
            $table->unsignedInteger('reserved_at')->nullable()->comment('Unix timestamp when the job was reserved for processing');
            $table->unsignedInteger('available_at')->comment('Unix timestamp when the job becomes available for processing');
            $table->unsignedInteger('created_at')->comment('Unix timestamp when the job was created');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->id()->comment('Unique batch identifier');
            $table->string('name')->comment('Human-readable name for the batch');
            $table->integer('total_jobs')->comment('Total number of jobs in this batch');
            $table->integer('pending_jobs')->comment('Number of jobs still pending execution');
            $table->integer('failed_jobs')->comment('Number of jobs that have failed');
            $table->longText('failed_job_ids')->comment('Serialized array of failed job IDs');
            $table->mediumText('options')->nullable()->comment('Additional batch options and configuration');
            $table->integer('cancelled_at')->nullable()->comment('Unix timestamp when the batch was cancelled');
            $table->integer('created_at')->comment('Unix timestamp when the batch was created');
            $table->integer('finished_at')->nullable()->comment('Unix timestamp when the batch finished processing');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the failed job record');
            $table->string('uuid')->unique()->comment('Original job UUID that failed');
            $table->text('connection')->comment('Database connection name used for the job');
            $table->text('queue')->comment('Queue name where the job was processed');
            $table->longText('payload')->comment('Serialized job data and parameters');
            $table->longText('exception')->comment('Full exception details and stack trace');
            $table->timestamp('failed_at')->useCurrent()->comment('Timestamp when the job failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
