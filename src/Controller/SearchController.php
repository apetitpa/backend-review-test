<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\SearchInputDto;
use App\Repository\ReadEventRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SearchController
{
    public function __construct(
        private readonly ReadEventRepositoryInterface $repository,
        private readonly DenormalizerInterface $denormalizer,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route(path: '/api/search', name: 'api_search', methods: ['GET'])]
    public function searchCommits(Request $request): JsonResponse
    {
        $searchInput = $this->denormalizer->denormalize($request->query->all(), SearchInputDto::class);

        $cacheKey = $this->getCacheKey($searchInput);

        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($searchInput) {
            $item->expiresAfter(86400);

            $countByType = $this->repository->countByType($searchInput);

            return [
                'meta' => [
                    'totalEvents' => $this->repository->countAll($searchInput),
                    'totalPullRequests' => $countByType['pullRequest'] ?? 0,
                    'totalCommits' => $countByType['commit'] ?? 0,
                    'totalComments' => $countByType['comment'] ?? 0,
                ],
                'data' => [
                    'events' => $this->repository->getLatest($searchInput),
                    'stats' => $this->repository->statsByTypePerHour($searchInput),
                ],
            ];
        });

        return new JsonResponse($data);
    }

    private function getCacheKey(SearchInputDto $searchInput): string
    {
        return 'search_'.md5(serialize([
            'date' => $searchInput->date,
            'keyword' => $searchInput->keyword,
        ]));
    }
}
