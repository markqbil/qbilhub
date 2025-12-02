<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use App\Entity\TenantRelationMapping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TenantRelationMapping>
 */
class TenantRelationMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TenantRelationMapping::class);
    }

    public function findMappingByRelationAndTenant(string $relationId, Tenant $tenant): ?TenantRelationMapping
    {
        return $this->createQueryBuilder('trm')
            ->where('trm.internalRelationId = :relationId')
            ->andWhere('trm.sourceTenant = :tenant')
            ->andWhere('trm.isActive = :active')
            ->setParameter('relationId', $relationId)
            ->setParameter('tenant', $tenant)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveHubConnections(Tenant $tenant): array
    {
        return $this->createQueryBuilder('trm')
            ->where('trm.sourceTenant = :tenant')
            ->andWhere('trm.isActive = :active')
            ->andWhere('trm.defaultSendViaHub = :sendViaHub')
            ->setParameter('tenant', $tenant)
            ->setParameter('active', true)
            ->setParameter('sendViaHub', true)
            ->getQuery()
            ->getResult();
    }
}
