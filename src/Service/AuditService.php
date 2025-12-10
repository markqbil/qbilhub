<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuditService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Log an action to the audit trail
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $onBehalfOf = null
    ): void {
        try {
            $user = $this->getCurrentUser();
            if ($user === null) {
                $this->logger->warning('Audit log attempted without authenticated user', [
                    'action' => $action,
                    'entityType' => $entityType,
                    'entityId' => $entityId,
                ]);
                return;
            }

            $auditLog = new AuditLog();
            $auditLog->setUser($user);
            $auditLog->setTenant($user->getTenant());
            $auditLog->setAction($action);
            $auditLog->setEntityType($entityType);
            $auditLog->setEntityId($entityId);
            $auditLog->setDescription($description);
            $auditLog->setOldValues($oldValues);
            $auditLog->setNewValues($newValues);
            $auditLog->setMetadata($metadata);
            $auditLog->setOnBehalfOf($onBehalfOf);

            // Add request information if available
            $request = $this->requestStack->getCurrentRequest();
            if ($request !== null) {
                $auditLog->setIpAddress($request->getClientIp());
                $auditLog->setUserAgent(substr($request->headers->get('User-Agent', ''), 0, 255));
            }

            $this->entityManager->persist($auditLog);
            $this->entityManager->flush();

            $this->logger->debug('Audit log created', [
                'action' => $action,
                'entityType' => $entityType,
                'entityId' => $entityId,
                'userId' => $user->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create audit log', [
                'action' => $action,
                'entityType' => $entityType,
                'entityId' => $entityId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log document view
     */
    public function logDocumentView(int $documentId, ?User $onBehalfOf = null): void
    {
        $this->log(
            AuditLog::ACTION_VIEW,
            'ReceivedDocument',
            $documentId,
            'Viewed document',
            onBehalfOf: $onBehalfOf
        );
    }

    /**
     * Log document processing
     */
    public function logDocumentProcess(
        int $documentId,
        array $contractData,
        ?User $onBehalfOf = null
    ): void {
        $this->log(
            AuditLog::ACTION_PROCESS,
            'ReceivedDocument',
            $documentId,
            'Processed document and created contract',
            newValues: $contractData,
            onBehalfOf: $onBehalfOf
        );
    }

    /**
     * Log document retry
     */
    public function logDocumentRetry(int $documentId): void
    {
        $this->log(
            AuditLog::ACTION_RETRY,
            'ReceivedDocument',
            $documentId,
            'Retried document processing'
        );
    }

    /**
     * Log contract creation
     */
    public function logContractCreate(int $contractId, array $contractData): void
    {
        $this->log(
            AuditLog::ACTION_CREATE,
            'PurchaseContract',
            $contractId,
            'Created purchase contract',
            newValues: $contractData
        );
    }

    /**
     * Log contract update
     */
    public function logContractUpdate(
        int $contractId,
        array $oldValues,
        array $newValues
    ): void {
        $this->log(
            AuditLog::ACTION_UPDATE,
            'PurchaseContract',
            $contractId,
            'Updated purchase contract',
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    /**
     * Log relation mapping changes
     */
    public function logRelationMappingChange(
        string $action,
        int $mappingId,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $descriptions = [
            AuditLog::ACTION_CREATE => 'Created relation mapping',
            AuditLog::ACTION_UPDATE => 'Updated relation mapping',
            AuditLog::ACTION_DELETE => 'Deleted relation mapping',
        ];

        $this->log(
            $action,
            'TenantRelationMapping',
            $mappingId,
            $descriptions[$action] ?? 'Modified relation mapping',
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    /**
     * Log user login
     */
    public function logLogin(User $user): void
    {
        $auditLog = new AuditLog();
        $auditLog->setUser($user);
        $auditLog->setTenant($user->getTenant());
        $auditLog->setAction(AuditLog::ACTION_LOGIN);
        $auditLog->setEntityType('User');
        $auditLog->setEntityId($user->getId());
        $auditLog->setDescription('User logged in');

        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent(substr($request->headers->get('User-Agent', ''), 0, 255));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user): void
    {
        $auditLog = new AuditLog();
        $auditLog->setUser($user);
        $auditLog->setTenant($user->getTenant());
        $auditLog->setAction(AuditLog::ACTION_LOGOUT);
        $auditLog->setEntityType('User');
        $auditLog->setEntityId($user->getId());
        $auditLog->setDescription('User logged out');

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }

    /**
     * Get the current authenticated user
     */
    private function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return null;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }
}
