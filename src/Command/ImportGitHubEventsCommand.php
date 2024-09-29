<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\ImportGitHubEventsMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:import-github-events')]
class ImportGitHubEventsCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import GH events')
            ->setHelp('This command allows you to import GH events')
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Date to import events from, format: YYYY-MM-DD, UTC',
                (new \DateTimeImmutable(timezone: new \DateTimeZone('UTC')))->format('Y-m-d')
            )
            ->addArgument('start-hour', InputArgument::OPTIONAL, 'Start hour, UTC', 0)
            ->addArgument('end-hour', InputArgument::OPTIONAL, 'End hour, UTC', 23)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $date = $input->getArgument('date');
        $startHour = (int) $input->getArgument('start-hour');
        $endHour = (int) $input->getArgument('end-hour');

        if (!$this->validateInput($date, $startHour, $endHour, $io)) {
            return Command::FAILURE;
        }

        $formatedDate = (new \DateTimeImmutable($date))->format('Y-m-d');

        $io->title("Dispatching import messages for $formatedDate from $startHour to $endHour");

        for ($i = $startHour; $i <= $endHour; $i++) {
            $this->bus->dispatch(new ImportGitHubEventsMessage($formatedDate, $i));
            $io->info("Dispatched ImportGitHubEventsMessage for $formatedDate hour $i");
        }

        $io->success('All import messages have been dispatched.');

        return Command::SUCCESS;
    }

    private function validateInput(string $date, int $startHour, int $endHour, SymfonyStyle $io): bool
    {
        if (!\DateTimeImmutable::createFromFormat('Y-m-d', $date)) {
            $io->error('Invalid date format, please use YYYY-MM-DD, UTC');
            return false;
        }

        if ($startHour < 0 || $startHour > 23 || $endHour < 0 || $endHour > 23) {
            $io->error('Invalid hour format, please use a number between 0 and 23');
            return false;
        }

        if ($endHour < $startHour) {
            $io->error('End hour must be greater than or equal to start hour');
            return false;
        }

        return true;
    }
}
