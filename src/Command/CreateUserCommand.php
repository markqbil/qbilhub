<?php

namespace App\Command;

use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addArgument('firstName', InputArgument::REQUIRED, 'First name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Last name')
            ->addOption('tenant-id', null, InputOption::VALUE_REQUIRED, 'Tenant ID (uses first tenant if not specified)')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Make user an admin')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Make user a super admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists', $email));
            return Command::FAILURE;
        }

        // Get or find tenant
        $tenantId = $input->getOption('tenant-id');
        if ($tenantId) {
            $tenant = $this->entityManager->getRepository(Tenant::class)->find($tenantId);
            if (!$tenant) {
                $io->error(sprintf('Tenant with ID "%s" not found', $tenantId));
                return Command::FAILURE;
            }
        } else {
            // Get first tenant
            $tenant = $this->entityManager->getRepository(Tenant::class)->findOneBy([]);
            if (!$tenant) {
                $io->error('No tenants found. Please create a tenant first or specify --tenant-id');
                return Command::FAILURE;
            }
            $io->note(sprintf('Using tenant: %s (ID: %d)', $tenant->getName(), $tenant->getId()));
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setTenant($tenant);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Set roles
        $roles = ['ROLE_USER'];
        if ($input->getOption('super-admin')) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        } elseif ($input->getOption('admin')) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User "%s %s" (%s) created successfully with roles: %s', $firstName, $lastName, $email, implode(', ', $roles)));

        return Command::SUCCESS;
    }
}
