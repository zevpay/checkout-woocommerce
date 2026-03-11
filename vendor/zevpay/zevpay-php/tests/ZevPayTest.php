<?php

declare(strict_types=1);

namespace ZevPay\Tests;

use PHPUnit\Framework\TestCase;
use ZevPay\Exceptions\AuthenticationException;
use ZevPay\Resources\Checkout;
use ZevPay\Resources\DynamicPayId;
use ZevPay\Resources\Invoices;
use ZevPay\Resources\StaticPayId;
use ZevPay\Resources\Transfers;
use ZevPay\Resources\VirtualAccounts;
use ZevPay\Resources\Wallet;
use ZevPay\ZevPay;

class ZevPayTest extends TestCase
{
    public function testConstructorWithValidKey(): void
    {
        $zevpay = new ZevPay('sk_test_abc123');

        $this->assertInstanceOf(Checkout::class, $zevpay->checkout);
        $this->assertInstanceOf(Transfers::class, $zevpay->transfers);
        $this->assertInstanceOf(Invoices::class, $zevpay->invoices);
        $this->assertInstanceOf(StaticPayId::class, $zevpay->staticPayId);
        $this->assertInstanceOf(DynamicPayId::class, $zevpay->dynamicPayId);
        $this->assertInstanceOf(VirtualAccounts::class, $zevpay->virtualAccounts);
        $this->assertInstanceOf(Wallet::class, $zevpay->wallet);
    }

    public function testConstructorRejectsPublicKey(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');

        new ZevPay('pk_test_abc123');
    }

    public function testConstructorRejectsEmptyKey(): void
    {
        $this->expectException(AuthenticationException::class);

        new ZevPay('');
    }

    public function testConstructorRejectsRandomKey(): void
    {
        $this->expectException(AuthenticationException::class);

        new ZevPay('random_key_123');
    }

    public function testConstructorWithLiveKey(): void
    {
        $zevpay = new ZevPay('sk_live_abc123');
        $this->assertInstanceOf(Checkout::class, $zevpay->checkout);
    }
}
