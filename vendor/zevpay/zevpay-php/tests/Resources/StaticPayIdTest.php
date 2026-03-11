<?php

declare(strict_types=1);

namespace ZevPay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Resources\StaticPayId;

class StaticPayIdTest extends TestCase
{
    private Client $mockClient;
    private StaticPayId $staticPayId;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->staticPayId = new StaticPayId($this->mockClient);
    }

    public function testCreate(): void
    {
        $params = ['pay_id' => 'mystore', 'name' => 'My Store'];

        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/static-payid', $params)
            ->willReturn(['id' => 'spid_abc', 'pay_id' => 'mystore']);

        $result = $this->staticPayId->create($params);
        $this->assertEquals('spid_abc', $result['id']);
    }

    public function testDeactivate(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/static-payid/spid_abc/deactivate')
            ->willReturn(['id' => 'spid_abc', 'status' => 'inactive']);

        $result = $this->staticPayId->deactivate('spid_abc');
        $this->assertEquals('inactive', $result['status']);
    }

    public function testReactivate(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/static-payid/spid_abc/reactivate')
            ->willReturn(['id' => 'spid_abc', 'status' => 'active']);

        $result = $this->staticPayId->reactivate('spid_abc');
        $this->assertEquals('active', $result['status']);
    }
}
