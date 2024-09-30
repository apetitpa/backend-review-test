<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class EventInputDto
{
    #[Assert\Length(min: 20)]
    public ?string $comment;

    public function __construct(?string $comment)
    {
        $this->comment = $comment;
    }
}
