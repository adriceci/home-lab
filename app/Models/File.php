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
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'type' => 'string',
            'size' => 'integer',
            'mime_type' => 'string',
            'extension' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
