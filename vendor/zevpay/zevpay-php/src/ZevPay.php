<?php

declare(strict_types=1);

namespace ZevPay;

use ZevPay\Exceptions\AuthenticationException;
use ZevPay\Resources\Checkout;
use ZevPay\Resources\DynamicPayId;
use ZevPay\Resources\Invoices;
use ZevPay\Resources\StaticPayId;
use ZevPay\Resources\Transfers;
use ZevPay\Resources\VirtualAccounts;
use ZevPay\Resources\Wallet;

class ZevPay
{
    public readonly Checkout $checkout;
    public readonly Transfers $transfers;
    public readonly Invoices $invoices;
    public readonly StaticPayId $staticPayId;
    public readonly DynamicPayId $dynamicPayId;
    public readonly VirtualAccounts $virtualAccounts;
    public readonly Wallet $wallet;

    /**
     * @param string $secretKey  Your secret API key (sk_live_* or sk_test_*)
     * @param array{
     *   base_url?: string,
     *   timeout?: int,
     *   max_retries?: int,
     * } $options
     * @throws AuthenticationException  If the key doesn't start with sk_
     */
    public function __construct(string $secretKey, array $options = [])
    {
        if (!str_starts_with($secretKey, 'sk_')) {
            throw new AuthenticationException(
                'Invalid API key. Use a secret key (sk_live_* or sk_test_*). Public keys cannot be used server-side.',
                'INVALID_KEY',
            );
        }

        $client = new Client($secretKey, $options);

        $this->checkout = new Checkout($client);
        $this->transfers = new Transfers($client);
        $this->invoices = new Invoices($client);
        $this->staticPayId = new StaticPayId($client);
        $this->dynamicPayId = new DynamicPayId($client);
        $this->virtualAccounts = new VirtualAccounts($client);
        $this->wallet = new Wallet($client);
    }
}
