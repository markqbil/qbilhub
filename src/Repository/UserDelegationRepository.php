<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDelegation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDelegation>
 */
class UserDelegationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDelegation::class);
    }

    public function findDelegatesForUser(User $user): array
    {
        return $this->createQueryBuilder('ud')
            ->select('IDENTITY(ud.delegate)')
            ->where('ud.delegator = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function findDelegatorsForUser(User $user): array
    {
        return $this->createQueryBuilder('ud')
            ->select('IDENTITY(ud.delegator)')
            ->where('ud.delegate = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
