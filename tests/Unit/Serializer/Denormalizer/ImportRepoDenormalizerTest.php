<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer\Denormalizer;

use App\Dto\ImportRepo;
use App\Serializer\Denormalizer\ImportRepoDenormalizer;
use PHPUnit\Framework\TestCase;

class ImportRepoDenormalizerTest extends TestCase
{
    private readonly ImportRepoDenormalizer $importRepoDenormalizer;

    protected function setUp(): void
    {
        $this->importRepoDenormalizer = new ImportRepoDenormalizer();
    }

    public function testDenormalize(): void
    {
        $data = [
            'id' => 123,
            'name' => 'test_repo',
            'url' => 'https://example.com/repo',
        ];

        /** @var ImportRepo $result */
        $result = $this->importRepoDenormalizer->denormalize($data, ImportRepo::class, 'json');

        $this->assertInstanceOf(ImportRepo::class, $result);
        $this->assertSame(123, $result->id);
        $this->assertSame('test_repo', $result->name);
        $this->assertSame('https://example.com/repo', $result->url);
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->importRepoDenormalizer->supportsDenormalization([], ImportRepo::class, 'json'));
        $this->assertFalse($this->importRepoDenormalizer->supportsDenormalization([], ImportRepo::class, 'xml'));
        $this->assertFalse($this->importRepoDenormalizer->supportsDenormalization([], \stdClass::class, 'json'));
    }
}
