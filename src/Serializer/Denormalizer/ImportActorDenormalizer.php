<?php

declare(strict_types=1);

namespace App\Serializer\Denormalizer;

use App\Dto\ImportActor;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ImportActorDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ImportActor
    {
        $importActor = new ImportActor();

        $importActor->id = $data['id'];
        $importActor->login = $data['login'];
        $importActor->url = $data['url'];
        $importActor->avatarUrl = $data['avatar_url'];

        return $importActor;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return ImportActor::class === $type;
    }
}
