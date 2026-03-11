<?php

declare(strict_types=1);

namespace ZevPay\Resources;

use ZevPay\Client;

class Checkout
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Initialize a checkout session.
     *
     * @param array{
     *   amount: int,
     *   email: string,
     *   currency?: string,
     *   reference?: string,
     *   callback_url?: string,
     *   customer_name?: string,
     *   metadata?: array<string, mixed>,
     *   payment_methods?: string[],
     * } $params
     * @return array<string, mixed>
     */
    public function initialize(array $params): array
    {
        return $this->client->request('POST', '/v1/checkout/session/initialize', $params);
    }

    /**
     * Select a payment method for a session.
     *
     * @param array{payment_type: string} $params
     * @return array<string, mixed>
     */
    public function selectPaymentMethod(string $sessionId, array $params): array
    {
        return $this->client->request('POST', "/v1/checkout/session/{$sessionId}/payment-method", $params);
    }

    /**
     * Verify a checkout session payment.
     *
     * @return array<string, mixed>
     */
    public function verify(string $sessionId): array
    {
        return $this->client->request('GET', "/v1/checkout/session/{$sessionId}/verify");
    }

    /**
     * Get checkout session details.
     *
     * @return array<string, mixed>
     */
    public function get(string $sessionId): array
    {
        return $this->client->request('GET', "/v1/checkout/session/{$sessionId}");
    }
}
