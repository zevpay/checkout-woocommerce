<?php

declare(strict_types=1);

namespace ZevPay;

use ZevPay\Exceptions\ZevPayException;

class Webhook
{
    /**
     * Verify a webhook signature.
     *
     * @param string $payload  Raw request body
     * @param string $signature  Value from the x-zevpay-signature header
     * @param string $secret  Your webhook secret
     */
    public static function verify(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Verify the signature and parse the webhook event.
     *
     * @param string $payload  Raw request body
     * @param string $signature  Value from the x-zevpay-signature header
     * @param string $secret  Your webhook secret
     * @return array<string, mixed>  Parsed event data
     * @throws ZevPayException  If the signature is invalid
     */
    public static function constructEvent(string $payload, string $signature, string $secret): array
    {
        if (!self::verify($payload, $signature, $secret)) {
            throw new ZevPayException('Invalid webhook signature', 400, 'WEBHOOK_SIGNATURE_INVALID');
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new ZevPayException('Invalid webhook payload', 400, 'WEBHOOK_PAYLOAD_INVALID');
        }

        return $event;
    }
}
