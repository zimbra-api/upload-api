<?php declare(strict_types=1);
/**
 * This file is part of the Zimbra API in PHP library.
 *
 * © Nguyen Van Nguyen <nguyennv1981@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zimbra\Upload;

use Http\Discovery\{
    Psr17FactoryDiscovery,
    Psr18ClientDiscovery
};
use Http\Message\MultipartStream\MultipartStreamBuilder;
use Psr\Log\{LoggerAwareInterface, LoggerInterface, NullLogger};
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Psr\Http\Message\{
    RequestFactoryInterface, RequestInterface, ResponseInterface, StreamFactoryInterface
};

/**
 * Client class in Zimbra Upload API.
 * 
 * @package   Zimbra
 * @category  Upload
 * @author    Nguyen Van Nguyen - nguyennv1981@gmail.com
 * @copyright Copyright © 2022-present by Nguyen Van Nguyen.
 */
class Client implements LoggerAwareInterface
{
    const MULTIPART_CONTENT_TYPE = 'multipart/form-data; boundary = "{boundary}"';
    const ZM_ADMIN_AUTH_TOKEN    = 'ZM_ADMIN_AUTH_TOKEN';
    const ZM_AUTH_TOKEN          = 'ZM_AUTH_TOKEN';
    const QUERY_FORMAT           = 'raw,extended';
    const REQUEST_METHOD         = 'POST';
    const REQUIRED_FILE_MESSAGE  = 'Upload request must have at least one file.';

    /**
     * Upload url
     * 
     * @var string
     */
    private string $uploadUrl;

    /**
     * Http auth token cookie
     * 
     * @var string
     */
    private string $authTokenCookie;

    /**
     * Http client
     * 
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * Request factory
     * 
     * @var RequestFactoryInterface
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * Stream factory
     * 
     * @var StreamFactoryInterface
     */
    private StreamFactoryInterface $streamFactory;

    /**
     * Http request message
     * 
     * @var RequestInterface
     */
    private ?RequestInterface $httpRequest = NULL;

    /**
     * Http response message
     * 
     * @var ResponseInterface
     */
    private ?ResponseInterface $httpResponse = NULL;

    /**
     * Logger
     * 
     * @var LoggerInterface
     */
    private ?LoggerInterface $logger = NULL;

    /**
     * Constructor
     *
     * @param string $uploadUrl
     * @param string $authToken
     * @param bool   $isAdmin
     */
    public function __construct(
        string $uploadUrl = '',
        string $authToken = '',
        bool $isAdmin = FALSE
    )
    {
        $this->uploadUrl = trim($uploadUrl);
        $this->authTokenCookie = strtr('{name}={authToken}', [
            '{name}' => $isAdmin ? self::ZM_ADMIN_AUTH_TOKEN : self::ZM_AUTH_TOKEN,
            '{authToken}' => trim($authToken),
        ]);

        $this->httpClient = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    private function parseResponse(): array
    {
        $attactments = [];
        if($this->httpResponse instanceof ResponseInterface) {
            $body = $response->getBody()->getContents();
            $this->getLogger()->debug('Response body', ['body' => $body]);
            preg_match('/\[\{(.*)\}\]/', $body, $matches, PREG_OFFSET_CAPTURE, 3);
            $match = $matches[0][0] ?? FALSE;
            if (!empty($match)) {
                $data = json_decode($match);
                if (is_array($data)) {
                    $attactments = array_map(static function($object) {
                        $attachmentId = $object->aid ?? '';
                        $fileName = $object->filename ?? '';
                        $contentType = $object->ct ?? '';
                        return new Attactment($attachmentId, $fileName, $contentType);
                    }, $data);
                }
                else {
                    $attachmentId = $data->aid ?? '';
                    $fileName = $data->filename ?? '';
                    $contentType = $data->ct ?? '';
                    $attactments[] = new Attactment($attachmentId, $fileName, $contentType);
                }
            }
        }
        return $attactments;
    }

    /**
     * Performs a upload request
     *
     * @param  Request $request
     * @return array
     */
    public function upload(Request $request): array
    {
        if (empty($this->request->getFiles())) {
            throw new \UnexpectedValueException(
                self::REQUIRED_FILE_MESSAGE
            );
        }

        $builder = new MultipartStreamBuilder($this->streamFactory);
        $builder->addResource('requestId', $request->getRequestId(), [
            'headers' => [
                'Content-Type' => 'text/plain',
            ]
        ]);
        foreach ($request->getFiles() as $file) {
            $builder->addResource($file->getFilename(), fopen($file->getRealPath(), 'r'), [
                'filename' => $file->getFilename(),
            ]);
            $this->getLogger()->debug('Upload file', ['file' => $file->getRealPath()]);
        }

        $uploadUrl = $this->uploadUrl . '?' . http_build_query(['fmt' => self::QUERY_FORMAT]);
        $this->httpRequest = $this->requestFactory
            ->createRequest(self::REQUEST_METHOD, $uploadUrl)
            ->withBody($builder->build())
            ->withHeader('Cookie', $this->authTokenCookie)
            ->withHeader('Content-Type', strtr(self::MULTIPART_CONTENT_TYPE, [
                '{boundary}' => $builder->getBoundary(),
            ]));

        try {
            $this->httpResponse = $this->httpClient->sendRequest($this->httpRequest);
        }
        catch (ClientExceptionInterface $ex) {
            throw $ex;
        }
        return $this->parseResponse();
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (!($this->logger instanceof LoggerInterface)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get http client
     *
     * @return ClientInterface
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Get http request.
     *
     * @return RequestInterface
     */
    public function getHttpRequest(): ?RequestInterface
    {
        return $this->httpRequest;
    }

    /**
     * Get http response.
     *
     * @return ResponseInterface
     */
    public function getHttpResponse(): ?ResponseInterface
    {
        return $this->httpResponse;
    }
}
