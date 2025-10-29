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
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary UUID for the setting');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade')->comment('User who owns this setting');
            $table->string('key')->comment('Setting key');
            $table->string('type')->default('string')->comment('Data type: string, number, boolean, json');
            $table->string('group')->nullable()->index()->comment('Logical group, e.g., ui, billing, streaming');
            $table->json('value')->nullable()->comment('Setting value, stored as JSON');
            $table->boolean('is_public')->default(false)->comment('Whether the setting can be exposed to clients');
            $table->text('description')->nullable()->comment('Human readable description for admins');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');

            // Unique constraint for user_id + key combination
            $table->unique(['user_id', 'key'], 'settings_user_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
