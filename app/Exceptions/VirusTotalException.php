<?php

namespace App\Exceptions;

use Exception;

/**
 * Base exception for VirusTotal API errors
 */
class VirusTotalException extends Exception
{
    protected int $httpCode;
    protected string $errorCode;
    protected ?array $errorData;

    public function __construct(
        string $message = '',
        int $httpCode = 0,
        string $errorCode = '',
        ?array $errorData = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->httpCode = $httpCode;
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Check if this error is retryable
     * 
     * @return bool
     */
    public function isRetryable(): bool
    {
        return \App\Services\VirusTotal\VirusTotalErrorCodes::isRetryable($this->errorCode);
    }
}
