<?php

declare(strict_types=1);

namespace ZevPay\Resources;

use ZevPay\Client;

class DynamicPayId
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Create a dynamic PayID.
     *
     * @param array{
     *   amount: int,
     *   name: string,
     *   expires_in_minutes?: int,
     *   amount_validation?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/dynamic-payid', $params);
    }

    /**
     * List dynamic PayIDs.
     *
     * @param array{page?: int, page_size?: int, status?: string} $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/v1/checkout/dynamic-payid', [], $params);
    }

    /**
     * Get a dynamic PayID.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->request('GET', "/v1/checkout/dynamic-payid/{$id}");
    }

    /**
     * Deactivate a dynamic PayID.
     *
     * @return array<string, mixed>
     */
    public function deactivate(string $id): array
    {
        return $this->client->request('POST', "/v1/checkout/dynamic-payid/{$id}/deactivate");
    }
}
