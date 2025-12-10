<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class QbilTradeApiClient
{
    private const BASE_URL = 'https://api.qbiltrade.com';
    private const API_VERSION = 'v1';

    private int $maxRetries = 3;
    private int $retryDelay = 1000; // milliseconds

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiToken,
        private readonly bool $enableRateLimiting = true
    ) {
    }

    /**
     * Get current API user profile
     */
    public function getMe(): array
    {
        return $this->request('GET', '/api/v1/me');
    }

    /**
     * List all contracts with optional filters
     */
    public function listContracts(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        return $this->request('GET', '/api/v1/contracts' . $queryParams);
    }

    /**
     * Get a specific contract by ID
     */
    public function getContract(string $contractId): array
    {
        return $this->request('GET', "/api/v1/contracts/{$contractId}");
    }

    /**
     * List all orders with optional filters
     */
    public function listOrders(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        return $this->request('GET', '/api/v1/orders' . $queryParams);
    }

    /**
     * Get a specific order by ID
     */
    public function getOrder(string $orderId): array
    {
        return $this->request('GET', "/api/v1/orders/{$orderId}");
    }

    /**
     * Create a new order
     */
    public function createOrder(array $orderData): array
    {
        return $this->request('POST', '/api/v1/orders', ['json' => $orderData]);
    }

    /**
     * Update an existing order
     */
    public function updateOrder(string $orderId, array $orderData): array
    {
        return $this->request('PATCH', "/api/v1/orders/{$orderId}", ['json' => $orderData]);
    }

    /**
     * List addresses
     */
    public function listAddresses(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        return $this->request('GET', '/api/v1/addresses' . $queryParams);
    }

    /**
     * Get a specific address by ID
     */
    public function getAddress(string $addressId): array
    {
        return $this->request('GET', "/api/v1/addresses/{$addressId}");
    }

    /**
     * List delivery conditions
     */
    public function listDeliveryConditions(): array
    {
        return $this->request('GET', '/api/v1/delivery-conditions');
    }

    /**
     * Make an authenticated API request with retry logic and rate limiting
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        $url = self::BASE_URL . $endpoint;
        $attempt = 0;

        // Add authentication header
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        while ($attempt < $this->maxRetries) {
            try {
                $this->logger->info('Qbil Trade API request', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'attempt' => $attempt + 1,
                ]);

                $response = $this->httpClient->request($method, $url, $options);
                $statusCode = $response->getStatusCode();

                // Check rate limiting headers
                if ($this->enableRateLimiting) {
                    $this->handleRateLimiting($response->getHeaders());
                }

                // Handle different status codes
                if ($statusCode >= 200 && $statusCode < 300) {
                    $data = $response->toArray();

                    $this->logger->info('Qbil Trade API success', [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'status' => $statusCode,
                    ]);

                    return $data;
                }

                // Handle rate limiting (429)
                if ($statusCode === 429) {
                    $retryAfter = (int) ($response->getHeaders()['x-ratelimit-reset'][0] ?? time() + 60);
                    $waitTime = max($retryAfter - time(), 1);

                    $this->logger->warning('Rate limit hit, waiting', [
                        'wait_seconds' => $waitTime,
                        'endpoint' => $endpoint,
                    ]);

                    if ($attempt < $this->maxRetries - 1) {
                        sleep($waitTime);
                        $attempt++;
                        continue;
                    }
                }

                // Handle server errors (5xx) with retry
                if ($statusCode >= 500 && $attempt < $this->maxRetries - 1) {
                    $this->logger->warning('Server error, retrying', [
                        'status' => $statusCode,
                        'endpoint' => $endpoint,
                        'attempt' => $attempt + 1,
                    ]);

                    usleep($this->retryDelay * 1000 * ($attempt + 1)); // Exponential backoff
                    $attempt++;
                    continue;
                }

                // Handle client errors (4xx)
                $errorData = $response->toArray(false);
                $this->logger->error('Qbil Trade API error', [
                    'status' => $statusCode,
                    'endpoint' => $endpoint,
                    'error' => $errorData,
                ]);

                throw new QbilTradeException(
                    $errorData['message'] ?? 'API request failed',
                    $statusCode,
                    $errorData
                );

            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Transport error calling Qbil Trade API', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt + 1,
                ]);

                if ($attempt < $this->maxRetries - 1) {
                    usleep($this->retryDelay * 1000 * ($attempt + 1));
                    $attempt++;
                    continue;
                }

                throw new QbilTradeException(
                    'Failed to connect to Qbil Trade API: ' . $e->getMessage(),
                    0,
                    [],
                    $e
                );
            }
        }

        throw new QbilTradeException('Max retries exceeded for Qbil Trade API request');
    }

    /**
     * Handle rate limiting by checking response headers
     */
    private function handleRateLimiting(array $headers): void
    {
        $remaining = (int) ($headers['x-ratelimit-remaining'][0] ?? 100);
        $window = (int) ($headers['x-ratelimit-window'][0] ?? 60);

        if ($remaining < 5) {
            $this->logger->warning('Approaching rate limit', [
                'remaining' => $remaining,
                'window' => $window,
            ]);
        }
    }

    /**
     * Build query parameters string from array
     */
    private function buildQueryParams(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        return '?' . http_build_query($params);
    }

    /**
     * Convert a purchase contract to a sales contract format
     */
    public function flipContractDirection(array $contractData): array
    {
        return [
            'type' => 'sales_order', // Changed from purchase to sales
            'external_reference' => $contractData['id'] ?? null,
            'buyer' => $contractData['seller'] ?? null, // Flip buyer/seller
            'seller' => $contractData['buyer'] ?? null,
            'items' => $contractData['items'] ?? [],
            'delivery_address' => $contractData['delivery_address'] ?? null,
            'delivery_date' => $contractData['delivery_date'] ?? null,
            'payment_terms' => $contractData['payment_terms'] ?? null,
            'notes' => 'Processed via QbilHub - Original contract: ' . ($contractData['contract_number'] ?? 'N/A'),
        ];
    }
}
