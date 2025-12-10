<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ReceivedDocument;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-test-data',
    description: 'Seeds the database with test data for development and demo purposes',
)]
class SeedTestDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Seeding QbilHub Test Data');

        // Clear existing test data
        $io->section('Clearing existing test data...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\ReceivedDocument')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Tenant')->execute();
        $io->success('Cleared existing data');

        // Create Tenants
        $io->section('Creating Tenants...');

        $tenant1 = new Tenant();
        $tenant1->setName('Qbil Software');
        $tenant1->setTenantCode('QBIL001');
        $tenant1->setIsHubActive(true);
        $tenant1->setLogoUrl('/images/qbil-logo.png');
        $tenant1->setMetadata(['industry' => 'Software', 'country' => 'Netherlands']);

        $tenant2 = new Tenant();
        $tenant2->setName('ABC Dairy Co.');
        $tenant2->setTenantCode('ABC001');
        $tenant2->setIsHubActive(false);
        $tenant2->setMetadata(['industry' => 'Dairy', 'country' => 'Belgium']);

        $tenant3 = new Tenant();
        $tenant3->setName('XYZ Foods Ltd.');
        $tenant3->setTenantCode('XYZ001');
        $tenant3->setIsHubActive(false);
        $tenant3->setMetadata(['industry' => 'Food Processing', 'country' => 'Germany']);

        $this->entityManager->persist($tenant1);
        $this->entityManager->persist($tenant2);
        $this->entityManager->persist($tenant3);
        $this->entityManager->flush();

        $io->success('Created 3 tenants');

        // Create Users
        $io->section('Creating Users...');

        $admin = new User();
        $admin->setEmail('admin@qbilhub.com');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setTenant($tenant1);

        $processor = new User();
        $processor->setEmail('processor@qbilhub.com');
        $processor->setRoles(['ROLE_USER']);
        $processor->setPassword($this->passwordHasher->hashPassword($processor, 'processor123'));
        $processor->setFirstName('John');
        $processor->setLastName('Processor');
        $processor->setTenant($tenant1);

        $this->entityManager->persist($admin);
        $this->entityManager->persist($processor);
        $this->entityManager->flush();

        $io->success('Created 2 users (admin@qbilhub.com / admin123, processor@qbilhub.com / processor123)');

        // Create Sample Documents
        $io->section('Creating Sample Documents...');

        $documents = [
            [
                'type' => 'Purchase Order',
                'status' => 'new',
                'source' => $tenant2,
                'target' => $tenant1,
                'data' => [
                    'po_number' => 'PO-2024-001',
                    'supplier_name' => 'ABC Dairy Co.',
                    'product' => 'WPC 80',
                    'quantity' => '1000 kg',
                    'price' => '€4,500',
                    'delivery_date' => '2024-12-15',
                ],
            ],
            [
                'type' => 'Invoice',
                'status' => 'processing',
                'source' => $tenant3,
                'target' => $tenant1,
                'data' => [
                    'invoice_number' => 'INV-2024-045',
                    'supplier_name' => 'XYZ Foods Ltd.',
                    'product' => 'Skimmed Milk Powder',
                    'quantity' => '500 kg',
                    'amount_due' => '€2,200',
                    'due_date' => '2024-12-20',
                ],
            ],
            [
                'type' => 'Contract',
                'status' => 'mapped',
                'source' => $tenant2,
                'target' => $tenant1,
                'data' => [
                    'contract_no' => 'CTR-2024-012',
                    'supplier' => 'ABC Dairy Co.',
                    'product_code' => 'WPC80-EU',
                    'annual_volume' => '12,000 kg',
                    'contract_value' => '€54,000',
                    'start_date' => '2025-01-01',
                ],
            ],
            [
                'type' => 'Delivery Note',
                'status' => 'new',
                'source' => $tenant3,
                'target' => $tenant1,
                'data' => [
                    'delivery_note' => 'DN-2024-089',
                    'supplier' => 'XYZ Foods Ltd.',
                    'product' => 'Lactose',
                    'delivered_qty' => '250 kg',
                    'delivery_date' => '2024-12-01',
                ],
            ],
            [
                'type' => 'Purchase Order',
                'status' => 'delegated',
                'source' => $tenant2,
                'target' => $tenant1,
                'data' => [
                    'po_number' => 'PO-2024-002',
                    'supplier_name' => 'ABC Dairy Co.',
                    'product' => 'Butter 82%',
                    'quantity' => '2000 kg',
                    'price' => '€9,800',
                ],
            ],
        ];

        foreach ($documents as $docData) {
            $document = new ReceivedDocument();
            $document->setDocumentType($docData['type']);
            $document->setStatus($docData['status']);
            $document->setSourceTenant($docData['source']);
            $document->setTargetTenant($docData['target']);
            $document->setRawData($docData['data']);
            $document->setDocumentUrl('/uploads/sample-' . strtolower(str_replace(' ', '-', $docData['type'])) . '.pdf');
            // receivedAt is set in constructor
            $document->setIsRead(rand(0, 1) === 1);

            if ($docData['status'] === 'mapped') {
                $document->setExtractedSchema($docData['data']);
                $document->setConfidenceScores(array_fill_keys(array_keys($docData['data']), rand(75, 98) / 100));
            }

            if ($docData['status'] === 'delegated') {
                $document->setProcessedBy($processor);
            }

            $this->entityManager->persist($document);
        }

        $this->entityManager->flush();

        $io->success('Created 5 sample documents');

        // Summary
        $io->section('✅ Seeding Complete!');
        $io->table(
            ['Resource', 'Count'],
            [
                ['Tenants', '3'],
                ['Users', '2'],
                ['Documents', '5'],
            ]
        );

        $io->info('You can now log in with:');
        $io->listing([
            'Email: admin@qbilhub.com | Password: admin123',
            'Email: processor@qbilhub.com | Password: processor123',
        ]);

        return Command::SUCCESS;
    }
}
