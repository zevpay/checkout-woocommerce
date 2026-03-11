<?php

declare(strict_types=1);

namespace ZevPay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Resources\Checkout;

class CheckoutTest extends TestCase
{
    private Client $mockClient;
    private Checkout $checkout;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->checkout = new Checkout($this->mockClient);
    }

    public function testInitialize(): void
    {
        $params = [
            'amount' => 500000,
            'email' => 'customer@example.com',
            'currency' => 'NGN',
            'reference' => 'ORDER-123',
        ];

        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/session/initialize', $params)
            ->willReturn(['session_id' => 'ses_abc', 'checkout_url' => 'https://...']);

        $result = $this->checkout->initialize($params);
        $this->assertEquals('ses_abc', $result['session_id']);
    }

    public function testVerify(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/session/ses_abc/verify')
            ->willReturn(['status' => 'completed', 'reference' => 'TXN-123']);

        $result = $this->checkout->verify('ses_abc');
        $this->assertEquals('completed', $result['status']);
    }

    public function testGet(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/session/ses_abc')
            ->willReturn(['session_id' => 'ses_abc', 'status' => 'pending']);

        $result = $this->checkout->get('ses_abc');
        $this->assertEquals('ses_abc', $result['session_id']);
    }

    public function testSelectPaymentMethod(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/session/ses_abc/payment-method', ['payment_type' => 'bank_transfer'])
            ->willReturn(['payment_type' => 'bank_transfer']);

        $result = $this->checkout->selectPaymentMethod('ses_abc', ['payment_type' => 'bank_transfer']);
        $this->assertEquals('bank_transfer', $result['payment_type']);
    }
}
