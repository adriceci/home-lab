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
        Schema::create('domains', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary UUID for the domain');
            $table->string('name')->comment('Domain name');
            $table->text('description')->nullable()->comment('Domain description');
            $table->string('status')->default('active')->comment('Domain status: active, inactive, suspended');
            $table->string('type')->comment('Domain type: subdomain, custom, etc.');
            $table->string('url')->nullable()->comment('Domain URL');
            $table->boolean('is_active')->default(true)->comment('Whether the domain is active');
            $table->boolean('is_verified')->default(false)->comment('Whether the domain is verified');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
