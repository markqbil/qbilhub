<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    public function findActiveTenants(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isHubActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTenantCode(string $tenantCode): ?Tenant
    {
        return $this->findOneBy(['tenantCode' => $tenantCode]);
    }
}
