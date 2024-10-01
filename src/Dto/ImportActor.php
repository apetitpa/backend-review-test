<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\EventType;
use Symfony\Component\Validator\Constraints as Assert;

class ImportActor
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $id;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $login;

    #[Assert\NotBlank]
    #[Assert\Url]
    public string $url;

    #[Assert\NotBlank]
    #[Assert\Url]
    public string $avatarUrl;
}
