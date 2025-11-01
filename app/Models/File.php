<?php

namespace App\Models;

use App\Traits\HasPrefixedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, HasPrefixedUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'path',
        'size',
        'type',
        'mime_type',
        'extension',
        'virustotal_scan_id',
        'virustotal_status',
        'virustotal_results',
        'virustotal_scanned_at',
        'storage_disk',
        'quarantined_at',
        'download_status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'type' => 'string',
            'size' => 'integer',
            'mime_type' => 'string',
            'extension' => 'string',
            'virustotal_scan_id' => 'string',
            'virustotal_status' => 'string',
            'virustotal_results' => 'array',
            'virustotal_scanned_at' => 'datetime',
            'storage_disk' => 'string',
            'quarantined_at' => 'datetime',
            'download_status' => 'string',
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
        return 'FIL';
    }
}
