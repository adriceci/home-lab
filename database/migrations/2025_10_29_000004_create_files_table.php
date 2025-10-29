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
            $table->unsignedBigInteger('size')->default(0)->comment('File size in bytes');
            $table->string('type')->comment('File type: movie, series, episode, etc.');
            $table->string('mime_type')->nullable()->comment('MIME type of the file');
            $table->string('extension')->nullable()->comment('File extension');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');
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
