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

use Http\Discovery\{
    Psr17FactoryDiscovery,
    Psr18ClientDiscovery
};
use Http\Message\MultipartStream\MultipartStreamBuilder;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger};
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{
    RequestFactoryInterface, RequestInterface, ResponseInterface, StreamFactoryInterface
};

/**
 * Client class in Zimbra Upload API.
 * 
 * @package   Zimbra
 * @category  Upload
 * @author    Nguyen Van Nguyen - nguyennv1981@gmail.com
 * @copyright Copyright © 2016-present by Nguyen Van Nguyen.
 */
class Client implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const HTTP_USER_AGENT        = 'PHP-Zimbra-Upload-Client';
    const MULTIPART_CONTENT_TYPE = 'multipart/form-data; boundary = "%s"';
    const QUERY_FORMAT           = 'raw,extended';
    const REQUEST_METHOD         = 'POST';
    const REQUIRED_FILE_MESSAGE  = 'Upload request must have at least one file.';
    const RESPONSE_BODY_MESSAGE  = 'Response body from Zimbra';
    const TEXT_CONTENT_TYPE      = 'text/plain';
    const UPLOAD_FILE_MESSAGE    = 'Upload file to Zimbra';

    /**
     * @var array
     */
    private static $originatingIpHeaders = [
        'Client-Ip',
        'Forwarded-For',
        'X-Client-Ip',
        'X-Forwarded-For',
    ];

    /**
     * @var array
     */
    private static $serverOriginatingIpHeaders = [
        'HTTP_CLIENT_IP',
        'HTTP_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_X_CLIENT_IP',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_X_FORWARDED',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];

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
     * Constructor
     *
     * @param string $uploadUrl
     * @param ClientInterface $httpClient
     * @param RequestFactoryInterface $requestFactory
     */
    public function __construct(
        private string $uploadUrl = '',
        ?ClientInterface $httpClient = NULL,
        ?RequestFactoryInterface $requestFactory = NULL
    )
    {
        $this->httpClient = $httpClient ?: Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
    }

    private function parseResponse(): array
    {
        $attactments = [];
        if($this->httpResponse instanceof ResponseInterface) {
            $body = $this->httpResponse->getBody()->getContents();
            $this->getLogger()->debug(self::RESPONSE_BODY_MESSAGE, ['body' => $body]);
            preg_match('/\[\{(.*)\}\]/', $body, $matches, PREG_OFFSET_CAPTURE, 3);
            $match = $matches[0][0] ?? FALSE;
            if (!empty($match)) {
                $data = json_decode($match);
                if (is_array($data)) {
                    $attactments = array_map(static function($obj) {
                        return new Attachment(
                            $obj->aid ?? '', $obj->filename ?? '', $obj->ct ?? '', $obj->s ?? 0
                        );
                    }, $data);
                }
                else {
                    $attactments[] = new Attachment(
                        $data->aid ?? '', $data->filename ?? '', $data->ct ?? '', $data->s ?? 0
                    );
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
        if (empty($request->getFiles())) {
            throw new \UnexpectedValueException(
                self::REQUIRED_FILE_MESSAGE
            );
        }

        $builder = new MultipartStreamBuilder();
        $builder->addResource('requestId', $request->getRequestId(), [
            'headers' => [
                'Content-Type' => self::TEXT_CONTENT_TYPE,
            ]
        ]);
        foreach ($request->getFiles() as $file) {
            $builder->addResource($file->getFilename(), fopen($file->getRealPath(), 'r'), [
                'filename' => $file->getFilename(),
            ]);
            $this->getLogger()->debug(self::UPLOAD_FILE_MESSAGE, ['file' => $file->getRealPath()]);
        }

        $uploadUrl = $this->uploadUrl . '?' . http_build_query(['fmt' => self::QUERY_FORMAT]);
        $this->httpRequest = $this->requestFactory
            ->createRequest(self::REQUEST_METHOD, $uploadUrl)
            ->withBody($builder->build())
            ->withHeader('Cookie', $request->getAuthTokenCookie())
            ->withHeader('Content-Type', sprintf(self::MULTIPART_CONTENT_TYPE, $builder->getBoundary()))
            ->withHeader('User-Agent', $_SERVER['HTTP_USER_AGENT'] ?? self::HTTP_USER_AGENT);
        if (!empty(self::getOriginatingIp())) {
            foreach (self::$originatingIpHeaders as $header) {
                $this->httpRequest = $this->httpRequest->withHeader($header, self::getOriginatingIp());
            }
        }
        $this->httpResponse = $this->httpClient->sendRequest($this->httpRequest);
        return $this->parseResponse();
    }

    /**
     * Get the logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (!($this->logger instanceof LoggerInterface)) {
            $this->setLogger(new NullLogger());
        }
        return $this->logger;
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
     * Get http request
     *
     * @return RequestInterface
     */
    public function getHttpRequest(): ?RequestInterface
    {
        return $this->httpRequest;
    }

    /**
     * Get http response
     *
     * @return ResponseInterface
     */
    public function getHttpResponse(): ?ResponseInterface
    {
        return $this->httpResponse;
    }

    private static function getOriginatingIp(): ?string
    {
        static $ip = NULL;
        if (empty($ip) && !empty($_SERVER)) {
            foreach(self::$serverOriginatingIpHeaders as $header) {
                if (!empty($_SERVER[$header])) {
                    $ip = $_SERVER[$header];

                    // Some proxies typically list the whole chain of IP
                    // addresses through which the client has reached us.
                    // e.g. client_ip, proxy_ip1, proxy_ip2, etc.
                    sscanf($ip, '%[^,]', $ip);
                    break;
                }
            }
        }
        return $ip;
    }
}
