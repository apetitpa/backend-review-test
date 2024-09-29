<?php

declare(strict_types=1);

namespace App\Message;

class ImportGitHubEventsMessage
{
    private string $date;
    private int $hour;

    public function __construct(string $date, int $hour)
    {
        $this->date = $date;
        $this->hour = $hour;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getHour(): int
    {
        return $this->hour;
    }
}
