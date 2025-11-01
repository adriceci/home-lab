<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPrefixedUuid
{
    protected static function bootHasPrefixedUuid()
    {
        static::creating(function (Model $model) {
            $keyName = $model->getKeyName();
            $currentId = $model->{$keyName};
            $prefix = strtoupper(static::getUuidPrefix());

            // Only generate a new UUID if:
            // 1. No ID is set, OR
            // 2. ID is set but doesn't have the correct prefix
            if (empty($currentId) || !static::hasCorrectPrefix($currentId, $prefix)) {
                $newId = static::generatePrefixedUuid($prefix);
                $model->{$keyName} = $newId;
            }
        });
    }

    protected static function hasCorrectPrefix(string $id, string $prefix): bool
    {
        return str_starts_with($id, $prefix . '-');
    }

    public static function generatePrefixedUuid(?string $prefix = null): string
    {
        if ($prefix === null) {
            $prefix = strtoupper(static::getUuidPrefix());
        }

        // Validate prefix length
        $prefixLength = strlen($prefix);
        if ($prefixLength < 3 || $prefixLength > 4) {
            throw new \InvalidArgumentException(
                "Prefix UUID must be between 3 and 4 characters. Got: '{$prefix}' ({$prefixLength} chars)"
            );
        }

        // Generate standard UUID
        $uuid = (string) Str::uuid();

        // Return format: PREFIX-UUID_COMPLETO
        // Example: ACC-0001-041f-487a-a626-5ce20f0d88e2
        return $prefix . '-' . substr($uuid, $prefixLength + 1);
    }

    public function initializeHasPrefixedUuid()
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    /**
     * Get the prefix UUID for this model.
     * Must return a string of 3-4 characters.
     */
    abstract protected static function getUuidPrefix(): string;
}
