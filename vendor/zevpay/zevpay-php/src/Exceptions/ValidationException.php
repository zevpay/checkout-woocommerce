<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class ValidationException extends ZevPayException
{
    public function __construct(
        string $message = 'Validation failed',
        string $errorCode = 'VALIDATION_ERROR',
        ?array $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 400, $errorCode, $details, $previous);
    }
}
