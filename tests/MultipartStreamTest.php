<?php

namespace Zimbra\Tests;

use Zimbra\Upload\MultipartStreamBuilder;

/**
 * Testcase class for Multipart Stream.
 */
class MultipartStreamTest extends ZimbraTestCase
{
    public function testSupportStreams()
    {
        $fooBody = $this->faker->sentence;
        $barBody = $this->faker->sentence;

        $builder = new MultipartStreamBuilder();
        $builder->addResource('foo', $fooBody, ['filename' => 'foo.txt'])
                ->addResource('bar', $barBody, ['filename' => 'bar.txt']);
        $multipartStream = (string) $builder->build();

        $this->assertTrue(false !== strpos($multipartStream, 'name="foo"'));
        $this->assertTrue(false !== strpos($multipartStream, 'name="bar"'));

        $this->assertTrue(false !== strpos($multipartStream, 'filename="foo.txt"'));
        $this->assertTrue(false !== strpos($multipartStream, 'filename="bar.txt"'));

        $this->assertTrue(false !== strpos($multipartStream, $fooBody));
        $this->assertTrue(false !== strpos($multipartStream, $barBody));
    }

    public function testSupportResources()
    {
        $resource = fopen($this->faker->image(null, 640, 480), 'r');

        $builder = new MultipartStreamBuilder();
        $builder->addResource('image', $resource);
        $multipartStream = (string) $builder->build();

        $this->assertTrue(false !== strpos($multipartStream, 'Content-Disposition: form-data; name="image"'));
        $this->assertTrue(false !== strpos($multipartStream, 'Content-Type: image/png'));
    }

    public function testSupportURIResources()
    {
        $url = $this->faker->imageUrl(640, 480);
        $resource = fopen($url, 'r');

        $builder = new MultipartStreamBuilder();
        $builder->addResource('image', $resource, ['filename' => 'image.png']);
        $multipartStream = (string) $builder->build();

        $this->assertTrue(false !== strpos($multipartStream, 'Content-Disposition: form-data; name="image"'));
        $this->assertTrue(false !== strpos($multipartStream, 'Content-Type: image/png'));

        $urlContents = file_get_contents($url);
        $this->assertStringContainsString($urlContents, $multipartStream);
    }

    public function testBoundary()
    {
        $builder = new MultipartStreamBuilder();
        $boundary = $builder->getBoundary();

        $builder->addResource('content1', $this->faker->sentence);
        $builder->addResource('content2', $this->faker->sentence);
        $builder->addResource('content3', $this->faker->sentence);

        $multipartStream = (string) $builder->build();
        $this->assertEquals(4, substr_count($multipartStream, $boundary));
    }
}
