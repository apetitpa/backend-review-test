<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchInputDto;
use Doctrine\DBAL\Connection;

class DbalReadEventRepository implements ReadEventRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function countAll(SearchInputDto $searchInput): int
    {
        $sql = <<<SQL
            SELECT SUM(count) AS count
            FROM event
            WHERE DATE(create_at) = :date
            AND payload::text LIKE :keyword
        SQL;

        /** @var string $result */
        $result = $this->connection->fetchOne($sql, [
            'date' => $searchInput->date,
            'keyword' => "%{$searchInput->keyword}%",
        ]);

        return (int) $result;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function countByType(SearchInputDto $searchInput): array
    {
        $sql = <<<SQL
            SELECT type, sum(count) as count
            FROM event
            WHERE DATE(create_at) = :date
            AND payload::text LIKE :keyword
            GROUP BY type
        SQL;

        return $this->connection->fetchAllKeyValue($sql, [
            'date' => $searchInput->date,
            'keyword' => "%{$searchInput->keyword}%",
        ]);
    }

    /**
     * @return array<int, array<int|string, int>>
     */
    public function statsByTypePerHour(SearchInputDto $searchInput): array
    {
        $sql = <<<SQL
            SELECT extract(hour from create_at) as hour, type, sum(count) as count
            FROM event
            WHERE date(create_at) = :date
            AND payload::text LIKE :keyword
            GROUP BY TYPE, EXTRACT(hour from create_at)
        SQL;

        /** @var array<int, array<string, int>> $stats */
        $stats = $this->connection->fetchAllKeyValue($sql, [
            'date' => $searchInput->date,
            'keyword' => "%{$searchInput->keyword}%",
        ]);

        $data = array_fill(0, 24, ['commit' => 0, 'pullRequest' => 0, 'comment' => 0]);

        foreach ($stats as $stat) {
            $data[(int) $stat['hour']][$stat['type']] = $stat['count'];
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatest(SearchInputDto $searchInput): array
    {
        $sql = <<<SQL
            SELECT type, repo_id
            FROM event
            WHERE date(create_at) = :date
            AND payload::text LIKE :keyword
        SQL;

        /** @var array<int, array<string, string>> $result */
        $result = $this->connection->fetchAllAssociative($sql, [
            'date' => $searchInput->date,
            'keyword' => $searchInput->keyword,
        ]);

        return array_map(static function (array $item): array {
            $item['repo'] = json_decode($item['repo'], true);

            return $item;
        }, $result);
    }

    public function exist(int $id): bool
    {
        $sql = <<<SQL
            SELECT 1
            FROM event
            WHERE id = :id
        SQL;

        $result = $this->connection->fetchOne($sql, [
            'id' => $id,
        ]);

        return (bool) $result;
    }
}
