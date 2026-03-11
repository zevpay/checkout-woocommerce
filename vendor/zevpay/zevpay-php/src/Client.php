<?php

declare(strict_types=1);

namespace ZevPay;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use ZevPay\Exceptions\ApiException;
use ZevPay\Exceptions\AuthenticationException;
use ZevPay\Exceptions\ConflictException;
use ZevPay\Exceptions\NotFoundException;
use ZevPay\Exceptions\PermissionException;
use ZevPay\Exceptions\RateLimitException;
use ZevPay\Exceptions\ValidationException;
use ZevPay\Exceptions\ZevPayException;

class Client
{
    private const VERSION = '0.1.0';
    private const DEFAULT_BASE_URL = 'https://api.zevpaycheckout.com';
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_MAX_RETRIES = 2;

    private GuzzleClient $httpClient;
    private string $baseUrl;

    public function __construct(
        private readonly string $secretKey,
        array $options = [],
    ) {
        $this->baseUrl = rtrim($options['base_url'] ?? self::DEFAULT_BASE_URL, '/');
        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        $maxRetries = $options['max_retries'] ?? self::DEFAULT_MAX_RETRIES;

        $stack = HandlerStack::create();
        $stack->push($this->retryMiddleware($maxRetries));

        $this->httpClient = new GuzzleClient([
            'handler' => $stack,
            'timeout' => $timeout,
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'zevpay-php/' . self::VERSION,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $url = $this->baseUrl . $path;

        $options = [];
        if (!empty($body)) {
            $options['json'] = $body;
        }
        if (!empty($query)) {
            $options['query'] = $query;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (ConnectException $e) {
            throw new ApiException(
                'Connection error: ' . $e->getMessage(),
                0,
                'CONNECTION_ERROR',
                $e,
            );
        }

        $statusCode = $response->getStatusCode();
        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true) ?? [];

        if ($statusCode >= 200 && $statusCode < 300) {
            return $data['data'] ?? $data;
        }

        $this->throwForStatus($statusCode, $data, $response);

        return []; // unreachable
    }

    /**
     * @return \Closure
     */
    private function retryMiddleware(int $maxRetries): callable
    {
        return Middleware::retry(
            function (int $retries, Request $request, ?Response $response, ?\Throwable $exception) use ($maxRetries): bool {
                if ($retries >= $maxRetries) {
                    return false;
                }

                // Retry on connection errors
                if ($exception instanceof ConnectException) {
                    return true;
                }

                // Retry on 5xx server errors
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            function (int $retries): int {
                // Exponential backoff: 1s, 2s, 4s...
                return (int) (1000 * pow(2, $retries));
            },
        );
    }

    /**
     * @param array<string, mixed> $data
     * @throws ZevPayException
     */
    private function throwForStatus(int $statusCode, array $data, Response $response): never
    {
        $error = $data['error'] ?? $data;
        $message = $error['message'] ?? 'Unknown error';
        $code = $error['code'] ?? 'UNKNOWN_ERROR';
        $details = $error['details'] ?? null;

        throw match ($statusCode) {
            400 => new ValidationException($message, $code, $details),
            401 => new AuthenticationException($message, $code),
            403 => new PermissionException($message, $code),
            404 => new NotFoundException($message, $code),
            409 => new ConflictException($message, $code),
            429 => new RateLimitException(
                $message,
                $code,
                $this->parseRetryAfter($response),
            ),
            default => new ApiException($message, $statusCode, $code),
        };
    }

    private function parseRetryAfter(Response $response): ?int
    {
        $header = $response->getHeaderLine('Retry-After');
        if ($header === '') {
            return null;
        }
        return (int) $header;
    }
}
