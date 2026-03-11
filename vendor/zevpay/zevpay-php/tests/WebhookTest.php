<?php

declare(strict_types=1);

namespace ZevPay\Tests;

use PHPUnit\Framework\TestCase;
use ZevPay\Exceptions\ZevPayException;
use ZevPay\Webhook;

class WebhookTest extends TestCase
{
    private string $secret = 'whsec_test_secret_123';

    public function testVerifyValidSignature(): void
    {
        $payload = '{"event":"charge.success","data":{"reference":"TXN-123"}}';
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $this->assertTrue(Webhook::verify($payload, $signature, $this->secret));
    }

    public function testVerifyInvalidSignature(): void
    {
        $payload = '{"event":"charge.success","data":{"reference":"TXN-123"}}';

        $this->assertFalse(Webhook::verify($payload, 'invalid_signature', $this->secret));
    }

    public function testVerifyTamperedPayload(): void
    {
        $payload = '{"event":"charge.success","data":{"reference":"TXN-123"}}';
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $tampered = '{"event":"charge.success","data":{"reference":"TXN-FAKE"}}';

        $this->assertFalse(Webhook::verify($tampered, $signature, $this->secret));
    }

    public function testVerifyWrongSecret(): void
    {
        $payload = '{"event":"charge.success","data":{"reference":"TXN-123"}}';
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $this->assertFalse(Webhook::verify($payload, $signature, 'wrong_secret'));
    }

    public function testConstructEventValid(): void
    {
        $payload = '{"event":"charge.success","data":{"reference":"TXN-123","amount":500000}}';
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $event = Webhook::constructEvent($payload, $signature, $this->secret);

        $this->assertEquals('charge.success', $event['event']);
        $this->assertEquals('TXN-123', $event['data']['reference']);
        $this->assertEquals(500000, $event['data']['amount']);
    }

    public function testConstructEventInvalidSignature(): void
    {
        $this->expectException(ZevPayException::class);
        $this->expectExceptionMessage('Invalid webhook signature');

        $payload = '{"event":"charge.success","data":{}}';
        Webhook::constructEvent($payload, 'bad_sig', $this->secret);
    }

    public function testConstructEventInvalidJson(): void
    {
        $payload = 'not valid json';
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $this->expectException(ZevPayException::class);
        $this->expectExceptionMessage('Invalid webhook payload');

        Webhook::constructEvent($payload, $signature, $this->secret);
    }
}
