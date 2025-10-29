<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'type',
        'url',
        'is_active',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'status' => 'string',
            'type' => 'string',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
