<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PurchaseContract;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchaseContract>
 */
class PurchaseContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseContract::class);
    }

    public function findByTenant(Tenant $tenant): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('pc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByContractNumber(string $contractNumber): ?PurchaseContract
    {
        return $this->findOneBy(['contractNumber' => $contractNumber]);
    }
}
