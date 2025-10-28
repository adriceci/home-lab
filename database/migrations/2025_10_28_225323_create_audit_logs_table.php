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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Unique identifier for the audit log entry');
            $table->foreignUuid('user_id')->nullable()->constrained()->onDelete('set null')->comment('ID of the user who performed the action');
            $table->string('action')->comment('Action performed (e.g., login, logout, create, update, delete)');
            $table->string('model_type')->nullable()->comment('Type of model affected (e.g., App\Models\User)');
            $table->string('model_id')->nullable()->comment('ID of the affected model instance');
            $table->json('old_values')->nullable()->comment('Previous values before the change');
            $table->json('new_values')->nullable()->comment('New values after the change');
            $table->string('ip_address', 45)->nullable()->comment('IP address from which the action was performed');
            $table->text('user_agent')->nullable()->comment('User agent string from the browser/client');
            $table->string('url')->nullable()->comment('URL where the action was performed');
            $table->string('method', 10)->nullable()->comment('HTTP method used for the action');
            $table->text('description')->nullable()->comment('Human-readable description of the action');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');

            // Indexes for better performance
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
