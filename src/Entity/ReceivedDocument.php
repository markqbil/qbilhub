<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ReceivedDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReceivedDocumentRepository::class)]
#[ORM\Table(name: 'received_documents')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
    ]
)]
class ReceivedDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $sourceTenant;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $targetTenant;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    private string $status = 'new';

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $documentType;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $documentUrl = null;

    #[ORM\Column(type: 'jsonb')]
    private array $rawData = [];

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $extractedSchema = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $mappedData = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $confidenceScores = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $processedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\ManyToOne(targetEntity: PurchaseContract::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?PurchaseContract $linkedContract = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->receivedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceTenant(): Tenant
    {
        return $this->sourceTenant;
    }

    public function setSourceTenant(Tenant $sourceTenant): self
    {
        $this->sourceTenant = $sourceTenant;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTargetTenant(): Tenant
    {
        return $this->targetTenant;
    }

    public function setTargetTenant(Tenant $targetTenant): self
    {
        $this->targetTenant = $targetTenant;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function setDocumentType(string $documentType): self
    {
        $this->documentType = $documentType;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDocumentUrl(): ?string
    {
        return $this->documentUrl;
    }

    public function setDocumentUrl(?string $documentUrl): self
    {
        $this->documentUrl = $documentUrl;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): self
    {
        $this->rawData = $rawData;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getExtractedSchema(): ?array
    {
        return $this->extractedSchema;
    }

    public function setExtractedSchema(?array $extractedSchema): self
    {
        $this->extractedSchema = $extractedSchema;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMappedData(): ?array
    {
        return $this->mappedData;
    }

    public function setMappedData(?array $mappedData): self
    {
        $this->mappedData = $mappedData;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getConfidenceScores(): ?array
    {
        return $this->confidenceScores;
    }

    public function setConfidenceScores(?array $confidenceScores): self
    {
        $this->confidenceScores = $confidenceScores;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getProcessedBy(): ?User
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): self
    {
        $this->processedBy = $processedBy;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLinkedContract(): ?PurchaseContract
    {
        return $this->linkedContract;
    }

    public function setLinkedContract(?PurchaseContract $linkedContract): self
    {
        $this->linkedContract = $linkedContract;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
