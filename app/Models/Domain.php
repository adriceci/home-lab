<?php

namespace App\Models;

use App\Traits\HasPrefixedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasFactory, HasPrefixedUuid, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'description',
        'status',
        'type',
        'url',
        'is_active',
        'is_verified',
        'virustotal_reputation',
        'virustotal_votes_harmless',
        'virustotal_votes_malicious',
        'virustotal_last_analysis_date',
        'virustotal_last_analysis_stats',
        'virustotal_categories',
        'virustotal_whois',
        'virustotal_subdomains',
        'virustotal_last_checked_at',
        'virustotal_status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'status' => 'string',
            'type' => 'string',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'virustotal_reputation' => 'integer',
            'virustotal_votes_harmless' => 'integer',
            'virustotal_votes_malicious' => 'integer',
            'virustotal_last_analysis_stats' => 'array',
            'virustotal_categories' => 'array',
            'virustotal_whois' => 'string', // Changed from array to string since it's stored as text
            'virustotal_subdomains' => 'array',
            'virustotal_last_analysis_date' => 'datetime',
            'virustotal_last_checked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the prefix UUID for this model.
     *
     * @return string
     */
    protected static function getUuidPrefix(): string
    {
        return 'DOM';
    }

    /**
     * Check if domain is considered safe based on VirusTotal data
     * 
     * @return bool
     */
    public function isSafe(): bool
    {
        if (!$this->hasVirusTotalInfo()) {
            return false; // Cannot determine safety without VirusTotal info
        }

        // Domain is safe if reputation is positive and no malicious votes
        return ($this->virustotal_reputation ?? 0) > 0 
            && ($this->virustotal_votes_malicious ?? 0) === 0;
    }

    /**
     * Check if domain has VirusTotal information
     * 
     * @return bool
     */
    public function hasVirusTotalInfo(): bool
    {
        return $this->virustotal_status === 'checked' 
            && $this->virustotal_last_checked_at !== null;
    }
}
