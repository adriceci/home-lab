<?php

namespace App\Models;

use App\Traits\HasPrefixedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repository extends Model
{
    use HasFactory, HasPrefixedUuid, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'description',
        'status',
        'type',
        'icon',
        'url',
        'order',
        'is_active',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'icon' => 'string',
            'type' => 'string',
            'status' => 'string',
            'url' => 'string',
            'order' => 'integer',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
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
        return 'REP';
    }
}
