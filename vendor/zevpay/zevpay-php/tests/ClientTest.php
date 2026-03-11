<?php

declare(strict_types=1);

namespace ZevPay\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ZevPay\Client;
use ZevPay\Exceptions\AuthenticationException;
use ZevPay\Exceptions\ConflictException;
use ZevPay\Exceptions\NotFoundException;
use ZevPay\Exceptions\PermissionException;
use ZevPay\Exceptions\RateLimitException;
use ZevPay\Exceptions\ValidationException;
use ZevPay\Exceptions\ApiException;

class ClientTest extends TestCase
{
    public function testValidationErrorThrown(): void
    {
        $client = $this->createClientWithResponse(400, [
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Amount is required',
                'details' => ['amount' => 'Field is required'],
            ],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amount is required');

        $client->request('POST', '/v1/checkout/session/initialize');
    }

    public function testAuthenticationErrorThrown(): void
    {
        $client = $this->createClientWithResponse(401, [
            'success' => false,
            'error' => ['code' => 'INVALID_KEY', 'message' => 'Invalid API key'],
        ]);

        $this->expectException(AuthenticationException::class);
        $client->request('GET', '/v1/checkout/session/test');
    }

    public function testPermissionErrorThrown(): void
    {
        $client = $this->createClientWithResponse(403, [
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Forbidden'],
        ]);

        $this->expectException(PermissionException::class);
        $client->request('GET', '/v1/checkout/wallet');
    }

    public function testNotFoundErrorThrown(): void
    {
        $client = $this->createClientWithResponse(404, [
            'success' => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Session not found'],
        ]);

        $this->expectException(NotFoundException::class);
        $client->request('GET', '/v1/checkout/session/invalid');
    }

    public function testConflictErrorThrown(): void
    {
        $client = $this->createClientWithResponse(409, [
            'success' => false,
            'error' => ['code' => 'CONFLICT', 'message' => 'Duplicate reference'],
        ]);

        $this->expectException(ConflictException::class);
        $client->request('POST', '/v1/checkout/transfer');
    }

    public function testRateLimitErrorThrown(): void
    {
        $client = $this->createClientWithResponse(429, [
            'success' => false,
            'error' => ['code' => 'RATE_LIMIT', 'message' => 'Too many requests'],
        ], ['Retry-After' => '30']);

        try {
            $client->request('GET', '/v1/checkout/session/test');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(30, $e->retryAfter);
            $this->assertEquals(429, $e->statusCode);
        }
    }

    public function testApiErrorThrown(): void
    {
        $client = $this->createClientWithResponse(500, [
            'success' => false,
            'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error'],
        ]);

        $this->expectException(ApiException::class);
        $client->request('GET', '/v1/checkout/session/test');
    }

    public function testSuccessfulResponse(): void
    {
        $client = $this->createClientWithResponse(200, [
            'success' => true,
            'data' => [
                'session_id' => 'ses_abc123',
                'status' => 'pending',
            ],
        ]);

        $result = $client->request('GET', '/v1/checkout/session/ses_abc123');

        $this->assertEquals('ses_abc123', $result['session_id']);
        $this->assertEquals('pending', $result['status']);
    }

    public function testValidationErrorIncludesDetails(): void
    {
        $client = $this->createClientWithResponse(400, [
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => [
                    'email' => 'Email is required',
                    'amount' => 'Amount must be positive',
                ],
            ],
        ]);

        try {
            $client->request('POST', '/v1/checkout/session/initialize');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotNull($e->details);
            $this->assertEquals('Email is required', $e->details['email']);
            $this->assertEquals('Amount must be positive', $e->details['amount']);
        }
    }

    /**
     * Helper to create a Client with a mocked Guzzle handler.
     */
    private function createClientWithResponse(int $status, array $body, array $headers = []): Client
    {
        $mock = new MockHandler([
            new Response($status, $headers, json_encode($body)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient([
            'handler' => $handlerStack,
            'http_errors' => false,
        ]);

        // Use reflection to inject the mock Guzzle client
        $client = new Client('sk_test_abc123');
        $ref = new \ReflectionClass($client);
        $prop = $ref->getProperty('httpClient');
        $prop->setValue($client, $guzzle);

        return $client;
    }
}
