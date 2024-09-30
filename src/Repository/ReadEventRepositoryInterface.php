<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchInputDto;

interface ReadEventRepositoryInterface
{
    public function countAll(SearchInputDto $searchInput): int;

    /**
     * @return array<string, int>
     */
    public function countByType(SearchInputDto $searchInput): array;

    /**
     * @return array<int, array<string, int>>
     */
    public function statsByTypePerHour(SearchInputDto $searchInput): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatest(SearchInputDto $searchInput): array;

    public function exist(int $id): bool;
}
