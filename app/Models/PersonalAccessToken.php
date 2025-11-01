<?php

namespace App\Models;

use App\Traits\HasPrefixedUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasPrefixedUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'abilities' => 'json',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
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
        return 'PAT';
    }
}
