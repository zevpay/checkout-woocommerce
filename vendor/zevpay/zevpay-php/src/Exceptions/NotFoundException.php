<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class NotFoundException extends ZevPayException
{
    public function __construct(
        string $message = 'Resource not found',
        string $errorCode = 'NOT_FOUND',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 404, $errorCode, null, $previous);
    }
}
