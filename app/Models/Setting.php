<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
        'group',
        'is_public',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'key' => 'string',
            'type' => 'string',
            'group' => 'string',
            'value' => 'array',
            'is_public' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
