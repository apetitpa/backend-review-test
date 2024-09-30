<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'actor')]
#[ORM\Entity]
class Actor
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $login;

    #[ORM\Column(type: 'string')]
    public string $url;

    #[ORM\Column(type: 'string')]
    public string $avatarUrl;

    public function __construct(int $id, string $login, string $url, string $avatarUrl)
    {
        $this->id = $id;
        $this->login = $login;
        $this->url = $url;
        $this->avatarUrl = $avatarUrl;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            $data['login'],
            $data['url'],
            $data['avatar_url']
        );
    }
}
