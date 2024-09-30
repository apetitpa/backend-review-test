<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\EventInputDto;
use App\Repository\ReadEventRepositoryInterface;
use App\Repository\WriteEventRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventController
{
    public function __construct(
        private readonly WriteEventRepositoryInterface $writeEventRepository,
        private readonly ReadEventRepositoryInterface $readEventRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route(path: '/api/event/{id}/update', name: 'api_commit_update', methods: ['PUT'])]
    public function update(Request $request, int $id): Response
    {
        $eventInput = $this->serializer->deserialize($request->getContent(), EventInputDto::class, 'json');

        $errors = $this->validator->validate($eventInput);

        if (\count($errors) > 0) {
            return new JsonResponse(
                ['message' => $errors->get(0)->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (false === $this->readEventRepository->exist($id)) {
            return new JsonResponse(
                ['message' => \sprintf('Event identified by %d not found !', $id)],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $this->writeEventRepository->update($eventInput, $id);
        } catch (\Exception $exception) {
            return new Response(null, Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
