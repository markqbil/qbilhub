<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\EntityResolutionMessage;
use App\Message\SchemaExtractionMessage;
use App\Repository\ReceivedDocumentRepository;
use App\Service\PythonServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class SchemaExtractionMessageHandler
{
    public function __construct(
        private readonly ReceivedDocumentRepository $documentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PythonServiceClient $pythonClient,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(SchemaExtractionMessage $message): void
    {
        $document = $this->documentRepository->find($message->getDocumentId());

        if (!$document) {
            $this->logger->error('Document not found', ['documentId' => $message->getDocumentId()]);
            return;
        }

        try {
            // Call Python service to extract schema
            $extractedSchema = $this->pythonClient->extractSchema($message->getRawData());

            $document->setExtractedSchema($extractedSchema);
            $document->setStatus('resolving_entities');
            $this->entityManager->flush();

            // Step 2: Resolve entities (product matching)
            $this->messageBus->dispatch(new EntityResolutionMessage(
                $document->getId(),
                $extractedSchema,
                $document->getSourceTenant()->getTenantCode(),
                $document->getTargetTenant()->getTenantCode()
            ));

            $this->logger->info('Schema extracted successfully', ['documentId' => $document->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Schema extraction failed', [
                'documentId' => $document->getId(),
                'error' => $e->getMessage()
            ]);

            $document->setStatus('error');
            $this->entityManager->flush();
        }
    }
}
