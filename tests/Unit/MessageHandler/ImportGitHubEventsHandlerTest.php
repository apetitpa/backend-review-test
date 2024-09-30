<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Dto\ImportEvent;
use App\Message\ImportGitHubEventsMessage;
use App\MessageHandler\ImportGitHubEventsHandler;
use App\Service\EventBatchImporter;
use App\Service\FileDownloader;
use App\Service\GzippedJsonFileProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class ImportGitHubEventsHandlerTest extends TestCase
{
    private readonly FileDownloader $fileDownloader;
    private readonly DenormalizerInterface $denormalizer;
    private readonly ValidatorInterface $validator;
    private readonly GzippedJsonFileProcessor $fileProcessor;
    private readonly EventBatchImporter $batchImporter;
    private readonly LoggerInterface $logger;
    private readonly ImportGitHubEventsHandler $handler;

    protected function setUp(): void
    {
        $this->fileDownloader = $this->createMock(FileDownloader::class);
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->fileProcessor = $this->createMock(GzippedJsonFileProcessor::class);
        $this->batchImporter = $this->createMock(EventBatchImporter::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ImportGitHubEventsHandler(
            $this->fileDownloader,
            $this->denormalizer,
            $this->validator,
            $this->fileProcessor,
            $this->batchImporter,
            $this->logger
        );
    }

    public function testInvokeWithSuccessfulProcessing(): void
    {
        $message = new ImportGitHubEventsMessage('2024-09-29', 12);
        $fileName = sys_get_temp_dir() . '/2024-09-29-12.json.gz';
        $url = 'https://data.gharchive.org/2024-09-29-12.json.gz';

        // Create file on disk
        touch($fileName);

        $this->fileDownloader
            ->expects($this->once())
            ->method('download')
            ->with($url, $fileName)
            ->willReturn(true);

        $this->fileProcessor
            ->expects($this->once())
            ->method('process')
            ->with($fileName)
            ->willReturnCallback(function (string $fileName, callable $callback) {
                $line = ['id' => '123', 'type' => 'PushEvent', 'actor' => [], 'repo' => [], 'created_at' => '2024-09-29T12:34:56+00:00'];
                $callback($line);
            });

        $importEvent = $this->createMock(ImportEvent::class);
        $this->denormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(['id' => '123', 'type' => 'PushEvent', 'actor' => [], 'repo' => [], 'created_at' => '2024-09-29T12:34:56+00:00'], ImportEvent::class)
            ->willReturn($importEvent);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($importEvent)
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->batchImporter
            ->expects($this->once())
            ->method('addEvent')
            ->with($importEvent);

        $this->batchImporter
            ->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($message);

        $this->assertFileDoesNotExist($fileName);
    }

    public function testInvokeWithFailedDownload(): void
    {
        $message = new ImportGitHubEventsMessage('2024-09-29', 12);
        $fileName = sys_get_temp_dir() . '/2024-09-29-12.json.gz';
        $url = 'https://data.gharchive.org/2024-09-29-12.json.gz';

        $this->fileDownloader
            ->expects($this->once())
            ->method('download')
            ->with($url, $fileName)
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to download file', ['url' => $url]);

        $this->fileProcessor
            ->expects($this->never())
            ->method('process');

        $this->handler->__invoke($message);
    }
}
