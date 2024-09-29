<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\EventTypeEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: '`event`')]
#[ORM\Index(name: 'IDX_EVENT_TYPE', columns: ['type'])]
#[ORM\Entity]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $id;

    #[ORM\Column(type: 'string', enumType: 'App\Entity\Enum\EventTypeEnum', nullable: false)]
    private EventTypeEnum $type;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 1])]
    private int $count = 1;

    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Actor::class, cascade: ['persist'])]
    private Actor $actor;

    #[ORM\JoinColumn(name: 'repo_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Repo::class, cascade: ['persist'])]
    private Repo $repo;

    #[ORM\Column(type: 'json', nullable: false, options: ['jsonb' => true])]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment;

    public function __construct(int $id, EventTypeEnum $type, Actor $actor, Repo $repo, array $payload, \DateTimeImmutable $createAt, ?string $comment)
    {
        $this->id = $id;
        $this->type = $type;
        $this->actor = $actor;
        $this->repo = $repo;
        $this->payload = $payload;
        $this->createAt = $createAt;
        $this->comment = $comment;

        if ($type === EventTypeEnum::COMMIT) {
            $this->count = $payload['size'] ?? 1;
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function actor(): Actor
    {
        return $this->actor;
    }

    public function repo(): Repo
    {
        return $this->repo;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function createAt(): \DateTimeImmutable
    {
        return $this->createAt;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
