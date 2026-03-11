<?php

declare(strict_types=1);

namespace ZevPay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Resources\Wallet;

class WalletTest extends TestCase
{
    private Client $mockClient;
    private Wallet $wallet;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->wallet = new Wallet($this->mockClient);
    }

    public function testGet(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/wallet')
            ->willReturn(['pay_id' => 'mystore', 'balance' => 5000000]);

        $result = $this->wallet->get();
        $this->assertEquals('mystore', $result['pay_id']);
    }

    public function testAddMember(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/wallet/members', ['pay_id' => 'johndoe'])
            ->willReturn(['pay_id' => 'johndoe', 'role' => 'member']);

        $result = $this->wallet->addMember(['pay_id' => 'johndoe']);
        $this->assertEquals('johndoe', $result['pay_id']);
    }

    public function testRemoveMember(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('DELETE', '/v1/checkout/wallet/members/johndoe')
            ->willReturn([]);

        $this->wallet->removeMember('johndoe');
    }
}
