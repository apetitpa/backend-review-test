<?php

declare(strict_types=1);

namespace App\Serializer\Denormalizer;

use App\Dto\ImportActor;
use App\Dto\ImportEvent;
use App\Dto\ImportRepo;
use App\Entity\EventType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ImportEventDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private readonly ImportActorDenormalizer $importActorDenormalizer,
        private readonly ImportRepoDenormalizer $importRepoDenormalizer,
    )
    {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ImportEvent
    {
        $importEvent = new ImportEvent();

        $importEvent->id = (int) $data['id'];
        $importEvent->type = $this->mapTypeToEventTpe($data['type']);
        $importEvent->createdAt = new \DateTimeImmutable($data['created_at']);
        $importEvent->payload = $data['payload'];
        $importEvent->actor = $this->importActorDenormalizer->denormalize($data['actor'], ImportActor::class);
        $importEvent->repo = $this->importRepoDenormalizer->denormalize($data['repo'], ImportRepo::class);

        return $importEvent;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return ImportEvent::class === $type
            && 'json' === $format;
    }

    private function mapTypeToEventTpe(string $type): string
    {
        return match ($type) {
            'CommitCommentEvent', 'PullRequestReviewCommentEvent', 'IssueCommentEvent' => EventType::COMMENT,
            'PushEvent' => EventType::COMMIT,
            'PullRequestEvent' => EventType::PULL_REQUEST,
            default => '',
        };
    }
}
