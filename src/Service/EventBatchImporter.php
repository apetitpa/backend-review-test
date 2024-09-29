<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ImportEvent;
use App\Repository\WriteEventRepository;
use Psr\Log\LoggerInterface;

class EventBatchImporter
{
    private array $eventBatch = [];

    public function __construct(
        private readonly WriteEventRepository $writeEventRepository,
        private readonly LoggerInterface $logger,
        private readonly int $batchSize = 500,
    ) {}

    public function addEvent(ImportEvent $event): void
    {
        $this->eventBatch[] = $event;

        if (count($this->eventBatch) >= $this->batchSize) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if (empty($this->eventBatch)) {
            return;
        }

        try {
            $this->writeEventRepository->batchImport($this->eventBatch);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to import batch', ['exception' => $e->getMessage()]);
        } finally {
            $this->eventBatch = [];
        }
    }
}
