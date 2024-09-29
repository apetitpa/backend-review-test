<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum EventTypeEnum: string
{
    case COMMIT = 'COM';
    case COMMENT = 'MSG';
    case PULL_REQUEST = 'PR';
}
