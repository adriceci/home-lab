<?php

namespace App\Models;

use App\Traits\HasPrefixedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScannedUrl extends Model
{
    use HasFactory, HasPrefixedUuid, SoftDeletes;

    protected $fillable = [
        'url',
        'domain',
        'virustotal_scan_id',
        'virustotal_status',
        'virustotal_results',
        'virustotal_scanned_at',
        'is_malicious',
        'blocked_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'url' => 'string',
            'domain' => 'string',
            'virustotal_scan_id' => 'string',
            'virustotal_status' => 'string',
            'virustotal_results' => 'array',
            'virustotal_scanned_at' => 'datetime',
            'is_malicious' => 'boolean',
            'blocked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Extract domain from URL
     */
    public static function extractDomain(string $url): string
    {
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['host'])) {
            // If parse_url fails, try a simpler approach
            $url = str_replace(['http://', 'https://'], '', $url);
            $parts = explode('/', $url);
            $domain = $parts[0];
        } else {
            $domain = $parsedUrl['host'];
        }
        
        // Remove port if present
        $domain = explode(':', $domain)[0];
        
        // Remove www. prefix
        $domain = preg_replace('/^www\./', '', $domain);
        
        return $domain;
    }

    /**
     * Scope to get malicious URLs
     */
    public function scopeMalicious($query)
    {
        return $query->where('is_malicious', true);
    }

    /**
     * Scope to get malicious domains
     */
    public function scopeMaliciousDomains($query)
    {
        return $query->where('is_malicious', true)
            ->select('domain')
            ->distinct();
    }

    /**
     * Scope to get blocked URLs
     */
    public function scopeBlocked($query)
    {
        return $query->whereNotNull('blocked_at');
    }

    /**
     * Get the prefix UUID for this model.
     *
     * @return string
     */
    public function getPrefixUuid(): string
    {
        return 'URL';
    }
}

