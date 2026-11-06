<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entities\WorkerJob;
use App\Enums\WorkerJobStatus;
use App\Enums\WorkerJobType;
use App\Messages\DownloadProductImageMessage;
use App\Messages\ImportProductsMessage;
use App\Repositories\WorkerJobRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:retry-failed', description: 'Re-dispatch failed worker jobs for a given type')]
final class RetryFailedCommand extends Command
{
    public function __construct(
        private readonly WorkerJobRepositoryInterface $workerJobRepository,
        private readonly MessageBusInterface $bus,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'transport',
            InputArgument::REQUIRED,
            'Worker job type (import|images)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transportName = $input->getArgument('transport');
        \assert(\is_string($transportName));

        $type = match ($transportName) {
            'import' => WorkerJobType::Import,
            'images' => WorkerJobType::ImageDownload,
            default => null,
        };

        if ($type === null) {
            $output->writeln(\sprintf('<error>Unknown transport: %s</error>', $transportName));

            return Command::FAILURE;
        }

        $failedJobs = $this->workerJobRepository->findByTypeAndStatus($type, WorkerJobStatus::Failed);

        if ($failedJobs === []) {
            $output->writeln('<info>No failed jobs to retry.</info>');

            return Command::SUCCESS;
        }

        $dispatched = 0;

        foreach ($failedJobs as $job) {
            $job->markProcessing();
            $this->workerJobRepository->save($job, false);

            if ($this->dispatchRetry($job, $type, $output)) {
                ++$dispatched;
            }
        }

        $this->em->flush();

        $output->writeln(\sprintf('<info>Re-dispatched %d jobs.</info>', $dispatched));

        return Command::SUCCESS;
    }

    private function dispatchRetry(WorkerJob $job, WorkerJobType $type, OutputInterface $output): bool
    {
        try {
            match ($type) {
                WorkerJobType::Import => $this->bus->dispatch(
                    new ImportProductsMessage($job->getId(), $job->getImportPayload()->file),
                ),
                WorkerJobType::ImageDownload => $this->bus->dispatch(
                    new DownloadProductImageMessage($job->getImageDownloadPayload()->imageId),
                ),
            };

            return true;
        } catch (\InvalidArgumentException $e) {
            $output->writeln(\sprintf(
                '<error>Job %s has invalid payload: %s</error>',
                $job->getId(),
                $e->getMessage(),
            ));

            return false;
        }
    }
}
