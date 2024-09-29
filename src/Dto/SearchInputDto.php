<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SearchInputDto
{
    #[Assert\NotBlank]
    #[Assert\Date]
    #[Assert\LessThanOrEqual('today')]
    public string $date;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $keyword;
}
