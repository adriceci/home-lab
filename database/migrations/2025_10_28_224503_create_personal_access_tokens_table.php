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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Unique identifier for the personal access token');
            $table->string('tokenable_type')->comment('Type of the tokenable model (e.g., App\\Models\\User)');
            $table->uuid('tokenable_id')->comment('ID of the tokenable model instance');
            $table->text('name')->comment('Human-readable name for the token');
            $table->string('token', 64)->unique()->comment('Hashed token value for API authentication');
            $table->text('abilities')->nullable()->comment('JSON array of token abilities/permissions');
            $table->timestamp('last_used_at')->nullable()->comment('Timestamp when the token was last used');
            $table->timestamp('expires_at')->nullable()->index()->comment('Timestamp when the token expires');
            $table->timestamps();

            // Add index for the polymorphic relationship
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
