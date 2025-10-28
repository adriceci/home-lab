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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary()->comment('Unique cache key identifier');
            $table->mediumText('value')->comment('Cached data value');
            $table->integer('expiration')->comment('Unix timestamp when the cache entry expires');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary()->comment('Cache lock key identifier');
            $table->string('owner')->comment('Process or instance that owns the lock');
            $table->integer('expiration')->comment('Unix timestamp when the lock expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
