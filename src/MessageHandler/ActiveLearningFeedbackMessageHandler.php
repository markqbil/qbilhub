<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ActiveLearningFeedbackMessage;
use App\Service\PythonServiceClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ActiveLearningFeedbackMessageHandler
{
    public function __construct(
        private readonly PythonServiceClient $pythonClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ActiveLearningFeedbackMessage $message): void
    {
        try {
            // Send correction to Python service for model retraining
            $this->pythonClient->submitFeedback([
                'sourceTenantCode' => $message->getSourceTenantCode(),
                'targetTenantCode' => $message->getTargetTenantCode(),
                'sourceField' => $message->getSourceField(),
                'sourceValue' => $message->getSourceValue(),
                'targetField' => $message->getTargetField(),
                'correctedValue' => $message->getCorrectedValue(),
            ]);

            $this->logger->info('Active learning feedback submitted', [
                'sourceTenant' => $message->getSourceTenantCode(),
                'targetTenant' => $message->getTargetTenantCode(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to submit active learning feedback', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
