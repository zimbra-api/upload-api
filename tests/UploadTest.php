<?php

namespace Zimbra\Tests;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\{
    DiscoveryStrategy,
    MockClientStrategy
};
use Psr\Log\LoggerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
    UriInterface
};
use Zimbra\Upload\{Attachment, Client, Request};

/**
 * Testcase class for upload client.
 */
class UploadTest extends ZimbraTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        HttpClientDiscovery::prependStrategy(MockClientStrategy::class);
    }

    public function testAttachment()
    {
        $attachmentId = $this->faker->uuid;
        $fileName = $this->faker->word;
        $contentType = $this->faker->mimeType;
        $size = $this->faker->randomNumber;

        $attachment = new Attachment($attachmentId, $fileName, $contentType, $size);
        $this->assertSame($attachmentId, $attachment->getAttachmentId());
        $this->assertSame($fileName, $attachment->getFileName());
        $this->assertSame($contentType, $attachment->getContentType());
        $this->assertSame($size, $attachment->getSize());
    }

    public function testRequest()
    {
        $requestId = $this->faker->uuid;
        $file1 = new \SplFileInfo(tempnam(sys_get_temp_dir(), $requestId));
        $file2 = new \SplFileInfo(tempnam(sys_get_temp_dir(), $requestId));

        $request = new Request([$file1], $requestId);
        $this->assertSame($requestId, $request->getRequestId());
        $this->assertSame([$file1], $request->getFiles());

        $request = new Request();
        $request->setRequestId($requestId)
            ->setFiles([$file1])
            ->addFile($file2);
        $this->assertSame($requestId, $request->getRequestId());
        $this->assertSame([$file1, $file2], $request->getFiles());
    }

    public function testClient()
    {
        $uploadUrl = $this->faker->url;
        $authToken = $this->faker->sha256;
        $requestId = $this->faker->uuid;

        $file1 = new \SplFileInfo(tempnam(sys_get_temp_dir(), $requestId));
        $file2 = new \SplFileInfo(tempnam(sys_get_temp_dir(), $requestId));
        $request = new Request([$file1, $file2], $requestId);

        $client = new Client($uploadUrl, $authToken);
        $client->upload($request);

        $httpRequest = $client->getHttpRequest();
        $this->assertInstanceOf(RequestInterface::class, $httpRequest);
        $this->assertSame(Client::REQUEST_METHOD, $httpRequest->getMethod());
        $this->assertStringStartsWith('ZM_AUTH_TOKEN', $httpRequest->getHeaderLine('Cookie'));
        $this->assertStringEndsWith($authToken, $httpRequest->getHeaderLine('Cookie'));
        $this->assertStringStartsWith('multipart/form-data', $httpRequest->getHeaderLine('Content-Type'));

        $uri = $httpRequest->getUri();
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame(http_build_query(['fmt' => Client::QUERY_FORMAT]), $uri->getQuery());
        $this->assertStringStartsWith($uploadUrl, $uri->__toString());

        $this->assertInstanceOf(LoggerInterface::class, $client->getLogger());
        $this->assertInstanceOf(ClientInterface::class, $client->getHttpClient());
        $this->assertInstanceOf(ResponseInterface::class, $client->getHttpResponse());
    }
}
