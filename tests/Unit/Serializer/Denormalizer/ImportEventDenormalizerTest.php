<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer\Denormalizer;

use App\Dto\ImportActor;
use App\Dto\ImportEvent;
use App\Dto\ImportRepo;
use App\Entity\EventType;
use App\Serializer\Denormalizer\ImportActorDenormalizer;
use App\Serializer\Denormalizer\ImportRepoDenormalizer;
use App\Serializer\Denormalizer\ImportEventDenormalizer;
use PHPUnit\Framework\TestCase;

class ImportEventDenormalizerTest extends TestCase
{
    private readonly ImportActorDenormalizer $importActorDenormalizer;
    private readonly ImportRepoDenormalizer $importRepoDenormalizer;
    private ImportEventDenormalizer $importEventDenormalizer;

    protected function setUp(): void
    {
        $this->importActorDenormalizer = $this->createMock(ImportActorDenormalizer::class);
        $this->importRepoDenormalizer = $this->createMock(ImportRepoDenormalizer::class);

        $this->importEventDenormalizer = new ImportEventDenormalizer(
            $this->importActorDenormalizer,
            $this->importRepoDenormalizer
        );
    }

    public function testDenormalize(): void
    {
        $data = [
            'id' => '123',
            'type' => 'PushEvent',
            'created_at' => '2024-09-29T12:34:56+00:00',
            'payload' => ['key' => 'value'],
            'actor' => ['name' => 'test_actor'],
            'repo' => ['name' => 'test_repo'],
        ];

        $importActor = new ImportActor();
        $importRepo = new ImportRepo();

        $this->importActorDenormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with($data['actor'], ImportActor::class)
            ->willReturn($importActor);

        $this->importRepoDenormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with($data['repo'], ImportRepo::class)
            ->willReturn($importRepo);

        /** @var ImportEvent $result */
        $result = $this->importEventDenormalizer->denormalize($data, ImportEvent::class, 'json');

        $this->assertInstanceOf(ImportEvent::class, $result);
        $this->assertSame(123, $result->id);
        $this->assertSame(EventType::COMMIT, $result->type);
        $this->assertEquals(new \DateTimeImmutable('2024-09-29T12:34:56+00:00'), $result->createdAt);
        $this->assertSame(['key' => 'value'], $result->payload);
        $this->assertSame($importActor, $result->actor);
        $this->assertSame($importRepo, $result->repo);
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->importEventDenormalizer->supportsDenormalization([], ImportEvent::class, 'json'));
        $this->assertFalse($this->importEventDenormalizer->supportsDenormalization([], ImportEvent::class, 'xml'));
        $this->assertFalse($this->importEventDenormalizer->supportsDenormalization([], \stdClass::class, 'json'));
    }
}
