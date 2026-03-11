<?php

declare(strict_types=1);

namespace ZevPay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Resources\VirtualAccounts;

class VirtualAccountsTest extends TestCase
{
    private Client $mockClient;
    private VirtualAccounts $virtualAccounts;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->virtualAccounts = new VirtualAccounts($this->mockClient);
    }

    public function testCreate(): void
    {
        $params = ['amount' => 1000000, 'validity_minutes' => 60];

        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/virtual-account', $params)
            ->willReturn(['account_number' => '1234567890', 'bank_name' => 'Test Bank']);

        $result = $this->virtualAccounts->create($params);
        $this->assertEquals('1234567890', $result['account_number']);
    }

    public function testList(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/virtual-account', [], ['status' => 'pending'])
            ->willReturn(['items' => [], 'total' => 0]);

        $result = $this->virtualAccounts->list(['status' => 'pending']);
        $this->assertArrayHasKey('items', $result);
    }
}
