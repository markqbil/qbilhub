<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
    ]
)]
class Tenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    private string $tenantCode;

    #[ORM\Column(type: 'boolean')]
    private bool $isHubActive = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'tenant', targetEntity: User::class)]
    private Collection $users;

    /**
     * @var Collection<int, TenantRelationMapping>
     */
    #[ORM\OneToMany(mappedBy: 'sourceTenant', targetEntity: TenantRelationMapping::class)]
    private Collection $relationMappings;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->relationMappings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTenantCode(): string
    {
        return $this->tenantCode;
    }

    public function setTenantCode(string $tenantCode): self
    {
        $this->tenantCode = $tenantCode;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isHubActive(): bool
    {
        return $this->isHubActive;
    }

    public function setIsHubActive(bool $isHubActive): self
    {
        $this->isHubActive = $isHubActive;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, TenantRelationMapping>
     */
    public function getRelationMappings(): Collection
    {
        return $this->relationMappings;
    }
}
