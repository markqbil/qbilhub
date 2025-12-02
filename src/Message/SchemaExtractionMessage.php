<?php

declare(strict_types=1);

namespace App\Message;

class SchemaExtractionMessage
{
    public function __construct(
        private readonly int $documentId,
        private readonly array $rawData
    ) {
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }
}
