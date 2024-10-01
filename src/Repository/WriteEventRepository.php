<?php

namespace App\Repository;

use App\Dto\EventInput;
use App\Dto\ImportEvent;

interface WriteEventRepository
{
    public function update(EventInput $authorInput, int $id): void;

    /**
     * @param ImportEvent[] $importEvents
     */
    public function batchImport(array $importEvents): void;
}
