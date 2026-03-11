<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class PermissionException extends ZevPayException
{
    public function __construct(
        string $message = 'Insufficient permissions',
        string $errorCode = 'PERMISSION_ERROR',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 403, $errorCode, null, $previous);
    }
}
