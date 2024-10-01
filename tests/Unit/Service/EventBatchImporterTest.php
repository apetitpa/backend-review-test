<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\ImportEvent;
use App\Repository\WriteEventRepository;
use App\Service\EventBatchImporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventBatchImporterTest extends TestCase
{
    private readonly EventBatchImporter $batchImporter;
    private readonly WriteEventRepository $writeEventRepositoryMock;
    private readonly LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->writeEventRepositoryMock = $this->createMock(WriteEventRepository::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->batchImporter = new EventBatchImporter(
            $this->writeEventRepositoryMock,
            $this->loggerMock,
            2
        );
    }

    public function testBatchImportSuccess(): void
    {
        $event1 = $this->createMock(ImportEvent::class);
        $event2 = $this->createMock(ImportEvent::class);

        $this->writeEventRepositoryMock
            ->expects($this->once())
            ->method('batchImport')
            ->with($this->equalTo([$event1, $event2]));

        $this->batchImporter->addEvent($event1);
        $this->batchImporter->addEvent($event2);
    }

    public function testFlushDoesNotImportWhenBatchIsEmpty(): void
    {
        $this->writeEventRepositoryMock
            ->expects($this->never())
            ->method('batchImport');

        $this->batchImporter->flush();
    }

    public function testBatchImportHandlesException(): void
    {
        $event1 = $this->createMock(ImportEvent::class);
        $event2 = $this->createMock(ImportEvent::class);

        $exception = new \Exception('Database error');

        $this->writeEventRepositoryMock
            ->expects($this->once())
            ->method('batchImport')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to import batch', ['exception' => $exception->getMessage()]);

        $this->batchImporter->addEvent($event1);
        $this->batchImporter->addEvent($event2);
    }
}
