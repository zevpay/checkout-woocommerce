<?php

declare(strict_types=1);

namespace ZevPay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Resources\Invoices;

class InvoicesTest extends TestCase
{
    private Client $mockClient;
    private Invoices $invoices;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->invoices = new Invoices($this->mockClient);
    }

    public function testCreate(): void
    {
        $params = [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'due_date' => '2026-04-01',
            'line_items' => [
                ['description' => 'Web Design', 'quantity' => 1, 'unit_price' => 5000000],
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/invoice', $params)
            ->willReturn(['public_id' => 'inv_abc', 'status' => 'draft']);

        $result = $this->invoices->create($params);
        $this->assertEquals('inv_abc', $result['public_id']);
    }

    public function testList(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/invoice', [], ['status' => 'sent'])
            ->willReturn(['items' => [], 'total' => 0]);

        $result = $this->invoices->list(['status' => 'sent']);
        $this->assertArrayHasKey('items', $result);
    }

    public function testSend(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/invoice/inv_abc/send')
            ->willReturn(['public_id' => 'inv_abc', 'status' => 'sent']);

        $result = $this->invoices->send('inv_abc');
        $this->assertEquals('sent', $result['status']);
    }

    public function testCancel(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/invoice/inv_abc/cancel')
            ->willReturn(['public_id' => 'inv_abc', 'status' => 'cancelled']);

        $result = $this->invoices->cancel('inv_abc');
        $this->assertEquals('cancelled', $result['status']);
    }
}
