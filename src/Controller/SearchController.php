<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\SearchInput;
use App\Repository\ReadEventRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SearchController
{
    public function __construct(
        private readonly ReadEventRepositoryInterface $repository,
        private readonly DenormalizerInterface $denormalizer,
    ) {
    }

    #[Route(path: '/api/search', name: 'api_search', methods: ['GET'])]
    public function searchCommits(Request $request): JsonResponse
    {
        $searchInput = $this->denormalizer->denormalize($request->query->all(), SearchInput::class);

        $countByType = $this->repository->countByType($searchInput);

        $data = [
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

        return new JsonResponse($data);
    }
}
