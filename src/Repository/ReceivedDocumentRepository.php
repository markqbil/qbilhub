<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ReceivedDocument;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReceivedDocument>
 */
class ReceivedDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReceivedDocument::class);
    }

    public function findUnreadCountForTenant(Tenant $tenant): int
    {
        return (int) $this->createQueryBuilder('rd')
            ->select('COUNT(rd.id)')
            ->where('rd.targetTenant = :tenant')
            ->andWhere('rd.isRead = :isRead')
            ->setParameter('tenant', $tenant)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findInboxForUser(User $user, ?string $filter = 'my'): array
    {
        $qb = $this->createQueryBuilder('rd')
            ->where('rd.targetTenant = :tenant')
            ->setParameter('tenant', $user->getTenant());

        if ($filter === 'my') {
            // Only show documents not yet processed or processed by this user
            $qb->andWhere('rd.processedBy IS NULL OR rd.processedBy = :user')
                ->setParameter('user', $user);
        } elseif ($filter === 'all') {
            // Show all documents for tenant (user has delegation rights)
        } elseif (is_numeric($filter)) {
            // Show documents for specific user
            $qb->andWhere('rd.processedBy IS NULL OR rd.processedBy = :filteredUser')
                ->setParameter('filteredUser', $filter);
        }

        return $qb->orderBy('rd.receivedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(Tenant $tenant, string $status): array
    {
        return $this->createQueryBuilder('rd')
            ->where('rd.targetTenant = :tenant')
            ->andWhere('rd.status = :status')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', $status)
            ->orderBy('rd.receivedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
