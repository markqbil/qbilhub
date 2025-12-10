<?php

namespace App\Command;

use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-tenant',
    description: 'Create a new tenant',
)]
class CreateTenantCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Tenant name')
            ->addArgument('tenantCode', InputArgument::REQUIRED, 'Tenant code (unique identifier)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $tenantCode = $input->getArgument('tenantCode');

        // Check if tenant already exists
        $existingTenant = $this->entityManager->getRepository(Tenant::class)->findOneBy(['tenantCode' => $tenantCode]);
        if ($existingTenant) {
            $io->error(sprintf('Tenant with code "%s" already exists', $tenantCode));
            return Command::FAILURE;
        }

        $tenant = new Tenant();
        $tenant->setName($name);
        $tenant->setTenantCode($tenantCode);
        $tenant->setIsHubActive(true);

        $this->entityManager->persist($tenant);
        $this->entityManager->flush();

        $io->success(sprintf('Tenant "%s" created successfully with ID: %d', $name, $tenant->getId()));

        return Command::SUCCESS;
    }
}
