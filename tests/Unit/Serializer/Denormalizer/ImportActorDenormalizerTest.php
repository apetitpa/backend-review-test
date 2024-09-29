<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer\Denormalizer;

use App\Dto\ImportActor;
use App\Serializer\Denormalizer\ImportActorDenormalizer;
use PHPUnit\Framework\TestCase;

class ImportActorDenormalizerTest extends TestCase
{
    private readonly ImportActorDenormalizer $importActorDenormalizer;

    protected function setUp(): void
    {
        $this->importActorDenormalizer = new ImportActorDenormalizer();
    }

    public function testDenormalize(): void
    {
        $data = [
            'id' => 456,
            'login' => 'test_login',
            'url' => 'https://example.com/actor',
            'avatar_url' => 'https://example.com/avatar.png',
        ];

        /** @var ImportActor $result */
        $result = $this->importActorDenormalizer->denormalize($data, ImportActor::class, 'json');

        $this->assertInstanceOf(ImportActor::class, $result);
        $this->assertSame(456, $result->id);
        $this->assertSame('test_login', $result->login);
        $this->assertSame('https://example.com/actor', $result->url);
        $this->assertSame('https://example.com/avatar.png', $result->avatarUrl);
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->importActorDenormalizer->supportsDenormalization([], ImportActor::class, 'json'));
        $this->assertFalse($this->importActorDenormalizer->supportsDenormalization([], ImportActor::class, 'xml'));
        $this->assertFalse($this->importActorDenormalizer->supportsDenormalization([], \stdClass::class, 'json'));
    }
}
