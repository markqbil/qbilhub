<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\EntityResolutionMessage;
use App\Message\ProcessDocumentMessage;
use App\Message\SchemaExtractionMessage;
use App\Repository\ReceivedDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ProcessDocumentMessageHandler
{
    public function __construct(
        private readonly ReceivedDocumentRepository $documentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ProcessDocumentMessage $message): void
    {
        $document = $this->documentRepository->find($message->getDocumentId());

        if (!$document) {
            $this->logger->error('Document not found', ['documentId' => $message->getDocumentId()]);
            return;
        }

        try {
            // Step 1: Extract schema using Python service (LLM)
            $this->messageBus->dispatch(new SchemaExtractionMessage(
                $document->getId(),
                $document->getRawData()
            ));

            $document->setStatus('extracting_schema');
            $this->entityManager->flush();

            $this->logger->info('Schema extraction initiated', ['documentId' => $document->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process document', [
                'documentId' => $document->getId(),
                'error' => $e->getMessage()
            ]);

            $document->setStatus('error');
            $this->entityManager->flush();
        }
    }
}
