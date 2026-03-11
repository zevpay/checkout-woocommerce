<?php

declare(strict_types=1);

namespace ZevPay\Resources;

use ZevPay\Client;

class Invoices
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Create an invoice.
     *
     * @param array{
     *   customer_name: string,
     *   customer_email: string,
     *   due_date: string,
     *   line_items: array<int, array{description: string, quantity: int, unit_price: int}>,
     *   tax_rate?: float,
     *   note?: string,
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/invoice', $params);
    }

    /**
     * List invoices.
     *
     * @param array{page?: int, page_size?: int, status?: string} $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/v1/checkout/invoice', [], $params);
    }

    /**
     * Get an invoice by public ID.
     *
     * @return array<string, mixed>
     */
    public function get(string $publicId): array
    {
        return $this->client->request('GET', "/v1/checkout/invoice/{$publicId}");
    }

    /**
     * Update an invoice.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function update(string $publicId, array $params): array
    {
        return $this->client->request('PUT', "/v1/checkout/invoice/{$publicId}", $params);
    }

    /**
     * Send an invoice (draft → sent).
     *
     * @return array<string, mixed>
     */
    public function send(string $publicId): array
    {
        return $this->client->request('POST', "/v1/checkout/invoice/{$publicId}/send");
    }

    /**
     * Cancel an invoice.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $publicId): array
    {
        return $this->client->request('POST', "/v1/checkout/invoice/{$publicId}/cancel");
    }
}
