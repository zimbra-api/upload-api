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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

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

    private static $mimetypes = [
        '7z' => 'application/x-7z-compressed',
        'aac' => 'audio/x-aac',
        'ai' => 'application/postscript',
        'aif' => 'audio/x-aiff',
        'asc' => 'text/plain',
        'asf' => 'video/x-ms-asf',
        'atom' => 'application/atom+xml',
        'avi' => 'video/x-msvideo',
        'bmp' => 'image/bmp',
        'bz2' => 'application/x-bzip2',
        'cer' => 'application/pkix-cert',
        'crl' => 'application/pkix-crl',
        'crt' => 'application/x-x509-ca-cert',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'cu' => 'application/cu-seeme',
        'deb' => 'application/x-debian-package',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dvi' => 'application/x-dvi',
        'eot' => 'application/vnd.ms-fontobject',
        'eps' => 'application/postscript',
        'epub' => 'application/epub+zip',
        'etx' => 'text/x-setext',
        'flac' => 'audio/flac',
        'flv' => 'video/x-flv',
        'gif' => 'image/gif',
        'gz' => 'application/gzip',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
        'ics' => 'text/calendar',
        'ini' => 'text/plain',
        'iso' => 'application/x-iso9660-image',
        'jar' => 'application/java-archive',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'latex' => 'application/x-latex',
        'log' => 'text/plain',
        'm4a' => 'audio/mp4',
        'm4v' => 'video/mp4',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mp4a' => 'audio/mp4',
        'mp4v' => 'video/mp4',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpg4' => 'video/mp4',
        'oga' => 'audio/ogg',
        'ogg' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'pbm' => 'image/x-portable-bitmap',
        'pdf' => 'application/pdf',
        'pgm' => 'image/x-portable-graymap',
        'png' => 'image/png',
        'pnm' => 'image/x-portable-anymap',
        'ppm' => 'image/x-portable-pixmap',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ps' => 'application/postscript',
        'qt' => 'video/quicktime',
        'rar' => 'application/x-rar-compressed',
        'ras' => 'image/x-cmu-raster',
        'rss' => 'application/rss+xml',
        'rtf' => 'application/rtf',
        'sgm' => 'text/sgml',
        'sgml' => 'text/sgml',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'torrent' => 'application/x-bittorrent',
        'ttf' => 'application/x-font-ttf',
        'txt' => 'text/plain',
        'wav' => 'audio/x-wav',
        'webm' => 'video/webm',
        'wma' => 'audio/x-ms-wma',
        'wmv' => 'video/x-ms-wmv',
        'woff' => 'application/x-font-woff',
        'wsdl' => 'application/wsdl+xml',
        'xbm' => 'image/x-xbitmap',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'yaml' => 'text/yaml',
        'yml' => 'text/yaml',
        'zip' => 'application/zip',

        // Non-Apache standard
        'pkpass' => 'application/vnd.apple.pkpass',
        'msg' => 'application/vnd.ms-outlook',
    ];

    private readonly StreamFactoryInterface $streamFactory;

    private readonly string $boundary;

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
                    $this->getBoundary(),
                    self::CRLF,
                    $this->getHeaders($data['headers']),
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
            $this->getBoundary(),
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

        $headers = $this->prepareHeaders(
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

    private function getHeaders(array $headers): string
    {
        $str = '';
        foreach ($headers as $key => $value) {
            $str .= sprintf("%s: %s\r\n", $key, $value);
        }

        return $str;
    }

    private function prepareHeaders(
        string $name,
        StreamInterface $stream,
        string $filename,
        array $headers
    ): array
    {
        $hasFilename = '0' === $filename || $filename;

        if (!$this->hasHeader($headers, 'content-disposition')) {
            $headers['Content-Disposition'] = sprintf(
                'form-data; name="%s"', $name
            );
            if ($hasFilename) {
                $headers['Content-Disposition'] .= sprintf(
                    '; filename="%s"', $this->basename($filename)
                );
            }
        }

        if (!$this->hasHeader($headers, 'content-length')) {
            if ($length = $stream->getSize()) {
                $headers['Content-Length'] = (string) $length;
            }
        }

        if (!$this->hasHeader($headers, 'content-type') && $hasFilename) {
            if ($type = $this->getMimetype($filename)) {
                $headers['Content-Type'] = $type;
            }
        }

        return $headers;
    }

    private function getMimetype($filename): ?string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return self::$mimetypes[$extension] ?? null;
    }

    private function basename($path): string
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

    private function hasHeader(array $headers, $key): bool
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
