<?php declare(strict_types=1);
/**
 * This file is part of the Zimbra Upload API in PHP library.
 *
 * © Nguyen Van Nguyen <nguyennv1981@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zimbra\Upload;

use PsrDiscovery\Discover;
use Psr\Http\Message\{
    StreamInterface,
    StreamFactoryInterface,
};

/**
 * Multipart stream class in Zimbra Upload API.
 * 
 * @package   Zimbra
 * @category  Upload
 * @author    Nguyen Van Nguyen - nguyennv1981@gmail.com
 * @copyright Copyright © 2024-present by Nguyen Van Nguyen.
 */
class MultipartStreamBuilder
{
    const CRLF   = "\r\n";
    const DASHES = '--';

    private StreamFactoryInterface $streamFactory;

    private string $boundary;

    private array $data = [];

    /**
     * Constructor
     *
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        ?StreamFactoryInterface $streamFactory = null
    )
    {
        $this->streamFactory = $streamFactory ?? Discover::httpStreamFactory();
        $this->boundary = bin2hex(random_bytes(20));
    }

    /**
     * Build the stream.
     *
     * @return StreamInterface
     */
    public function build(): StreamInterface
    {
        $buffer = fopen('php://temp', 'r+');
        foreach ($this->data as $data) {
            fwrite(
                $buffer,
                implode([
                    self::DASHES,
                    $this->boundary,
                    self::CRLF,
                    self::getHeaders($data['headers']),
                    self::CRLF,
                ])
            );

            $contentStream = $data['content'];

            if ($contentStream->isSeekable()) {
                $contentStream->rewind();
            }
            if ($contentStream->isReadable()) {
                while (!$contentStream->eof()) {
                    fwrite($buffer, $contentStream->read(1048576));
                }
            }
            else {
                fwrite($buffer, $contentStream->__toString());
            }
            fwrite($buffer, self::CRLF);
        }
        fwrite($buffer, implode([
            self::DASHES,
            $this->boundary,
            self::DASHES,
            self::CRLF,
        ]));
        fseek($buffer, 0);
        return $this->createStream($buffer);
    }

    /**
     * Add a resource to the Multipart Stream.
     *
     * @param string $name
     * @param string|resource|StreamInterface $resource
     * @param array $options
     * @return self
     */
    public function addResource(
        string $name, $resource, array $options = []
    ): self
    {
        $stream = $this->createStream($resource);

        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }

        if (empty($options['filename'])) {
            $options['filename'] = null;
            $uri = $stream->getMetadata('uri');
            if ('php://' !== substr($uri, 0, 6)) {
                $options['filename'] = $uri;
            }
        }

        $headers = self::prepareHeaders(
            $name,
            $stream,
            $options['filename'] ?? '',
            $options['headers']
        );

        return $this->addData($stream, $headers);
    }

    /**
     * Add a resource to the Multipart Stream.
     *
     * @param string|resource|StreamInterface $resource 
     * @param array $headers
     * @return self
     */
    public function addData($resource, array $headers = []): self
    {
        $this->data[] = [
            'content' => $this->createStream($resource),
            'headers' => $headers,
        ];

        return $this;
    }

    /**
     * Get the boundary that separates the streams.
     *
     * @return string
     */
    public function getBoundary(): string
    {
        return $this->boundary;
    }

    private static function getHeaders(array $headers): string
    {
        $str = '';
        foreach ($headers as $key => $value) {
            $str .= sprintf("%s: %s\r\n", $key, $value);
        }

        return $str;
    }

    private static function prepareHeaders(
        string $name,
        StreamInterface $stream,
        string $filename,
        array $headers
    ): array
    {
        $hasFilename = '0' === $filename || $filename;

        if (!self::hasHeader($headers, 'content-disposition')) {
            $headers['Content-Disposition'] = sprintf(
                'form-data; name="%s"', $name
            );
            if ($hasFilename) {
                $headers['Content-Disposition'] .= sprintf(
                    '; filename="%s"', self::basename($filename)
                );
            }
        }

        if (!self::hasHeader($headers, 'content-length')) {
            if ($length = $stream->getSize()) {
                $headers['Content-Length'] = (string) $length;
            }
        }

        if (!self::hasHeader($headers, 'content-type') && $hasFilename) {
            if ($type = MimeTypes::getMimetype($filename)) {
                $headers['Content-Type'] = $type;
            }
        }

        return $headers;
    }

    private static function basename(string $path): string
    {
        $separators = '/';
        if (DIRECTORY_SEPARATOR != '/') {
            $separators .= DIRECTORY_SEPARATOR;
        }

        $path = rtrim($path, $separators);

        $filename = preg_match(
            '@[^' . preg_quote($separators, '@') . ']+$@', $path, $matches
        ) ? $matches[0] : '';

        return $filename;
    }

    private static function hasHeader(array $headers, string $key): bool
    {
        $header = strtolower($key);
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $header) {
                return true;
            }
        }

        return false;
    }

    private function createStream($resource): StreamInterface
    {
        if ($resource instanceof StreamInterface) {
            return $resource;
        }
        if (\is_string($resource)) {
            return $this->streamFactory->createStream($resource);
        }
        if (\is_resource($resource)) {
            return $this->streamFactory->createStreamFromResource($resource);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'First argument to "%s::createStream()" must be a string, resource or StreamInterface.',
                __CLASS__
            )
        );
    }
}
