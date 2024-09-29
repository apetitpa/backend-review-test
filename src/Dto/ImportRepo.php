<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\EventType;
use Symfony\Component\Validator\Constraints as Assert;

class ImportRepo
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $id;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Url]
    public string $url;
}
