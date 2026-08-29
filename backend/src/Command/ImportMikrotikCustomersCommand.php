<?php

namespace App\Command;

use App\Service\Mikrotik\MikrotikCustomerImporter;
use App\Service\Mikrotik\MikrotikQueueReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mikrotik:import-customers',
    description: 'Imports missing customers from MikroTik simple queues.'
)]
class ImportMikrotikCustomersCommand extends Command
{
    public function __construct(
        private readonly MikrotikQueueReader $queueReader,
        private readonly MikrotikCustomerImporter $customerImporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Read and calculate import results without persisting customers or assigned plans.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $queueReadResult = $this->queueReader->readSimpleQueues();
            $result = $this->customerImporter->import($queueReadResult, $dryRun);
        } catch (\Throwable $exception) {
            $io->error('MikroTik customer import failed: ' . $exception->getMessage());

            return Command::FAILURE;
        }

        $io->title($dryRun ? 'MikroTik customer import dry-run' : 'MikroTik customer import');
        $io->listing([
            sprintf('Queues read: %d', $result->queuesRead),
            sprintf('New customers: %d', $result->created),
            sprintf('Existing assigned plans: %d', $result->existing),
            sprintf('Invalid queues: %d', $result->invalid),
            sprintf('Ambiguous assigned plans: %d', $result->ambiguous),
            sprintf('Plans discovered: %d', $result->plansDiscovered),
            sprintf('New plans: %d', $result->newPlans),
            sprintf('Existing plans: %d', $result->existingPlans),
            sprintf('Customer plans to create: %d', $result->customerPlansToCreate),
        ]);

        if ($dryRun) {
            $io->section('Dry-run synchronization');
            $io->listing([
                sprintf('IP addresses to update: %d', $result->ipAddressesToUpdate),
                sprintf('Plans to update: %d', $result->plansToUpdate),
                sprintf('MAC addresses found: %d', $result->macAddressesFound),
                sprintf('MAC addresses to complete: %d', $result->macAddressesToComplete),
                sprintf('MAC addresses to update: %d', $result->macAddressesToUpdate),
            ]);
            $io->note('Dry-run completed without persisting Customers, Plans or CustomerPlans.');
        }

        return Command::SUCCESS;
    }
}
