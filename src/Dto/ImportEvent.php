<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\EventType;
use Symfony\Component\Validator\Constraints as Assert;

class ImportEvent
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $id;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [EventType::class, 'getChoices'])]
    public string $type;

    #[Assert\NotBlank]
    #[Assert\LessThan('today UTC')]
    public \DateTimeImmutable $createdAt;

    #[Assert\NotNull]
    #[Assert\Valid]
    public ImportRepo $repo;

    #[Assert\NotNull]
    #[Assert\Valid]
    public ImportActor $actor;

    public array $payload;
}
