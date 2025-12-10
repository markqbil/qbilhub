<?php

declare(strict_types=1);

namespace App\Service;

class PythonServiceException extends \RuntimeException
{
    public const ERROR_CONNECTION = 'connection';
    public const ERROR_SCHEMA_EXTRACTION = 'schema_extraction';
    public const ERROR_ENTITY_RESOLUTION = 'entity_resolution';
    public const ERROR_FEEDBACK = 'feedback';

    private string $errorType;
    private int $httpStatusCode;

    public function __construct(
        string $message,
        string $errorType,
        int $httpStatusCode = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorType = $errorType;
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function isConnectionError(): bool
    {
        return $this->errorType === self::ERROR_CONNECTION;
    }

    public function isRetryable(): bool
    {
        // Connection errors and certain HTTP status codes are retryable
        return $this->isConnectionError()
            || $this->httpStatusCode === 503
            || $this->httpStatusCode === 502
            || $this->httpStatusCode === 504;
    }
}
