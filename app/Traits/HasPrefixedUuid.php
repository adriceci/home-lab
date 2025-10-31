<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

trait HasPrefixedUuid
{
    use HasUuids;

    /**
     * Generate a new UUID for the model with the model's prefix.
     *
     * @return string
     */
    public function newUniqueId(): string
    {
        return $this->createUuid();
    }

    /**
     * Create a UUID with the model's prefix.
     * Replaces the first N characters of a standard UUID with the prefix,
     * where N is the length of the prefix (3-4 characters).
     *
     * @return string
     */
    protected function createUuid(): string
    {
        $prefix = strtoupper($this->getPrefixUuid());
        
        // Validate prefix length
        $prefixLength = strlen($prefix);
        if ($prefixLength < 3 || $prefixLength > 4) {
            throw new \InvalidArgumentException(
                "Prefix UUID must be between 3 and 4 characters. Got: '{$prefix}' ({$prefixLength} chars)"
            );
        }

        // Generate standard UUID (format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
        $uuid = (string) Str::uuid();
        
        // Remove hyphens to replace characters more easily
        $uuidWithoutHyphens = str_replace('-', '', $uuid);
        
        // Replace first N characters with prefix
        $prefixedUuid = $prefix . substr($uuidWithoutHyphens, $prefixLength);
        
        // Restore UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        // Format: 8-4-4-4-12 characters
        $formattedUuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr($prefixedUuid, 0, 8),
            substr($prefixedUuid, 8, 4),
            substr($prefixedUuid, 12, 4),
            substr($prefixedUuid, 16, 4),
            substr($prefixedUuid, 20)
        );
        
        return $formattedUuid;
    }

    /**
     * Get the prefix UUID for this model.
     * Must be implemented by the model and return a string of 3-4 uppercase characters.
     *
     * @return string
     */
    abstract public function getPrefixUuid(): string;
}

