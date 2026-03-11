<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class ConflictException extends ZevPayException
{
    public function __construct(
        string $message = 'Duplicate resource',
        string $errorCode = 'CONFLICT',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 409, $errorCode, null, $previous);
    }
}
