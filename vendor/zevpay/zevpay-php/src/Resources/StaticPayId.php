<?php

declare(strict_types=1);

namespace ZevPay\Resources;

use ZevPay\Client;

class StaticPayId
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Create a static PayID.
     *
     * @param array{
     *   pay_id: string,
     *   suffix?: string,
     *   name: string,
     *   description?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/static-payid', $params);
    }

    /**
     * List static PayIDs.
     *
     * @param array{page?: int, page_size?: int, status?: string} $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/v1/checkout/static-payid', [], $params);
    }

    /**
     * Get a static PayID.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->client->request('GET', "/v1/checkout/static-payid/{$id}");
    }

    /**
     * Update a static PayID.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function update(string $id, array $params): array
    {
        return $this->client->request('PUT', "/v1/checkout/static-payid/{$id}", $params);
    }

    /**
     * Deactivate a static PayID.
     *
     * @return array<string, mixed>
     */
    public function deactivate(string $id): array
    {
        return $this->client->request('POST', "/v1/checkout/static-payid/{$id}/deactivate");
    }

    /**
     * Reactivate a static PayID.
     *
     * @return array<string, mixed>
     */
    public function reactivate(string $id): array
    {
        return $this->client->request('POST', "/v1/checkout/static-payid/{$id}/reactivate");
    }
}
