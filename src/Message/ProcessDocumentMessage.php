<?php

declare(strict_types=1);

namespace App\Message;

class ProcessDocumentMessage
{
    public function __construct(
        private readonly int $documentId
    ) {
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }
}
