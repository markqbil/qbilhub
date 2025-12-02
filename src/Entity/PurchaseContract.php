<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\PurchaseContractRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseContractRepository::class)]
#[ORM\Table(name: 'purchase_contracts')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
    ]
)]
class PurchaseContract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    private string $contractNumber;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $supplier;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $product;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private string $quantity;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    private string $unit;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    private string $pricePerUnit;

    #[ORM\Column(type: 'string', length: 10)]
    #[Assert\NotBlank]
    private string $currency;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    private \DateTimeImmutable $deliveryDate;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $deliveryLocation = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $additionalTerms = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'draft';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

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

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function setTenant(Tenant $tenant): self
    {
        $this->tenant = $tenant;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getContractNumber(): string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): self
    {
        $this->contractNumber = $contractNumber;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSupplier(): string
    {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): self
    {
        $this->supplier = $supplier;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getProduct(): string
    {
        return $this->product;
    }

    public function setProduct(string $product): self
    {
        $this->product = $product;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPricePerUnit(): string
    {
        return $this->pricePerUnit;
    }

    public function setPricePerUnit(string $pricePerUnit): self
    {
        $this->pricePerUnit = $pricePerUnit;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDeliveryDate(): \DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(\DateTimeImmutable $deliveryDate): self
    {
        $this->deliveryDate = $deliveryDate;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDeliveryLocation(): ?string
    {
        return $this->deliveryLocation;
    }

    public function setDeliveryLocation(?string $deliveryLocation): self
    {
        $this->deliveryLocation = $deliveryLocation;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAdditionalTerms(): ?array
    {
        return $this->additionalTerms;
    }

    public function setAdditionalTerms(?array $additionalTerms): self
    {
        $this->additionalTerms = $additionalTerms;
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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
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
