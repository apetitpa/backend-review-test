<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\EventInputDto;
use Doctrine\DBAL\Connection;

class DbalWriteEventRepository implements WriteEventRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function update(EventInputDto $authorInput, int $id): void
    {
        $sql = <<<SQL
            UPDATE event
            SET comment = :comment
            WHERE id = :id
        SQL;

        $this->connection->executeQuery($sql, ['id' => $id, 'comment' => $authorInput->comment]);
    }
}
