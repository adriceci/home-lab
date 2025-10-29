<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
