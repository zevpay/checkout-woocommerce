<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class AuthenticationException extends ZevPayException
{
    public function __construct(
        string $message = 'Invalid or missing API key',
        string $errorCode = 'AUTHENTICATION_ERROR',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 401, $errorCode, null, $previous);
    }
}
