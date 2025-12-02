<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PythonServiceClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $pythonServiceUrl,
        private readonly LoggerInterface $logger
    ) {
    }

    public function extractSchema(array $rawData): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->pythonServiceUrl . '/api/extract-schema', [
                'json' => ['rawData' => $rawData],
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Python service returned non-200 status code');
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Failed to extract schema from Python service', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Schema extraction failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function resolveEntities(array $extractedData, string $sourceTenantCode, string $targetTenantCode): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->pythonServiceUrl . '/api/resolve-entities', [
                'json' => [
                    'extractedData' => $extractedData,
                    'sourceTenantCode' => $sourceTenantCode,
                    'targetTenantCode' => $targetTenantCode,
                ],
                'timeout' => 60,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Python service returned non-200 status code');
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Failed to resolve entities from Python service', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Entity resolution failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function submitFeedback(array $feedbackData): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->pythonServiceUrl . '/api/feedback', [
                'json' => $feedbackData,
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 204) {
                throw new \RuntimeException('Python service returned non-success status code');
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to submit feedback to Python service', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Feedback submission failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
