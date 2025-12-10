<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\EntityResolutionMessage;
use App\Message\SchemaExtractionMessage;
use App\Repository\ReceivedDocumentRepository;
use App\Service\NotificationService;
use App\Service\PythonServiceClient;
use App\Service\PythonServiceException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class SchemaExtractionMessageHandler
{
    public function __construct(
        private readonly ReceivedDocumentRepository $documentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PythonServiceClient $pythonClient,
        private readonly MessageBusInterface $messageBus,
        private readonly NotificationService $notificationService,
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

        // Notify that processing has started
        $this->notificationService->notifyProcessingStarted($document, 'Extracting schema');

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

        } catch (PythonServiceException $e) {
            $this->handlePythonServiceError($document, $e);
        } catch (\Exception $e) {
            $this->handleGenericError($document, $e);
        }
    }

    private function handlePythonServiceError($document, PythonServiceException $e): void
    {
        if ($e->isConnectionError()) {
            // Service is down - notify user and allow retry
            $this->logger->warning('Python service unavailable during schema extraction', [
                'documentId' => $document->getId(),
                'error' => $e->getMessage()
            ]);

            $this->notificationService->notifyServiceUnavailable($document, 'Intelligence Service');

            // Mark as queued (not error) so it can be retried
            $document->setStatus('queued');
            $this->entityManager->flush();

            // Throw recoverable exception for Messenger retry
            throw new RecoverableMessageHandlingException(
                'Python service unavailable, will retry',
                0,
                $e
            );
        }

        // Non-connection error - mark as error
        $this->logger->error('Schema extraction failed', [
            'documentId' => $document->getId(),
            'errorType' => $e->getErrorType(),
            'error' => $e->getMessage()
        ]);

        $document->setStatus('error');
        $this->entityManager->flush();

        $this->notificationService->notifyDocumentError(
            $document,
            $e->getErrorType(),
            'Schema extraction failed. Please try again or contact support.'
        );
    }

    private function handleGenericError($document, \Exception $e): void
    {
        $this->logger->error('Unexpected error during schema extraction', [
            'documentId' => $document->getId(),
            'error' => $e->getMessage()
        ]);

        $document->setStatus('error');
        $this->entityManager->flush();

        $this->notificationService->notifyDocumentError(
            $document,
            'unknown',
            'An unexpected error occurred. Please try again or contact support.'
        );
    }
}
