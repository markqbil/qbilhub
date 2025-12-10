<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ReceivedDocument;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger
    ) {
    }

    public function notifyDocumentReady(ReceivedDocument $document): void
    {
        $this->publishUpdate($document, 'document_ready', [
            'status' => $document->getStatus(),
            'message' => 'Document is ready for mapping',
        ]);
    }

    public function notifyDocumentError(ReceivedDocument $document, string $errorType, string $errorMessage): void
    {
        $this->publishUpdate($document, 'document_error', [
            'status' => 'error',
            'errorType' => $errorType,
            'errorMessage' => $errorMessage,
        ]);

        $this->logger->warning('Document processing error notification sent', [
            'documentId' => $document->getId(),
            'errorType' => $errorType,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function notifyServiceUnavailable(ReceivedDocument $document, string $serviceName): void
    {
        $this->publishUpdate($document, 'service_unavailable', [
            'status' => 'queued',
            'service' => $serviceName,
            'message' => sprintf('The %s service is temporarily unavailable. Your document has been queued and will be processed automatically when the service is restored.', $serviceName),
        ]);
    }

    public function notifyProcessingDelayed(ReceivedDocument $document, string $reason): void
    {
        $this->publishUpdate($document, 'processing_delayed', [
            'status' => 'delayed',
            'reason' => $reason,
            'message' => 'Document processing has been delayed. It will be processed automatically.',
        ]);
    }

    public function notifyProcessingStarted(ReceivedDocument $document, string $stage): void
    {
        $this->publishUpdate($document, 'processing_started', [
            'status' => 'processing',
            'stage' => $stage,
            'message' => sprintf('Document is being processed: %s', $stage),
        ]);
    }

    private function publishUpdate(ReceivedDocument $document, string $type, array $data): void
    {
        try {
            $tenantId = $document->getTargetTenant()->getId();
            $topic = sprintf('https://qbilhub.com/inbox/%d', $tenantId);

            $update = new Update(
                $topic,
                json_encode([
                    'type' => $type,
                    'documentId' => $document->getId(),
                    'timestamp' => (new \DateTime())->format('c'),
                    ...$data,
                ])
            );

            $this->hub->publish($update);

            $this->logger->debug('Mercure update published', [
                'topic' => $topic,
                'type' => $type,
                'documentId' => $document->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish Mercure update', [
                'documentId' => $document->getId(),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
