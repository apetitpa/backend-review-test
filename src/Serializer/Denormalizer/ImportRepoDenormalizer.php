<?php

declare(strict_types=1);

namespace App\Serializer\Denormalizer;

use App\Dto\ImportRepo;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ImportRepoDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ImportRepo
    {
        $importRepo = new ImportRepo();

        $importRepo->id = $data['id'];
        $importRepo->name = $data['name'];
        $importRepo->url = $data['url'];

        return $importRepo;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return ImportRepo::class === $type
            && 'json' === $format;
    }
}
