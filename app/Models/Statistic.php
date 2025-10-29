<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Statistic extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
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
}
