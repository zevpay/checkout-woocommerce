<?php

declare(strict_types=1);

namespace ZevPay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Resources\Transfers;

class TransfersTest extends TestCase
{
    private Client $mockClient;
    private Transfers $transfers;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->transfers = new Transfers($this->mockClient);
    }

    public function testCreate(): void
    {
        $params = [
            'type' => 'bank_transfer',
            'amount' => 1000000,
            'account_number' => '0123456789',
            'bank_code' => '044',
            'account_name' => 'John Doe',
            'narration' => 'Payout',
        ];

        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/transfer', $params)
            ->willReturn(['reference' => 'TXN-123', 'status' => 'pending']);

        $result = $this->transfers->create($params);
        $this->assertEquals('TXN-123', $result['reference']);
    }

    public function testList(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/transfer', [], ['page' => 1, 'status' => 'completed'])
            ->willReturn(['items' => [], 'total' => 0]);

        $result = $this->transfers->list(['page' => 1, 'status' => 'completed']);
        $this->assertArrayHasKey('items', $result);
    }

    public function testVerify(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/transfer/TXN-123/verify')
            ->willReturn(['reference' => 'TXN-123', 'status' => 'completed']);

        $result = $this->transfers->verify('TXN-123');
        $this->assertEquals('completed', $result['status']);
    }

    public function testListBanks(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/transfer/banks')
            ->willReturn([['code' => '044', 'name' => 'Access Bank']]);

        $result = $this->transfers->listBanks();
        $this->assertEquals('044', $result[0]['code']);
    }

    public function testResolveAccount(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/transfer/resolve-account', [
                'account_number' => '0123456789',
                'bank_code' => '044',
            ])
            ->willReturn(['account_name' => 'JOHN DOE']);

        $result = $this->transfers->resolveAccount([
            'account_number' => '0123456789',
            'bank_code' => '044',
        ]);
        $this->assertEquals('JOHN DOE', $result['account_name']);
    }

    public function testCalculateCharges(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/checkout/transfer/calculate-charges', ['amount' => 1000000])
            ->willReturn(['fee' => 5000, 'total' => 1005000]);

        $result = $this->transfers->calculateCharges(['amount' => 1000000]);
        $this->assertEquals(5000, $result['fee']);
    }

    public function testGetBalance(): void
    {
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/checkout/transfer/balance')
            ->willReturn(['available_balance' => 5000000, 'currency' => 'NGN']);

        $result = $this->transfers->getBalance();
        $this->assertEquals(5000000, $result['available_balance']);
    }
}
