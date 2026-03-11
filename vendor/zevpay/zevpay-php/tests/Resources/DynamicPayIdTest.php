<?php

declare(strict_types=1);

namespace ZevPay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Resources\DynamicPayId;

class DynamicPayIdTest extends TestCase
{
    private Client $mockClient;
    private DynamicPayId $dynamicPayId;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->dynamicPayId = new DynamicPayId($this->mockClient);
    }

    public function testCreate(): void
    {
        $params = ['amount' => 1000000, 'name' => 'Donation Drive'];

        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/dynamic-payid', $params)
            ->willReturn(['id' => 'dpid_abc', 'full_pay_id' => 'donation-drive-xyz.dpay']);

        $result = $this->dynamicPayId->create($params);
        $this->assertEquals('dpid_abc', $result['id']);
    }

    public function testDeactivate(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/dynamic-payid/dpid_abc/deactivate')
            ->willReturn(['id' => 'dpid_abc', 'status' => 'inactive']);

        $result = $this->dynamicPayId->deactivate('dpid_abc');
        $this->assertEquals('inactive', $result['status']);
    }
}
