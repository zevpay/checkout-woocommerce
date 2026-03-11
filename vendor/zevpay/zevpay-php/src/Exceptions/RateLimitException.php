<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class RateLimitException extends ZevPayException
{
    public function __construct(
        string $message = 'Too many requests',
        string $errorCode = 'RATE_LIMIT_EXCEEDED',
        public readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $errorCode, null, $previous);
    }
}
