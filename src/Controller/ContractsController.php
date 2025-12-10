<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ReceivedDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contracts')]
class ContractsController extends AbstractController
{
    public function __construct(
        private readonly ReceivedDocumentRepository $documentRepository
    ) {
    }

    #[Route('', name: 'app_contracts', methods: ['GET'])]
    public function index(): Response
    {
        // Show only contract-type documents
        $contracts = $this->documentRepository->findBy(
            ['documentType' => 'Contract'],
            ['receivedAt' => 'DESC']
        );

        return $this->render('contracts/index.html.twig', [
            'contracts' => $contracts,
        ]);
    }
}
