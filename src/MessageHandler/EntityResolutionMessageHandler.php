<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\EntityResolutionMessage;
use App\Repository\ReceivedDocumentRepository;
use App\Service\PythonServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EntityResolutionMessageHandler
{
    public function __construct(
        private readonly ReceivedDocumentRepository $documentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PythonServiceClient $pythonClient,
        private readonly HubInterface $mercureHub,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(EntityResolutionMessage $message): void
    {
        $document = $this->documentRepository->find($message->getDocumentId());

        if (!$document) {
            $this->logger->error('Document not found', ['documentId' => $message->getDocumentId()]);
            return;
        }

        try {
            // Call Python service to resolve entities (product matching)
            $resolutionResult = $this->pythonClient->resolveEntities(
                $message->getExtractedData(),
                $message->getSourceTenantCode(),
                $message->getTargetTenantCode()
            );

            $document->setMappedData($resolutionResult['mappedData']);
            $document->setConfidenceScores($resolutionResult['confidenceScores']);
            $document->setStatus('mapping');
            $this->entityManager->flush();

            // Publish real-time notification via Mercure
            $update = new Update(
                sprintf('https://qbilhub.com/inbox/%d', $document->getTargetTenant()->getId()),
                json_encode([
                    'type' => 'document_ready',
                    'documentId' => $document->getId(),
                    'status' => 'mapping'
                ])
            );
            $this->mercureHub->publish($update);

            $this->logger->info('Entity resolution completed', ['documentId' => $document->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Entity resolution failed', [
                'documentId' => $document->getId(),
                'error' => $e->getMessage()
            ]);

            $document->setStatus('error');
            $this->entityManager->flush();
        }
    }
}
