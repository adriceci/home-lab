<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPrefixedUuid
{
    /**
     * Boot the trait.
     * Laravel automatically calls boot{TraitName} methods when a trait is used.
     * This MUST be public static for Laravel to call it.
     */
    public static function bootHasPrefixedUuid(): void
    {
        static::creating(function (Model $model): void {
            $keyName = $model->getKeyName();
            
            // Check if ID is not set using multiple methods to be safe
            $currentId = $model->getAttribute($keyName) ?? $model->{$keyName} ?? null;

            // Generate UUID if not set, null, or empty string
            if (empty($currentId)) {
                $uuid = static::generatePrefixedUuid();
                $model->setAttribute($keyName, $uuid);
                $model->{$keyName} = $uuid;
            }
        });
    }

    protected static function hasCorrectPrefix(string $id): bool
    {
        $prefix = static::getUuidPrefix();
        return str_starts_with($id, $prefix . '-');
    }

    public static function generatePrefixedUuid(): string
    {
        $prefix = static::getUuidPrefix();
        $uuid = (string) Str::uuid();
        return $prefix . '-' . substr($uuid, strlen($prefix) + 1);
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

    abstract protected static function getUuidPrefix(): string;
}
