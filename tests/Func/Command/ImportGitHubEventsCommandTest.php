<?php

declare(strict_types=1);

namespace App\Tests\Func\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ImportGitHubEventsCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:import-github-events');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'date' => '2018-02-14',
            'start-hour' => 0,
            'end-hour' => 0,
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteWithInvalidDate(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:import-github-events');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'date' => 'abcdefg',
        ]);

        $this->assertStringContainsString('Invalid date format, please use YYYY-MM-DD, UTC', $commandTester->getDisplay());
    }

    public function testExecuteWithInvalidStartHour(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:import-github-events');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'date' => '2018-02-14',
            'start-hour' => '-1',
        ]);

        $this->assertStringContainsString('Invalid hour format, please use a number between 0 and 23', $commandTester->getDisplay());
    }
}
