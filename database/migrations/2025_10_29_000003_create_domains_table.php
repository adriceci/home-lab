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

            // VirusTotal fields
            $table->integer('virustotal_reputation')->nullable()->comment('Reputación del dominio en VirusTotal');
            $table->integer('virustotal_votes_harmless')->default(0)->comment('Número de votos harmless');
            $table->integer('virustotal_votes_malicious')->default(0)->comment('Número de votos malicious');
            $table->timestamp('virustotal_last_analysis_date')->nullable()->comment('Fecha del último análisis');
            $table->json('virustotal_last_analysis_stats')->nullable()->comment('Estadísticas completas de análisis (harmless, malicious, suspicious, undetected)');
            $table->json('virustotal_categories')->nullable()->comment('Categorías del dominio');
            $table->text('virustotal_whois')->nullable()->comment('Información WHOIS del dominio');
            $table->json('virustotal_subdomains')->nullable()->comment('Lista de subdominios relacionados');
            $table->timestamp('virustotal_last_checked_at')->nullable()->comment('Última vez que se verificó con VirusTotal');
            $table->string('virustotal_status')->nullable()->comment('Estado de verificación (pending, checked, error, quota_exceeded, etc.)');

            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');

            // Indexes for better performance
            $table->index('virustotal_status');
            $table->index('virustotal_last_checked_at');
            $table->index('is_active');
            $table->index('type');
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
