<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Throwable;

class QbilTradeException extends Exception
{
    private array $responseData;

    public function __construct(
        string $message = '',
        int $code = 0,
        array $responseData = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function isAuthenticationError(): bool
    {
        return $this->code === 401;
    }

    public function isPermissionError(): bool
    {
        return $this->code === 403;
    }

    public function isRateLimitError(): bool
    {
        return $this->code === 429;
    }

    public function isValidationError(): bool
    {
        return in_array($this->code, [400, 422]);
    }
}
