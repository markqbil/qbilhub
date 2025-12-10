<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PythonServiceClient
{
    private bool $isHealthy = true;
    private ?\DateTimeImmutable $lastHealthCheck = null;
    private const HEALTH_CHECK_INTERVAL = 30; // seconds

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
                throw new PythonServiceException(
                    'Schema extraction failed',
                    PythonServiceException::ERROR_SCHEMA_EXTRACTION,
                    $response->getStatusCode()
                );
            }

            $this->markHealthy();
            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            $this->markUnhealthy();
            $this->logger->error('Python service connection failed during schema extraction', [
                'error' => $e->getMessage()
            ]);
            throw new PythonServiceException(
                'Python service is unavailable: ' . $e->getMessage(),
                PythonServiceException::ERROR_CONNECTION,
                0,
                $e
            );
        } catch (\Exception $e) {
            if ($e instanceof PythonServiceException) {
                throw $e;
            }
            $this->logger->error('Failed to extract schema from Python service', [
                'error' => $e->getMessage()
            ]);
            throw new PythonServiceException(
                'Schema extraction failed: ' . $e->getMessage(),
                PythonServiceException::ERROR_SCHEMA_EXTRACTION,
                0,
                $e
            );
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
                throw new PythonServiceException(
                    'Entity resolution failed',
                    PythonServiceException::ERROR_ENTITY_RESOLUTION,
                    $response->getStatusCode()
                );
            }

            $this->markHealthy();
            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            $this->markUnhealthy();
            $this->logger->error('Python service connection failed during entity resolution', [
                'error' => $e->getMessage()
            ]);
            throw new PythonServiceException(
                'Python service is unavailable: ' . $e->getMessage(),
                PythonServiceException::ERROR_CONNECTION,
                0,
                $e
            );
        } catch (\Exception $e) {
            if ($e instanceof PythonServiceException) {
                throw $e;
            }
            $this->logger->error('Failed to resolve entities from Python service', [
                'error' => $e->getMessage()
            ]);
            throw new PythonServiceException(
                'Entity resolution failed: ' . $e->getMessage(),
                PythonServiceException::ERROR_ENTITY_RESOLUTION,
                0,
                $e
            );
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
                throw new PythonServiceException(
                    'Feedback submission failed',
                    PythonServiceException::ERROR_FEEDBACK,
                    $response->getStatusCode()
                );
            }

            $this->markHealthy();
        } catch (TransportExceptionInterface $e) {
            $this->markUnhealthy();
            $this->logger->error('Python service connection failed during feedback submission', [
                'error' => $e->getMessage()
            ]);
            throw new PythonServiceException(
                'Python service is unavailable: ' . $e->getMessage(),
                PythonServiceException::ERROR_CONNECTION,
                0,
                $e
            );
        } catch (\Exception $e) {
            if ($e instanceof PythonServiceException) {
                throw $e;
            }
            $this->logger->error('Failed to submit feedback to Python service', [
                'error' => $e->getMessage()
            ]);
            throw new PythonServiceException(
                'Feedback submission failed: ' . $e->getMessage(),
                PythonServiceException::ERROR_FEEDBACK,
                0,
                $e
            );
        }
    }

    public function checkHealth(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->pythonServiceUrl . '/health', [
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() === 200) {
                $this->markHealthy();
                return $response->toArray();
            }

            $this->markUnhealthy();
            return ['status' => 'unhealthy', 'error' => 'Non-200 response'];
        } catch (\Exception $e) {
            $this->markUnhealthy();
            return ['status' => 'unavailable', 'error' => $e->getMessage()];
        }
    }

    public function isServiceHealthy(): bool
    {
        // Cache health status to avoid excessive checks
        if ($this->lastHealthCheck !== null) {
            $elapsed = (new \DateTimeImmutable())->getTimestamp() - $this->lastHealthCheck->getTimestamp();
            if ($elapsed < self::HEALTH_CHECK_INTERVAL) {
                return $this->isHealthy;
            }
        }

        $health = $this->checkHealth();
        return $health['status'] === 'healthy';
    }

    private function markHealthy(): void
    {
        $this->isHealthy = true;
        $this->lastHealthCheck = new \DateTimeImmutable();
    }

    private function markUnhealthy(): void
    {
        $this->isHealthy = false;
        $this->lastHealthCheck = new \DateTimeImmutable();
    }
}
