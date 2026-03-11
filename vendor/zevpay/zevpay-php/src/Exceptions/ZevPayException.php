<?php

declare(strict_types=1);

namespace ZevPay\Exceptions;

class ZevPayException extends \Exception
{
    public function __construct(
        string $message = '',
        public readonly int $statusCode = 0,
        public readonly string $errorCode = '',
        public readonly ?array $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
