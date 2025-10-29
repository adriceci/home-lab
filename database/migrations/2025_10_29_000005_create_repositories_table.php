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
        Schema::create('repositories', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary UUID for the repository');
            $table->string('name')->comment('Repository name');
            $table->text('description')->nullable()->comment('Repository description');
            $table->string('status')->default('active')->comment('Repository status: active, inactive, maintenance');
            $table->string('type')->comment('Repository type: movie, series, music, etc.');
            $table->string('icon')->nullable()->comment('Repository icon');
            $table->string('url')->nullable()->comment('Repository URL');
            $table->integer('order')->default(0)->comment('Display order');
            $table->boolean('is_active')->default(true)->comment('Whether the repository is active');
            $table->boolean('is_verified')->default(false)->comment('Whether the repository is verified');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
