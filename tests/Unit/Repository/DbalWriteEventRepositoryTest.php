<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Dto\ImportActor;
use App\Dto\ImportEvent;
use App\Dto\ImportRepo;
use App\Entity\EventType;
use App\Repository\DbalWriteEventRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DbalWriteEventRepositoryTest extends TestCase
{
    private DbalWriteEventRepository $repository;
    private Connection $connectionMock;

    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(Connection::class);

        $this->repository = $this->getMockBuilder(DbalWriteEventRepository::class)
            ->setConstructorArgs([$this->connectionMock])
            ->onlyMethods(['bulkInsert'])
            ->getMock();
    }

    public function testBatchImport(): void
    {
        $actorMock = $this->createMock(ImportActor::class);
        $actorMock->id = 1;
        $actorMock->login = 'john_doe';
        $actorMock->url = 'https://example.com/john_doe';
        $actorMock->avatarUrl = 'https://example.com/avatar.jpg';

        $repoMock = $this->createMock(ImportRepo::class);
        $repoMock->id = 2;
        $repoMock->name = 'example_repo';
        $repoMock->url = 'https://example.com/example_repo';

        $eventMock = $this->createMock(ImportEvent::class);
        $eventMock->id = 100;
        $eventMock->actor = $actorMock;
        $eventMock->repo = $repoMock;
        $eventMock->type = EventType::COMMIT;
        $eventMock->payload = ['size' => 3, 'comment' => ['body' => 'Great work!']];
        $eventMock->createdAt = new \DateTimeImmutable('2022-01-01 12:00:00');

        $importEvents = [$eventMock];

        $this->connectionMock
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) {
                $callback();
            }));

        $expectedActors = [
            [
                'id' => 1,
                'login' => 'john_doe',
                'url' => 'https://example.com/john_doe',
                'avatar_url' => 'https://example.com/avatar.jpg',
            ],
        ];

        $expectedRepos = [
            [
                'id' => 2,
                'name' => 'example_repo',
                'url' => 'https://example.com/example_repo',
            ],
        ];

        $expectedEvents = [
            [
                'id' => 100,
                'actor_id' => 1,
                'repo_id' => 2,
                'type' => EventType::COMMIT,
                'count' => 3,
                'payload' => json_encode(['size' => 3, 'comment' => ['body' => 'Great work!']]),
                'create_at' => '2022-01-01 12:00:00',
                'comment' => null,
            ],
        ];

        $this->repository->expects($this->exactly(3))
            ->method('bulkInsert')
            ->withConsecutive(
                ['actor', ['id', 'login', 'url', 'avatar_url'], $expectedActors],
                ['repo', ['id', 'name', 'url'], $expectedRepos],
                ['event', ['id', 'actor_id', 'repo_id', 'type', 'count', 'payload', 'create_at', 'comment'], $expectedEvents]
            );

        $this->repository->batchImport($importEvents);
    }
}
