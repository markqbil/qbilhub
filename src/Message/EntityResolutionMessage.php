<?php

declare(strict_types=1);

namespace App\Message;

class EntityResolutionMessage
{
    public function __construct(
        private readonly int $documentId,
        private readonly array $extractedData,
        private readonly string $sourceTenantCode,
        private readonly string $targetTenantCode
    ) {
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getExtractedData(): array
    {
        return $this->extractedData;
    }

    public function getSourceTenantCode(): string
    {
        return $this->sourceTenantCode;
    }

    public function getTargetTenantCode(): string
    {
        return $this->targetTenantCode;
    }
}
