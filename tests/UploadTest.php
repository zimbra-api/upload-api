<?php

namespace Zimbra\Tests;

use Http\Mock\Client as MockClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;

use Zimbra\Upload\Attactment;
use Zimbra\Upload\Client;
use Zimbra\Upload\Request;

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

    public function testAttactment()
    {
        $attachmentId = $this->faker->uuid;
        $fileName = $this->faker->word;
        $contentType = $this->faker->mimeType;

        $attactment = new Attactment($attachmentId, $fileName, $contentType);
        $this->assertSame($attachmentId, $attactment->getAttachmentId());
        $this->assertSame($fileName, $attactment->getFileName());
        $this->assertSame($contentType, $attactment->getContentType());
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
        // $this->assertSame($location, $client->getLocation());
        // $this->assertSame($authToken, $client->getAuthToken());
        // $this->assertTrue($client->hasHeader('Method'));
        // $this->assertTrue($client->hasHeader('User-Agent'));
    }
}
