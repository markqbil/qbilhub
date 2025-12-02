<?php

declare(strict_types=1);

namespace App\Message;

class ActiveLearningFeedbackMessage
{
    public function __construct(
        private readonly string $sourceTenantCode,
        private readonly string $targetTenantCode,
        private readonly string $sourceField,
        private readonly string $sourceValue,
        private readonly string $targetField,
        private readonly string $correctedValue
    ) {
    }

    public function getSourceTenantCode(): string
    {
        return $this->sourceTenantCode;
    }

    public function getTargetTenantCode(): string
    {
        return $this->targetTenantCode;
    }

    public function getSourceField(): string
    {
        return $this->sourceField;
    }

    public function getSourceValue(): string
    {
        return $this->sourceValue;
    }

    public function getTargetField(): string
    {
        return $this->targetField;
    }

    public function getCorrectedValue(): string
    {
        return $this->correctedValue;
    }
}
