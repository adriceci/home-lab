<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'old_values' => 'array',
            'new_values' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that was affected by the action.
     */
    public function model()
    {
        if ($this->model_type && $this->model_id) {
            return $this->belongsTo($this->model_type, 'model_id');
        }

        return null;
    }

    /**
     * Create an audit log entry.
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?string $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?string $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $url = null,
        ?string $method = null
    ): self {
        return self::create([
            'user_id' => $userId ?? Auth::user()->id ?? null,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'url' => $url ?? request()->url(),
            'method' => $method ?? request()->method(),
            'description' => $description,
        ]);
    }
}
