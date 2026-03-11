<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class ApiException extends ZevPayException
{
    public function __construct(
        string $message = 'Internal server error',
        int $statusCode = 500,
        string $errorCode = 'API_ERROR',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $errorCode, null, $previous);
    }
}
