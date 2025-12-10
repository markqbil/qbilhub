<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ReceivedDocument;
use App\Repository\ReceivedDocumentRepository;
use App\Service\QbilTradeApiClient;
use App\Service\QbilTradeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export-qbil-contracts',
    description: 'Export processed contracts from QbilHub to Qbil Trade as orders',
)]
class ExportQbilContractsCommand extends Command
{
    public function __construct(
        private readonly QbilTradeApiClient $qbilApi,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReceivedDocumentRepository $documentRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL', 'Maximum number of contracts to export', 10)
            ->addOption('document-id', 'd', InputOption::VALUE_OPTIONAL, 'Specific document ID to export')
            ->addOption('flip-direction', 'f', InputOption::VALUE_NONE, 'Flip buyer/seller (purchase → sales)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview without actually sending')
            ->setHelp(<<<'HELP'
This command exports processed contracts from QbilHub to Qbil Trade API as new orders.

The command can automatically flip the direction of contracts (purchase → sales)
to handle the receiving party's perspective.

Examples:
  # Export up to 10 processed contracts
  php bin/console app:export-qbil-contracts

  # Export specific document
  php bin/console app:export-qbil-contracts --document-id=123

  # Flip direction (convert purchase to sales)
  php bin/console app:export-qbil-contracts --flip-direction

  # Preview without sending
  php bin/console app:export-qbil-contracts --dry-run
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Exporting Contracts to Qbil Trade');

        $limit = (int) $input->getOption('limit');
        $documentId = $input->getOption('document-id');
        $flipDirection = $input->getOption('flip-direction');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('DRY RUN MODE - No data will be sent to Qbil Trade');
        }

        try {
            // Verify API connection
            $io->section('Verifying API Connection');
            $apiUser = $this->qbilApi->getMe();
            $io->success('Connected to Qbil Trade as: ' . ($apiUser['name'] ?? 'Unknown'));

            // Get documents to export
            $documents = $this->getDocumentsToExport($documentId, $limit);

            if (empty($documents)) {
                $io->warning('No processed contracts found to export');
                $io->note('Documents must have status "mapped" and documentType "Contract" to be exported');
                return Command::SUCCESS;
            }

            $io->text(sprintf('Found %d contracts ready for export', count($documents)));

            // Export each contract
            $io->section($dryRun ? 'Preview Export' : 'Exporting Contracts');
            $progressBar = $io->createProgressBar(count($documents));
            $progressBar->start();

            $exported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($documents as $document) {
                try {
                    $result = $this->exportContract($document, $flipDirection, $dryRun, $io);

                    if ($result['success']) {
                        $exported++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'document_id' => $document->getId(),
                        'error' => $e->getMessage(),
                    ];
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);

            // Show results
            $io->section('Export Summary');

            if ($dryRun) {
                $io->info(sprintf('Would export: %d contracts', $exported));
            } else {
                $io->success(sprintf('Successfully exported: %d contracts', $exported));
            }

            if ($skipped > 0) {
                $io->warning(sprintf('Skipped: %d contracts', $skipped));
            }

            if (!empty($errors)) {
                $io->error(sprintf('Failed to export: %d contracts', count($errors)));
                $io->table(
                    ['Document ID', 'Error'],
                    array_map(fn($e) => [$e['document_id'], $e['error']], $errors)
                );
            }

            return Command::SUCCESS;

        } catch (QbilTradeException $e) {
            $io->error('Qbil Trade API Error: ' . $e->getMessage());

            if ($e->isAuthenticationError()) {
                $io->note('Please check your QBIL_TRADE_API_TOKEN in .env file');
            }

            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error('Unexpected error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getDocumentsToExport(?string $documentId, int $limit): array
    {
        if ($documentId) {
            $document = $this->documentRepository->find($documentId);
            return $document ? [$document] : [];
        }

        // Get mapped contracts that haven't been exported yet
        return $this->documentRepository->findBy(
            [
                'documentType' => 'Contract',
                'status' => 'mapped',
            ],
            ['id' => 'ASC'],
            $limit
        );
    }

    private function exportContract(
        ReceivedDocument $document,
        bool $flipDirection,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $rawData = $document->getRawData();
        $extractedSchema = $document->getExtractedSchema() ?? $rawData;

        // Build order payload
        $orderData = $this->buildOrderPayload($extractedSchema, $flipDirection);

        if ($dryRun) {
            $io->writeln('');
            $io->section('Would export document #' . $document->getId());
            $io->writeln('Order data:');
            $io->writeln(json_encode($orderData, JSON_PRETTY_PRINT));
            return ['success' => true, 'dry_run' => true];
        }

        // Send to Qbil Trade API
        $response = $this->qbilApi->createOrder($orderData);

        // Update document status
        $document->setStatus('delegated'); // Mark as exported
        $metadata = $document->getMetadata() ?? [];
        $metadata['qbil_trade_export'] = [
            'order_id' => $response['id'] ?? null,
            'exported_at' => date('Y-m-d H:i:s'),
            'flipped' => $flipDirection,
        ];
        $document->setMetadata($metadata);

        $this->entityManager->flush();

        return ['success' => true, 'order_id' => $response['id'] ?? null];
    }

    private function buildOrderPayload(array $contractData, bool $flipDirection): array
    {
        $payload = [
            'type' => $flipDirection ? 'sales_order' : 'purchase_order',
            'external_reference' => $contractData['contract_number'] ?? null,
            'notes' => 'Processed via QbilHub',
        ];

        // Handle direction flipping
        if ($flipDirection) {
            // Purchase → Sales: flip buyer/seller
            $payload['buyer'] = $contractData['seller'] ?? null;
            $payload['seller'] = $contractData['buyer'] ?? null;
        } else {
            $payload['buyer'] = $contractData['buyer'] ?? null;
            $payload['seller'] = $contractData['seller'] ?? null;
        }

        // Add financial data
        if (isset($contractData['total_amount'])) {
            $payload['total_amount'] = $contractData['total_amount'];
        }

        if (isset($contractData['currency'])) {
            $payload['currency'] = $contractData['currency'];
        }

        // Add delivery information
        if (isset($contractData['delivery_date'])) {
            $payload['delivery_date'] = $contractData['delivery_date'];
        }

        if (isset($contractData['delivery_address'])) {
            $payload['delivery_address'] = $contractData['delivery_address'];
        }

        // Add line items
        if (!empty($contractData['items'])) {
            $payload['items'] = array_map(function ($item) {
                return [
                    'product_code' => $item['product_code'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'total' => (float) ($item['total'] ?? 0),
                ];
            }, $contractData['items']);
        }

        // Payment terms
        if (isset($contractData['payment_terms'])) {
            $payload['payment_terms'] = $contractData['payment_terms'];
        }

        return $payload;
    }
}
