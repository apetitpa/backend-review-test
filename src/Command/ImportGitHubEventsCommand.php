<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\ImportEvent;
use App\Repository\WriteEventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:import-github-events')]
class ImportGitHubEventsCommand extends Command
{
    public const URL_TEMPLATE = 'https://data.gharchive.org/%s-%d.json.gz';

    public const BATCH_SIZE = 500;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly SerializerInterface $serializer,
        private readonly WriteEventRepository $writeEventRepository,
        private readonly ValidatorInterface $validator,
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
        $formatedDate = (new \DateTimeImmutable($date))->format('Y-m-d');
        $startHour = (int) $input->getArgument('start-hour');
        $endHour = (int) $input->getArgument('end-hour');

        if (!$this->validateInput($formatedDate, $startHour, $endHour, $io)) {
            return Command::FAILURE;
        }

        $io->title("Importing events for $formatedDate from $startHour to $endHour");
        $io->progressStart($endHour - $startHour + 1);

        $globalStats = [
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        for ($i = $startHour; $i < $endHour + 1; $i++) {
            $url = sprintf(self::URL_TEMPLATE, $formatedDate, $i);

            $io->newLine();
            $io->info("Importing $url");

            $fileName = $this->downloadFile($url, $formatedDate, $i, $io);

            if ($fileName === null) {
                $io->progressAdvance();
                continue;
            }

            $fileStats = $this->processGzippedJsonFile($fileName, $io);

            $io->info("Successfully imported $url");
            $this->displayFileStats($fileName, $fileStats, $io);

            $this->updateGlobalStats($globalStats, $fileStats);

            unlink($fileName);

            $io->progressAdvance();
        }

        $this->displayFinalStats($globalStats, $io);

        return Command::SUCCESS;
    }

    private function validateInput(string $date, int $startHour, int $endHour, SymfonyStyle $io): bool
    {
        if (!\DateTimeImmutable::createFromFormat('Y-m-d', $date)) {
            $io->error('Invalid date format, please use YYYY-MM-DD');
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

    private function downloadFile(string $url, string $date, int $hour, SymfonyStyle $io): ?string
    {
        try {
            $response = $this->client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                $io->warning("Failed to import $url, status code: {$response->getStatusCode()}");
                return null;
            }

            $tempDir = sys_get_temp_dir();
            $fileName = "$tempDir/$date-$hour.json.gz";

            $fileHandler = fopen($fileName, 'w');
            foreach ($this->client->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
            }
            fclose($fileHandler);

            return $fileName;
        } catch (TransportExceptionInterface $e) {
            $io->warning("Failed to import $url - " . $e->getMessage());
            return null;
        }
    }

    private function processGzippedJsonFile(string $fileName, SymfonyStyle $io): array
    {
        $eventsProcessed = 0;
        $eventsImported = 0;
        $eventsSkipped = 0;
        $eventsFailed = 0;

        $eventBatch = [];

        $gzFileHandler = gzopen($fileName, 'rb');
        if ($gzFileHandler === false) {
            $io->warning("Failed to open {$fileName}");
            return ['processed' => 0, 'imported' => 0, 'skipped' => 0, 'failed' => 0];
        }

        while (!gzeof($gzFileHandler)) {
            $line = gzgets($gzFileHandler);
            if ($line === false) {
                continue;
            }

            $eventsProcessed++;

            $event = $this->serializer->deserialize($line, ImportEvent::class, 'json');
            $errors = $this->validator->validate($event);

            if (count($errors) > 0) {
                $eventsSkipped++;
                continue;
            }

            $eventBatch[] = $event;

            if (count($eventBatch) >= self::BATCH_SIZE) {
                $this->importBatch($eventBatch, $eventsImported, $eventsFailed, $io);
                $eventBatch = [];
            }
        }

        if (count($eventBatch) > 0) {
            $this->importBatch($eventBatch, $eventsImported, $eventsFailed, $io);
        }

        gzclose($gzFileHandler);

        return [
            'processed' => $eventsProcessed,
            'imported' => $eventsImported,
            'skipped' => $eventsSkipped,
            'failed' => $eventsFailed,
        ];
    }

    private function importBatch(array $eventBatch, int &$eventsImported, int &$eventsFailed, SymfonyStyle $io): void
    {
        try {
            $this->writeEventRepository->batchImport($eventBatch);
            $eventsImported += count($eventBatch);
        } catch (\Throwable $e) {
            $eventsFailed += count($eventBatch);
            $io->warning("Failed to import batch - " . $e->getMessage());
        }
    }

    private function updateGlobalStats(array &$globalStats, array $fileStats): void
    {
        foreach ($globalStats as $key => &$value) {
            $value += $fileStats[$key];
        }
    }

    private function displayFileStats(string $fileName, array $fileStats, SymfonyStyle $io): void
    {
        $io->section("Statistics for $fileName");
        $io->listing([
            "Events processed: {$fileStats['processed']}",
            "Events imported: {$fileStats['imported']}",
            "Events skipped: {$fileStats['skipped']}",
            "Events failed: {$fileStats['failed']}",
        ]);
    }

    private function displayFinalStats(array $globalStats, SymfonyStyle $io): void
    {
        $io->newLine(2);
        $io->success("Import completed");
        $io->title("Global Statistics");
        $io->listing([
            "Total events processed: {$globalStats['processed']}",
            "Total events imported: {$globalStats['imported']}",
            "Total events skipped: {$globalStats['skipped']}",
            "Total events failed: {$globalStats['failed']}",
        ]);
    }
}
