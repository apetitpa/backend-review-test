<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\EventInput;
use App\Dto\ImportEvent;
use App\Entity\EventType;
use Doctrine\DBAL\Connection;

class DbalWriteEventRepository implements WriteEventRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function update(EventInput $authorInput, int $id): void
    {
        $sql = <<<SQL
        UPDATE event
        SET comment = :comment
        WHERE id = :id
SQL;

        $this->connection->executeQuery($sql, ['id' => $id, 'comment' => $authorInput->comment]);
    }

    /**
     * @param ImportEvent[] $importEvents
     *
     * @throws \Throwable
     */
    public function batchImport(array $importEvents): void
    {
        [$actors, $repos, $events] = $this->prepareData($importEvents);

        $this->connection->transactional(function () use ($actors, $repos, $events) {
            $actorsList = array_values($actors);
            $reposList = array_values($repos);

            $this->bulkInsert('actor', ['id', 'login', 'url', 'avatar_url'], $actorsList);
            $this->bulkInsert('repo', ['id', 'name', 'url'], $reposList);
            $this->bulkInsert('event', ['id', 'actor_id', 'repo_id', 'type', 'count', 'payload', 'create_at', 'comment'], $events);
        });
    }

    protected function bulkInsert(string $table, array $columns, array $values): void
    {
        if (empty($values)) {
            return;
        }

        $columnsSql = implode(', ', $columns);
        $placeholderForRow = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $placeholders = array_fill(0, count($values), $placeholderForRow);
        $valuesSql = implode(', ', $placeholders);

        $parameters = [];
        foreach ($values as $row) {
            foreach ($columns as $column) {
                $parameters[] = $row[$column];
            }
        }

        $sql = "INSERT INTO $table ($columnsSql) VALUES $valuesSql ON CONFLICT (id) DO NOTHING";
        $this->connection->executeQuery($sql, $parameters);
    }

    private function prepareData(array $importEvents): array
    {
        $actors = [];
        $repos = [];
        $events = [];

        foreach ($importEvents as $event) {
            $actorId = $event->actor->id;
            $repoId = $event->repo->id;

            $actors[$actorId] = [
                'id' => $actorId,
                'login' => $event->actor->login,
                'url' => $event->actor->url,
                'avatar_url' => $event->actor->avatarUrl,
            ];

            $repos[$repoId] = [
                'id' => $repoId,
                'name' => $event->repo->name,
                'url' => $event->repo->url,
            ];

            $jsonEncodedPayload = json_encode($event->payload);
            $formattedDate = $event->createdAt->format('Y-m-d H:i:s');
            $count = EventType::COMMIT === $event->type && isset($event->payload['size']) ? $event->payload['size'] : 1;
            $comment = EventType::COMMENT === $event->type ? ($event->payload['comment']['body'] ?? null) : null;

            $events[] = [
                'id' => $event->id,
                'actor_id' => $actorId,
                'repo_id' => $repoId,
                'type' => $event->type,
                'count' => $count,
                'payload' => $jsonEncodedPayload,
                'create_at' => $formattedDate,
                'comment' => $comment,
            ];
        }

        return [$actors, $repos, $events];
    }
}
