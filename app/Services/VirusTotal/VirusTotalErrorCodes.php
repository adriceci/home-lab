<?php

namespace App\Services\VirusTotal;

/**
 * VirusTotal API Error Codes and HTTP Status Codes
 * 
 * Based on VirusTotal API documentation:
 * https://developers.virustotal.com/reference/errors
 */
class VirusTotalErrorCodes
{
    // HTTP Status Codes
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_CONFLICT = 409;
    public const HTTP_FAILED_DEPENDENCY = 424;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;

    // Error Codes
    public const ERROR_BAD_REQUEST = 'BadRequestError';
    public const ERROR_INVALID_ARGUMENT = 'InvalidArgumentError';
    public const ERROR_NOT_AVAILABLE_YET = 'NotAvailableYet';
    public const ERROR_UNSELECTIVE_CONTENT_QUERY = 'UnselectiveContentQueryError';
    public const ERROR_UNSUPPORTED_CONTENT_QUERY = 'UnsupportedContentQueryError';
    public const ERROR_AUTHENTICATION_REQUIRED = 'AuthenticationRequiredError';
    public const ERROR_USER_NOT_ACTIVE = 'UserNotActiveError';
    public const ERROR_WRONG_CREDENTIALS = 'WrongCredentialsError';
    public const ERROR_FORBIDDEN = 'ForbiddenError';
    public const ERROR_NOT_FOUND = 'NotFoundError';
    public const ERROR_ALREADY_EXISTS = 'AlreadyExistsError';
    public const ERROR_FAILED_DEPENDENCY = 'FailedDependencyError';
    public const ERROR_QUOTA_EXCEEDED = 'QuotaExceededError';
    public const ERROR_TOO_MANY_REQUESTS = 'TooManyRequestsError';
    public const ERROR_TRANSIENT = 'TransientError';
    public const ERROR_DEADLINE_EXCEEDED = 'DeadlineExceededError';

    /**
     * Map HTTP status codes to error codes
     * 
     * @return array<int, array<string>>
     */
    public static function getHttpCodeToErrorCodes(): array
    {
        return [
            self::HTTP_BAD_REQUEST => [
                self::ERROR_BAD_REQUEST,
                self::ERROR_INVALID_ARGUMENT,
                self::ERROR_NOT_AVAILABLE_YET,
                self::ERROR_UNSELECTIVE_CONTENT_QUERY,
                self::ERROR_UNSUPPORTED_CONTENT_QUERY,
            ],
            self::HTTP_UNAUTHORIZED => [
                self::ERROR_AUTHENTICATION_REQUIRED,
                self::ERROR_USER_NOT_ACTIVE,
                self::ERROR_WRONG_CREDENTIALS,
            ],
            self::HTTP_FORBIDDEN => [
                self::ERROR_FORBIDDEN,
            ],
            self::HTTP_NOT_FOUND => [
                self::ERROR_NOT_FOUND,
            ],
            self::HTTP_CONFLICT => [
                self::ERROR_ALREADY_EXISTS,
            ],
            self::HTTP_FAILED_DEPENDENCY => [
                self::ERROR_FAILED_DEPENDENCY,
            ],
            self::HTTP_TOO_MANY_REQUESTS => [
                self::ERROR_QUOTA_EXCEEDED,
                self::ERROR_TOO_MANY_REQUESTS,
            ],
            self::HTTP_SERVICE_UNAVAILABLE => [
                self::ERROR_TRANSIENT,
            ],
            self::HTTP_GATEWAY_TIMEOUT => [
                self::ERROR_DEADLINE_EXCEEDED,
            ],
        ];
    }

    /**
     * Get error code description
     * 
     * @param string $errorCode
     * @return string
     */
    public static function getErrorDescription(string $errorCode): string
    {
        $descriptions = [
            self::ERROR_BAD_REQUEST => 'The API request is invalid or malformed. The message usually provides details about why the request is not valid.',
            self::ERROR_INVALID_ARGUMENT => 'Some of the provided arguments are incorrect.',
            self::ERROR_NOT_AVAILABLE_YET => 'The resource is not available yet, but will become available later.',
            self::ERROR_UNSELECTIVE_CONTENT_QUERY => 'Content search query is not selective enough.',
            self::ERROR_UNSUPPORTED_CONTENT_QUERY => 'Unsupported content search query.',
            self::ERROR_AUTHENTICATION_REQUIRED => 'The operation requires an authenticated user. Verify that you have provided your API key.',
            self::ERROR_USER_NOT_ACTIVE => 'The user account is not active. Make sure you properly activated your account by following the link sent to your email.',
            self::ERROR_WRONG_CREDENTIALS => 'The provided API key is incorrect.',
            self::ERROR_FORBIDDEN => 'You are not allowed to perform the requested operation.',
            self::ERROR_NOT_FOUND => 'The requested resource was not found.',
            self::ERROR_ALREADY_EXISTS => 'The resource already exists.',
            self::ERROR_FAILED_DEPENDENCY => 'The request depended on another request and that request failed.',
            self::ERROR_QUOTA_EXCEEDED => 'You have exceeded one of your quotas (minute, daily or monthly). Daily quotas are reset every day at 00:00 UTC. You may have run out of disk space and/or number of files on your VirusTotal Monitor account.',
            self::ERROR_TOO_MANY_REQUESTS => 'Too many requests.',
            self::ERROR_TRANSIENT => 'Transient server error. Retry might work.',
            self::ERROR_DEADLINE_EXCEEDED => 'The operation took too long to complete.',
        ];

        return $descriptions[$errorCode] ?? 'Unknown error code.';
    }

    /**
     * Map error code to database status value
     * 
     * @param string $errorCode
     * @return string
     */
    public static function errorCodeToStatus(string $errorCode): string
    {
        return match ($errorCode) {
            self::ERROR_QUOTA_EXCEEDED, self::ERROR_TOO_MANY_REQUESTS => 'quota_exceeded',
            self::ERROR_AUTHENTICATION_REQUIRED, self::ERROR_USER_NOT_ACTIVE, self::ERROR_WRONG_CREDENTIALS => 'authentication_error',
            self::ERROR_NOT_FOUND => 'not_found',
            self::ERROR_FORBIDDEN => 'forbidden',
            self::ERROR_DEADLINE_EXCEEDED => 'timeout',
            self::ERROR_TRANSIENT => 'transient_error',
            self::ERROR_FAILED_DEPENDENCY => 'dependency_error',
            self::ERROR_ALREADY_EXISTS => 'already_exists',
            self::ERROR_BAD_REQUEST, self::ERROR_INVALID_ARGUMENT, self::ERROR_UNSELECTIVE_CONTENT_QUERY, self::ERROR_UNSUPPORTED_CONTENT_QUERY => 'bad_request',
            self::ERROR_NOT_AVAILABLE_YET => 'not_available',
            default => 'error',
        };
    }

    /**
     * Check if error code is retryable
     * 
     * @param string $errorCode
     * @return bool
     */
    public static function isRetryable(string $errorCode): bool
    {
        return in_array($errorCode, [
            self::ERROR_TRANSIENT,
            self::ERROR_DEADLINE_EXCEEDED,
            self::ERROR_TOO_MANY_REQUESTS, // Can retry after rate limit period
            self::ERROR_FAILED_DEPENDENCY,
            self::ERROR_NOT_AVAILABLE_YET, // Resource not available yet, but will become available later
        ]);
    }
}
