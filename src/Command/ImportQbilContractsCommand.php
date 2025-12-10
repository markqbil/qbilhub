<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ReceivedDocument;
use App\Entity\Tenant;
use App\Repository\TenantRepository;
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
    name: 'app:import-qbil-contracts',
    description: 'Import contracts from Qbil Trade API into Hub Inbox',
)]
class ImportQbilContractsCommand extends Command
{
    public function __construct(
        private readonly QbilTradeApiClient $qbilApi,
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantRepository $tenantRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of contracts to import', 10)
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'Filter by contract status')
            ->addOption('tenant-code', 't', InputOption::VALUE_OPTIONAL, 'Tenant code to assign contracts to')
            ->setHelp(<<<'HELP'
This command imports contracts from Qbil Trade API into the QbilHub inbox.

Examples:
  # Import up to 10 contracts
  php bin/console app:import-qbil-contracts

  # Import 50 contracts
  php bin/console app:import-qbil-contracts --limit=50

  # Import only active contracts
  php bin/console app:import-qbil-contracts --status=active

  # Import to specific tenant
  php bin/console app:import-qbil-contracts --tenant-code=QBIL001
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importing Contracts from Qbil Trade');

        // Get options
        $limit = (int) $input->getOption('limit');
        $status = $input->getOption('status');
        $tenantCode = $input->getOption('tenant-code');

        try {
            // Verify API connection
            $io->section('Verifying API Connection');
            $apiUser = $this->qbilApi->getMe();
            $io->success('Connected to Qbil Trade as: ' . ($apiUser['name'] ?? 'Unknown'));

            // Get or create default tenant
            $hubTenant = $this->getOrCreateHubTenant($tenantCode);
            $io->info('Using tenant: ' . $hubTenant->getName() . ' (' . $hubTenant->getTenantCode() . ')');

            // Fetch contracts from API
            $io->section('Fetching Contracts');
            $filters = ['limit' => $limit];
            if ($status) {
                $filters['status'] = $status;
            }

            $response = $this->qbilApi->listContracts($filters);
            $contracts = $response['data'] ?? [];

            if (empty($contracts)) {
                $io->warning('No contracts found matching the criteria');
                return Command::SUCCESS;
            }

            $io->text(sprintf('Found %d contracts to import', count($contracts)));

            // Import each contract
            $io->section('Importing Contracts');
            $progressBar = $io->createProgressBar(count($contracts));
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($contracts as $contractData) {
                try {
                    if ($this->importContract($contractData, $hubTenant)) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'contract' => $contractData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);

            // Show results
            $io->section('Import Summary');
            $io->success(sprintf('Successfully imported: %d contracts', $imported));

            if ($skipped > 0) {
                $io->warning(sprintf('Skipped (already exists): %d contracts', $skipped));
            }

            if (!empty($errors)) {
                $io->error(sprintf('Failed to import: %d contracts', count($errors)));
                $io->table(['Contract ID', 'Error'], array_map(fn($e) => [$e['contract'], $e['error']], $errors));
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

    private function importContract(array $contractData, Tenant $tenant): bool
    {
        $contractId = $contractData['id'] ?? null;
        if (!$contractId) {
            return false;
        }

        // Check if already exists
        $existing = $this->entityManager->getRepository(ReceivedDocument::class)
            ->findOneBy(['externalId' => $contractId]);

        if ($existing) {
            return false; // Skip duplicates
        }

        // Create new received document
        $document = new ReceivedDocument();
        $document->setDocumentType('Contract');
        $document->setStatus('new');
        $document->setSourceTenant($tenant);
        $document->setTargetTenant($tenant); // Will be updated during processing
        $document->setExternalId($contractId);

        // Store contract data
        $rawData = [
            'contract_number' => $contractData['contract_number'] ?? $contractId,
            'contract_type' => $contractData['type'] ?? 'purchase',
            'buyer' => $contractData['buyer']['name'] ?? 'Unknown',
            'seller' => $contractData['seller']['name'] ?? 'Unknown',
            'total_amount' => $contractData['total_amount'] ?? null,
            'currency' => $contractData['currency'] ?? 'EUR',
            'delivery_date' => $contractData['delivery_date'] ?? null,
            'status' => $contractData['status'] ?? 'pending',
            'created_at' => $contractData['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        // Add line items if available
        if (!empty($contractData['items'])) {
            $rawData['items'] = array_map(function ($item) {
                return [
                    'product_code' => $item['product_code'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total' => $item['total'] ?? 0,
                ];
            }, $contractData['items']);
        }

        $document->setRawData($rawData);
        $document->setDocumentUrl('qbil-trade://contracts/' . $contractId);
        $document->setIsRead(false);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return true;
    }

    private function getOrCreateHubTenant(?string $tenantCode): Tenant
    {
        if ($tenantCode) {
            $tenant = $this->tenantRepository->findOneBy(['tenantCode' => $tenantCode]);
            if ($tenant) {
                return $tenant;
            }
        }

        // Get or create default "Qbil Trade" tenant
        $tenant = $this->tenantRepository->findOneBy(['tenantCode' => 'QBILTRADE']);

        if (!$tenant) {
            $tenant = new Tenant();
            $tenant->setName('Qbil Trade');
            $tenant->setTenantCode('QBILTRADE');
            $tenant->setIsHubActive(true);

            $this->entityManager->persist($tenant);
            $this->entityManager->flush();
        }

        return $tenant;
    }
}
