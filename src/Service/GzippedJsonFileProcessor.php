<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

class GzippedJsonFileProcessor
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function process(string $fileName, callable $lineProcessor): void
    {
        $gzFileHandler = gzopen($fileName, 'rb');
        if ($gzFileHandler === false) {
            $this->logger->error('Failed to open file', ['file' => $fileName]);
            return;
        }

        while (!gzeof($gzFileHandler)) {
            $line = gzgets($gzFileHandler);
            if ($line === false) {
                continue;
            }

            $lineProcessor($line);
        }

        gzclose($gzFileHandler);
    }
}
