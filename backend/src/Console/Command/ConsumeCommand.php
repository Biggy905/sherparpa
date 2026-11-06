<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Factories\ConsumerBusFactory;
use App\Factories\TransportFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Worker;

#[AsCommand(name: 'app:consume', description: 'Run Messenger worker for a given transport')]
final class ConsumeCommand extends Command implements SignalableCommandInterface
{
    private ?Worker $worker = null;

    public function __construct(
        private readonly TransportFactory $transportFactory,
        private readonly ConsumerBusFactory $consumerBusFactory,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'transport',
            InputArgument::REQUIRED,
            'Transport name (import|images)',
        );
    }

    /**
     * @return list<int>
     */
    public function getSubscribedSignals(): array
    {
        return [\SIGTERM, \SIGINT];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        if ($this->worker !== null) {
            $this->worker->stop();
        }

        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transportName = $input->getArgument('transport');
        \assert(\is_string($transportName));

        if (!\in_array($transportName, ['import', 'images'], true)) {
            $output->writeln(\sprintf('<error>Unknown transport: %s</error>', $transportName));

            return Command::FAILURE;
        }

        $transport = $this->transportFactory->create($transportName);
        $bus = $this->consumerBusFactory->create();

        $this->worker = new Worker(
            [$transport],
            $bus,
            new EventDispatcher(),
            $this->logger,
        );

        $output->writeln(\sprintf('<info>Consuming "%s" transport. Press Ctrl+C to stop.</info>', $transportName));

        $this->worker->run();

        return Command::SUCCESS;
    }
}
