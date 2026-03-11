<?php

declare(strict_types=1);

namespace ZevPay\Resources;

use ZevPay\Client;

class Wallet
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Get wallet details.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return $this->client->request('GET', '/v1/checkout/wallet');
    }

    /**
     * Update wallet settings.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function update(array $params): array
    {
        return $this->client->request('PUT', '/v1/checkout/wallet', $params);
    }

    /**
     * List wallet members.
     *
     * @param array{page?: int, page_size?: int} $params
     * @return array<string, mixed>
     */
    public function listMembers(array $params = []): array
    {
        return $this->client->request('GET', '/v1/checkout/wallet/members', [], $params);
    }

    /**
     * Add a wallet member.
     *
     * @param array{pay_id: string} $params
     * @return array<string, mixed>
     */
    public function addMember(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/wallet/members', $params);
    }

    /**
     * Remove a wallet member.
     */
    public function removeMember(string $payId): void
    {
        $this->client->request('DELETE', "/v1/checkout/wallet/members/{$payId}");
    }

    /**
     * Update a wallet member.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function updateMember(string $payId, array $params): array
    {
        return $this->client->request('PUT', "/v1/checkout/wallet/members/{$payId}", $params);
    }
}
