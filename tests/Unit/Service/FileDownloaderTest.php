<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\FileDownloader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class FileDownloaderTest extends TestCase
{
    private readonly FileDownloader $fileDownloader;
    private readonly HttpClientInterface $httpClientMock;
    private readonly LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->fileDownloader = new FileDownloader(
            $this->httpClientMock,
            $this->loggerMock
        );
    }

    public function testDownloadSuccessful(): void
    {
        $url = 'https://example.com/file.gz';
        $destination = sys_get_temp_dir() . '/file.gz';
        $content = 'test content';

        $responseMock = new MockResponse($content, ['http_code' => 200]);
        $responseStreamMock = $this->createMock(ResponseStreamInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($responseMock);

        $this->httpClientMock
            ->expects($this->once())
            ->method('stream')
            ->with($responseMock)
            ->willReturn($responseStreamMock);

        $result = $this->fileDownloader->download($url, $destination);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        unlink($destination);
    }

    public function testDownloadFailsOnNon200StatusCode(): void
    {
        $url = 'https://example.com/file.gz';
        $destination = sys_get_temp_dir() . '/file.gz';

        $responseMock = new MockResponse('', ['http_code' => 404]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to download file', [
                'url' => $url,
                'status_code' => 404,
            ]);

        $result = $this->fileDownloader->download($url, $destination);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($destination);
    }

    public function testDownloadHandlesTransportException(): void
    {
        $url = 'https://example.com/file.gz';
        $destination = sys_get_temp_dir() . '/file.gz';

        $exception = new TransportException('Connection error');

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Transport exception during file download', ['exception' => $exception]);

        $result = $this->fileDownloader->download($url, $destination);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($destination);
    }
}
