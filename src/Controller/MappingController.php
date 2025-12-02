<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PurchaseContract;
use App\Entity\ReceivedDocument;
use App\Message\ActiveLearningFeedbackMessage;
use App\Repository\PurchaseContractRepository;
use App\Repository\ReceivedDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hub/mapping')]
#[IsGranted('ROLE_USER')]
class MappingController extends AbstractController
{
    public function __construct(
        private readonly ReceivedDocumentRepository $documentRepository,
        private readonly PurchaseContractRepository $contractRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route('/document/{id}', name: 'hub_mapping_view', methods: ['GET'])]
    public function view(ReceivedDocument $document): Response
    {
        $user = $this->getUser();

        // Security: Ensure document belongs to user's tenant
        if ($document->getTargetTenant()->getId() !== $user->getTenant()->getId()) {
            throw $this->createAccessDeniedException('Access denied');
        }

        return $this->render('hub/mapping.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/document/{id}/data', name: 'hub_mapping_data', methods: ['GET'])]
    public function getData(ReceivedDocument $document): JsonResponse
    {
        $user = $this->getUser();

        // Security: Ensure document belongs to user's tenant
        if ($document->getTargetTenant()->getId() !== $user->getTenant()->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $document->getId(),
            'rawData' => $document->getRawData(),
            'extractedSchema' => $document->getExtractedSchema(),
            'mappedData' => $document->getMappedData(),
            'confidenceScores' => $document->getConfidenceScores(),
            'documentUrl' => $document->getDocumentUrl(),
        ]);
    }

    #[Route('/document/{id}/save', name: 'hub_mapping_save', methods: ['POST'])]
    public function save(ReceivedDocument $document, Request $request): JsonResponse
    {
        $user = $this->getUser();

        // Security: Ensure document belongs to user's tenant
        if ($document->getTargetTenant()->getId() !== $user->getTenant()->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Create or update purchase contract
        $contract = new PurchaseContract();
        $contract->setTenant($user->getTenant());
        $contract->setContractNumber($data['contractNumber']);
        $contract->setSupplier($data['supplier']);
        $contract->setProduct($data['product']);
        $contract->setQuantity($data['quantity']);
        $contract->setUnit($data['unit']);
        $contract->setPricePerUnit($data['pricePerUnit']);
        $contract->setCurrency($data['currency']);
        $contract->setDeliveryDate(new \DateTimeImmutable($data['deliveryDate']));
        $contract->setDeliveryLocation($data['deliveryLocation'] ?? null);
        $contract->setAdditionalTerms($data['additionalTerms'] ?? null);
        $contract->setStatus('active');
        $contract->setCreatedBy($user);

        $this->entityManager->persist($contract);

        // Update document
        $document->setLinkedContract($contract);
        $document->setStatus('processed');
        $document->setProcessedBy($user);
        $document->setProcessedAt(new \DateTimeImmutable());

        // Handle active learning feedback for corrections
        if (isset($data['corrections']) && is_array($data['corrections'])) {
            foreach ($data['corrections'] as $correction) {
                $this->messageBus->dispatch(new ActiveLearningFeedbackMessage(
                    $document->getSourceTenant()->getTenantCode(),
                    $document->getTargetTenant()->getTenantCode(),
                    $correction['sourceField'],
                    $correction['sourceValue'],
                    $correction['targetField'],
                    $correction['correctedValue']
                ));
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'contractId' => $contract->getId(),
        ]);
    }

    #[Route('/document/{id}/feedback', name: 'hub_mapping_feedback', methods: ['POST'])]
    public function submitFieldFeedback(ReceivedDocument $document, Request $request): JsonResponse
    {
        $user = $this->getUser();

        // Security: Ensure document belongs to user's tenant
        if ($document->getTargetTenant()->getId() !== $user->getTenant()->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Send feedback to Python service for active learning
        $this->messageBus->dispatch(new ActiveLearningFeedbackMessage(
            $document->getSourceTenant()->getTenantCode(),
            $document->getTargetTenant()->getTenantCode(),
            $data['sourceField'],
            $data['sourceValue'],
            $data['targetField'],
            $data['correctedValue']
        ));

        return $this->json(['success' => true]);
    }
}
