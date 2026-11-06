<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Repositories\WorkerJobRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:status', description: 'Show worker jobs status by type and status')]
final class StatusCommand extends Command
{
    public function __construct(
        private readonly WorkerJobRepositoryInterface $workerJobRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counts = $this->workerJobRepository->countByTypeAndStatus();

        if ($counts === []) {
            $output->writeln('<info>No worker jobs.</info>');

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Type', 'Status', 'Count']);

        foreach ($counts as $type => $statuses) {
            foreach ($statuses as $status => $count) {
                $table->addRow([$type, $status, (string) $count]);
            }
        }

        $table->render();

        return Command::SUCCESS;
    }
}
