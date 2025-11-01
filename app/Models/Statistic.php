<?php

namespace App\Models;

use App\Traits\HasPrefixedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Statistic extends Model
{
    use HasFactory, HasPrefixedUuid, SoftDeletes;

    protected $fillable = [
        'id',
        'model_type',
        'model_id',
        'metric',
        'value',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'metric' => 'string',
            'value' => 'integer',
            'context' => 'array',
            'occurred_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the prefix UUID for this model.
     *
     * @return string
     */
    protected static function getUuidPrefix(): string
    {
        return 'STA';
    }
}
