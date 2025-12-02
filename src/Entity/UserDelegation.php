<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\UserDelegationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDelegationRepository::class)]
#[ORM\Table(name: 'user_delegations')]
#[ORM\UniqueConstraint(name: 'unique_delegation', columns: ['delegator_id', 'delegate_id'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Delete(),
    ]
)]
class UserDelegation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'delegationsGiven')]
    #[ORM\JoinColumn(nullable: false)]
    private User $delegator;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'delegationsReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private User $delegate;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDelegator(): User
    {
        return $this->delegator;
    }

    public function setDelegator(User $delegator): self
    {
        $this->delegator = $delegator;
        return $this;
    }

    public function getDelegate(): User
    {
        return $this->delegate;
    }

    public function setDelegate(User $delegate): self
    {
        $this->delegate = $delegate;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
