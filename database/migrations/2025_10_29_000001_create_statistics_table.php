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
        Schema::create('statistics', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary UUID for the statistic record');
            $table->uuidMorphs('model');
            $table->string('metric')->index()->comment('Metric name, e.g., views, downloads, searches, plays');
            $table->unsignedBigInteger('value')->default(0)->comment('Metric numeric value');
            $table->json('context')->nullable()->comment('Optional context for the metric (e.g., device, region)');
            $table->timestamp('occurred_at')->nullable()->index()->comment('When the metric occurred');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
