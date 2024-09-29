<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Dto\ImportEvent;
use App\Message\ImportGitHubEventsMessage;
use App\Service\EventBatchImporter;
use App\Service\FileDownloader;
use App\Service\GzippedJsonFileProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
class ImportGitHubEventsHandler
{
    private const URL_TEMPLATE = 'https://data.gharchive.org/%s-%d.json.gz';

    public function __construct(
        private readonly FileDownloader $fileDownloader,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly GzippedJsonFileProcessor $fileProcessor,
        private readonly EventBatchImporter $batchImporter,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ImportGitHubEventsMessage $message): void
    {
        $date = $message->getDate();
        $hour = $message->getHour();

        $url = sprintf(self::URL_TEMPLATE, $date, $hour);

        $tempDir = sys_get_temp_dir();
        $fileName = "$tempDir/$date-$hour.json.gz";

        $downloaded = $this->fileDownloader->download($url, $fileName);

        if (!$downloaded) {
            $this->logger->error('Failed to download file', ['url' => $url]);
            return;
        }

        $this->fileProcessor->process($fileName, function (string $line): void {
            $event = $this->serializer->deserialize($line, ImportEvent::class, 'json');
            $errors = $this->validator->validate($event);

            if (count($errors) > 0) {
                return;
            }

            $this->batchImporter->addEvent($event);
        });

        $this->batchImporter->flush();

        unlink($fileName);
    }
}
