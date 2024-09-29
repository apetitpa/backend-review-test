<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\EventInputDto;

interface WriteEventRepositoryInterface
{
    public function update(EventInputDto $authorInput, int $id): void;
}
