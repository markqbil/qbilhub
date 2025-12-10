<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant;
use App\Entity\TenantRelationMapping;
use App\Repository\TenantRepository;
use App\Repository\TenantRelationMappingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly TenantRelationMappingRepository $mappingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        $stats = [
            'activeTenants' => count($this->tenantRepository->findActiveTenants()),
            'totalMappings' => count($this->mappingRepository->findBy(['sourceTenant' => $tenant])),
            'activeMappings' => count($this->mappingRepository->findBy([
                'sourceTenant' => $tenant,
                'isActive' => true
            ])),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/tenants', name: 'admin_tenants')]
    public function tenants(): Response
    {
        return $this->render('admin/tenants.html.twig');
    }

    #[Route('/tenants/directory', name: 'admin_tenant_directory', methods: ['GET'])]
    public function getTenantDirectory(): JsonResponse
    {
        $tenants = $this->tenantRepository->findActiveTenants();

        $data = array_map(function (Tenant $tenant) {
            return [
                'id' => $tenant->getId(),
                'name' => $tenant->getName(),
                'tenantCode' => $tenant->getTenantCode(),
                'logoUrl' => $tenant->getLogoUrl(),
                'isHubActive' => $tenant->isHubActive(),
            ];
        }, $tenants);

        return new JsonResponse($data);
    }

    #[Route('/relations', name: 'admin_relations')]
    public function relations(): Response
    {
        return $this->render('admin/relations.html.twig');
    }

    #[Route('/relations/list', name: 'admin_relations_list', methods: ['GET'])]
    public function getRelations(): JsonResponse
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        $mappings = $this->mappingRepository->findBy(['sourceTenant' => $tenant]);

        $data = array_map(function (TenantRelationMapping $mapping) {
            $externalTenant = $this->tenantRepository->findByTenantCode($mapping->getExternalTenantCode());

            return [
                'id' => $mapping->getId(),
                'internalRelationId' => $mapping->getInternalRelationId(),
                'externalTenantCode' => $mapping->getExternalTenantCode(),
                'externalTenantName' => $externalTenant?->getName(),
                'externalTenantLogo' => $externalTenant?->getLogoUrl(),
                'isActive' => $mapping->isActive(),
                'defaultSendViaHub' => $mapping->isDefaultSendViaHub(),
                'createdAt' => $mapping->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $mapping->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $mappings);

        return new JsonResponse($data);
    }

    #[Route('/relations/create', name: 'admin_relations_create', methods: ['POST'])]
    public function createRelation(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['internalRelationId']) || !isset($data['externalTenantCode'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing required fields'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if mapping already exists
        $existing = $this->mappingRepository->findOneBy([
            'sourceTenant' => $tenant,
            'internalRelationId' => $data['internalRelationId'],
        ]);

        if ($existing) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Mapping already exists for this relation'
            ], Response::HTTP_CONFLICT);
        }

        // Verify external tenant exists
        $externalTenant = $this->tenantRepository->findByTenantCode($data['externalTenantCode']);
        if (!$externalTenant) {
            return new JsonResponse([
                'success' => false,
                'message' => 'External tenant not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $mapping = new TenantRelationMapping();
        $mapping->setSourceTenant($tenant);
        $mapping->setInternalRelationId($data['internalRelationId']);
        $mapping->setExternalTenantCode($data['externalTenantCode']);
        $mapping->setIsActive($data['isActive'] ?? true);
        $mapping->setDefaultSendViaHub($data['defaultSendViaHub'] ?? false);

        $this->entityManager->persist($mapping);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Relation mapping created',
            'id' => $mapping->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/relations/{id}', name: 'admin_relations_update', methods: ['PUT'])]
    public function updateRelation(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        $mapping = $this->mappingRepository->find($id);

        if (!$mapping || $mapping->getSourceTenant()->getId() !== $tenant->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Mapping not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['internalRelationId'])) {
            $mapping->setInternalRelationId($data['internalRelationId']);
        }
        if (isset($data['externalTenantCode'])) {
            $mapping->setExternalTenantCode($data['externalTenantCode']);
        }
        if (isset($data['isActive'])) {
            $mapping->setIsActive((bool) $data['isActive']);
        }
        if (isset($data['defaultSendViaHub'])) {
            $mapping->setDefaultSendViaHub((bool) $data['defaultSendViaHub']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Relation mapping updated'
        ]);
    }

    #[Route('/relations/{id}', name: 'admin_relations_delete', methods: ['DELETE'])]
    public function deleteRelation(int $id): JsonResponse
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        $mapping = $this->mappingRepository->find($id);

        if (!$mapping || $mapping->getSourceTenant()->getId() !== $tenant->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Mapping not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($mapping);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Relation mapping deleted'
        ]);
    }

    #[Route('/hub/activate', name: 'admin_hub_activate', methods: ['POST'])]
    public function activateHub(): JsonResponse
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        $tenant->setIsHubActive(true);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Hub activated for your organization'
        ]);
    }

    #[Route('/hub/deactivate', name: 'admin_hub_deactivate', methods: ['POST'])]
    public function deactivateHub(): JsonResponse
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        $tenant->setIsHubActive(false);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Hub deactivated for your organization'
        ]);
    }

    #[Route('/hub/status', name: 'admin_hub_status', methods: ['GET'])]
    public function getHubStatus(): JsonResponse
    {
        $user = $this->getUser();
        $tenant = $user->getTenant();

        return new JsonResponse([
            'isHubActive' => $tenant->isHubActive(),
            'tenantCode' => $tenant->getTenantCode(),
            'tenantName' => $tenant->getName(),
        ]);
    }
}
