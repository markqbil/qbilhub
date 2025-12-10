<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ReceivedDocument;
use App\Message\ProcessDocumentMessage;
use App\Repository\ReceivedDocumentRepository;
use App\Repository\UserDelegationRepository;
use App\Service\AuditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hub/inbox')]
// TODO: Re-enable authentication after demo: #[IsGranted('ROLE_USER')]
class HubInboxController extends AbstractController
{
    public function __construct(
        private readonly ReceivedDocumentRepository $documentRepository,
        private readonly UserDelegationRepository $delegationRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly AuditService $auditService
    ) {
    }

    #[Route('', name: 'hub_inbox_index', methods: ['GET'])]
    public function index(): Response
    {
        // TODO: Add authentication - for demo, show all documents
        $documents = $this->documentRepository->findAll();

        return $this->render('hub/inbox.html.twig', [
            'documents' => $documents,
        ]);
    }

    #[Route('/documents', name: 'hub_inbox_documents', methods: ['GET'])]
    public function getDocuments(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $filter = $request->query->get('filter', 'my');

        // Verify user has delegation rights if viewing others' documents
        if ($filter === 'all') {
            $delegators = $this->delegationRepository->findDelegatorsForUser($user);
            if (empty($delegators)) {
                $filter = 'my'; // Fallback to my documents if no delegations
            }
        }

        $documents = $this->documentRepository->findInboxForUser($user, $filter);

        return $this->json([
            'documents' => array_map(function (ReceivedDocument $doc) {
                return [
                    'id' => $doc->getId(),
                    'status' => $doc->getStatus(),
                    'sourceTenant' => [
                        'name' => $doc->getSourceTenant()->getName(),
                        'logoUrl' => $doc->getSourceTenant()->getLogoUrl(),
                    ],
                    'documentType' => $doc->getDocumentType(),
                    'documentUrl' => $doc->getDocumentUrl(),
                    'receivedAt' => $doc->getReceivedAt()->format('c'),
                    'isRead' => $doc->isRead(),
                    'processedBy' => $doc->getProcessedBy()?->getFullName(),
                ];
            }, $documents),
        ]);
    }

    #[Route('/unread-count', name: 'hub_inbox_unread_count', methods: ['GET'])]
    public function getUnreadCount(): JsonResponse
    {
        $user = $this->getUser();
        $unreadCount = $this->documentRepository->findUnreadCountForTenant($user->getTenant());

        return $this->json(['unreadCount' => $unreadCount]);
    }

    #[Route('/document/{id}/mark-read', name: 'hub_inbox_mark_read', methods: ['POST'])]
    public function markAsRead(ReceivedDocument $document): JsonResponse
    {
        $user = $this->getUser();

        // Security: Ensure document belongs to user's tenant
        if ($document->getTargetTenant()->getId() !== $user->getTenant()->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $document->setIsRead(true);
        $this->documentRepository->getEntityManager()->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/document/{id}/retry', name: 'hub_inbox_retry', methods: ['POST'])]
    public function retryDocument(ReceivedDocument $document): JsonResponse
    {
        $user = $this->getUser();

        // Security: Ensure document belongs to user's tenant
        if ($document->getTargetTenant()->getId() !== $user->getTenant()->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Only allow retry for error or queued documents
        if (!in_array($document->getStatus(), ['error', 'queued'], true)) {
            return $this->json([
                'error' => 'Document cannot be retried',
                'message' => 'Only failed or queued documents can be retried'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Reset document status and dispatch for reprocessing
        $document->setStatus('new');
        $this->documentRepository->getEntityManager()->flush();

        // Dispatch message to reprocess
        $this->messageBus->dispatch(new ProcessDocumentMessage($document->getId()));

        // Log the retry action
        $this->auditService->logDocumentRetry($document->getId());

        return $this->json([
            'success' => true,
            'message' => 'Document has been queued for reprocessing'
        ]);
    }
}
