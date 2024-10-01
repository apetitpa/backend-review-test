<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileDownloader
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {}

    public function download(string $url, string $destination): bool
    {
        try {
            $response = $this->client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Failed to download file', [
                    'url' => $url,
                    'status_code' => $response->getStatusCode(),
                ]);
                return false;
            }

            $fileHandler = fopen($destination, 'w');
            foreach ($this->client->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
            }
            fclose($fileHandler);

            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport exception during file download', ['exception' => $e]);
            return false;
        }
    }
}
