<?php

declare(strict_types=1);

namespace ZevPay\Resources;

use ZevPay\Client;

class VirtualAccounts
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Create a virtual account.
     *
     * @param array{
     *   amount: int,
     *   validity_minutes?: int,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/virtual-account', $params);
    }

    /**
     * List virtual accounts.
     *
     * @param array{page?: int, page_size?: int, status?: string} $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/v1/checkout/virtual-account', [], $params);
    }

    /**
     * Get a virtual account.
     *
     * @return array<string, mixed>
     */
    public function get(string $publicId): array
    {
        return $this->client->request('GET', "/v1/checkout/virtual-account/{$publicId}");
    }
}
