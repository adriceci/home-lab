<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LogEngine
{
    /**
     * Log an informational message to the specified channel
     *
     * @param string $channel
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->info($message, $context);
    }

    /**
     * Log a debug message to the specified channel
     *
     * @param string $channel
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->debug($message, $context);
    }

    /**
     * Log a warning message to the specified channel
     *
     * @param string $channel
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->warning($message, $context);
    }

    /**
     * Log an error message to the specified channel
     *
     * @param string $channel
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->error($message, $context);
    }
}

