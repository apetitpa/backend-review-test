<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\GzippedJsonFileProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GzippedJsonFileProcessorTest extends TestCase
{
    private readonly GzippedJsonFileProcessor $fileProcessor;
    private readonly LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->fileProcessor = new GzippedJsonFileProcessor(
            $this->loggerMock
        );
    }

    public function testProcessFileSuccessfully(): void
    {
        $fileName = sys_get_temp_dir() . '/test.gz';

        $lines = [
            '{"event": "event1"}',
            '{"event": "event2"}',
            '{"event": "event3"}',
        ];

        $gz = gzopen($fileName, 'wb9');
        foreach ($lines as $line) {
            gzwrite($gz, $line . "\n");
        }
        gzclose($gz);

        $processedLines = [];

        $this->fileProcessor->process($fileName, function ($line) use (&$processedLines) {
            $processedLines[] = trim($line);
        });

        $this->assertEquals($lines, $processedLines);

        unlink($fileName);
    }
}
