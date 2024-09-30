<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command must import GitHub events.
 * You can add the parameters and code you want in this command to meet the need.
 */
#[AsCommand(name: 'app:import-github-events')]
class ImportGitHubEventsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Import GH events');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Let's rock !
        // It's up to you now

        return Command::SUCCESS;
    }
}
