<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\TenantRelationMappingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TenantRelationMappingRepository::class)]
#[ORM\Table(name: 'tenant_relation_mappings')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
    ]
)]
class TenantRelationMapping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class, inversedBy: 'relationMappings')]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $sourceTenant;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $internalRelationId;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $externalTenantCode;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $defaultSendViaHub = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
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

    public function getInternalRelationId(): string
    {
        return $this->internalRelationId;
    }

    public function setInternalRelationId(string $internalRelationId): self
    {
        $this->internalRelationId = $internalRelationId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getExternalTenantCode(): string
    {
        return $this->externalTenantCode;
    }

    public function setExternalTenantCode(string $externalTenantCode): self
    {
        $this->externalTenantCode = $externalTenantCode;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isDefaultSendViaHub(): bool
    {
        return $this->defaultSendViaHub;
    }

    public function setDefaultSendViaHub(bool $defaultSendViaHub): self
    {
        $this->defaultSendViaHub = $defaultSendViaHub;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
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
