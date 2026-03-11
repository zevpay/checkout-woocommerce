<?php

declare(strict_types=1);

namespace ZevPay\Resources;

use ZevPay\Client;

class Transfers
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Create a transfer.
     *
     * @param array{
     *   type: string,
     *   amount: int,
     *   account_number?: string,
     *   bank_code?: string,
     *   account_name?: string,
     *   pay_id?: string,
     *   narration?: string,
     *   reference?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/transfer', $params);
    }

    /**
     * List transfers.
     *
     * @param array{
     *   page?: int,
     *   page_size?: int,
     *   status?: string,
     *   type?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/v1/checkout/transfer', [], $params);
    }

    /**
     * Get a transfer by reference.
     *
     * @return array<string, mixed>
     */
    public function get(string $reference): array
    {
        return $this->client->request('GET', "/v1/checkout/transfer/{$reference}");
    }

    /**
     * Verify a transfer by reference.
     *
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        return $this->client->request('GET', "/v1/checkout/transfer/{$reference}/verify");
    }

    /**
     * List available banks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBanks(): array
    {
        return $this->client->request('GET', '/v1/checkout/transfer/banks');
    }

    /**
     * Resolve a bank account.
     *
     * @param array{account_number: string, bank_code: string} $params
     * @return array<string, mixed>
     */
    public function resolveAccount(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/transfer/resolve-account', $params);
    }

    /**
     * Calculate transfer charges.
     *
     * @param array{amount: int, bank_code?: string} $params
     * @return array<string, mixed>
     */
    public function calculateCharges(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/transfer/calculate-charges', $params);
    }

    /**
     * Get wallet balance.
     *
     * @return array<string, mixed>
     */
    public function getBalance(): array
    {
        return $this->client->request('GET', '/v1/checkout/transfer/balance');
    }
}
