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
    StreamInterface,
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

    protected function mockHttpResponse(string $contents): ResponseInterface
    {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')->willReturn($contents);
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);
        return $response;
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
        $authToken = $this->faker->sha256;
        $file = new \SplFileInfo(tempnam(sys_get_temp_dir(), $requestId));

        $request = new Request([$file], $requestId, $authToken);
        $this->assertSame($requestId, $request->getRequestId());
        $this->assertSame("ZM_AUTH_TOKEN=$authToken", $request->getAuthTokenCookie());
        $this->assertSame([$file], $request->getFiles());

        $request = new Request([$file], $requestId, $authToken, TRUE);
        $this->assertSame("ZM_ADMIN_AUTH_TOKEN=$authToken", $request->getAuthTokenCookie());
    }

    public function testClient()
    {
        $uploadUrl = $this->faker->url;
        $authToken = $this->faker->sha256;
        $requestId = $this->faker->uuid;

        $attachmentId = $this->faker->uuid;
        $fileName = $this->faker->word;
        $contentType = $this->faker->mimeType;
        $size = $this->faker->randomNumber;
        $attachment = new Attachment($attachmentId, $fileName, $contentType, $size);
        $responseContent = strtr('200,"{requestId}",[{"aid":"{attachmentId}","ct":"{contentType}","filename":"{fileName}","s":{size}}]', [
            '{requestId}' => $requestId,
            '{attachmentId}' => $attachmentId,
            '{contentType}' => $contentType,
            '{fileName}' => $fileName,
            '{size}' => $size,
        ]);
        $response = $this->mockHttpResponse($responseContent);

        $file1 = new \SplFileInfo(tempnam(sys_get_temp_dir(), $requestId));
        $file2 = new \SplFileInfo(tempnam(sys_get_temp_dir(), $requestId));
        $request = new Request([$file1, $file2], $requestId, $authToken);

        $client = new Client($uploadUrl);
        $httpClient = $client->getHttpClient();
        $httpClient->setDefaultResponse($response);
        $this->assertInstanceOf(ClientInterface::class, $httpClient);

        $attachments = $client->upload($request);
        $this->assertEquals([$attachment], $attachments);
        $this->assertSame($response, $client->getHttpResponse());

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
    }
}
