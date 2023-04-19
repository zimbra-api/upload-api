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

/**
 * Request class in Zimbra Upload API.
 * 
 * @package   Zimbra
 * @category  Upload
 * @author    Nguyen Van Nguyen - nguyennv1981@gmail.com
 * @copyright Copyright © 2016-present by Nguyen Van Nguyen.
 */
class Request
{
    const ACCOUNT_AUTH_TOKEN = 'ZM_AUTH_TOKEN';
    const ADMIN_AUTH_TOKEN   = 'ZM_ADMIN_AUTH_TOKEN';

    /**
     * Http auth token cookie
     * 
     * @var string
     */
    private string $authTokenCookie;

    /**
     * Constructor
     *
     * @param  array  $files
     * @param  string $requestId
     * @param  string $authToken
     * @param  bool   $isAdmin
     * @return self
     */
    public function __construct(
        private array $files = [],
        private string $requestId = '',
        string $authToken = '',
        bool $isAdmin = FALSE,
    )
    {
        $this->authTokenCookie = strtr('{name}={authToken}', [
            '{name}' => $isAdmin ? self::ADMIN_AUTH_TOKEN : self::ACCOUNT_AUTH_TOKEN,
            '{authToken}' => trim($authToken),
        ]);
    }

    /**
     * Get request id
     *
     * @return string
     */
    public function getRequestId(): string
    {
        if (empty($this->requestId)) {
            $this->requestId = uniqid(bin2hex(random_bytes(8)), TRUE);
        }
        return $this->requestId;
    }

    /**
     * Get auth token cookie
     *
     * @return string
     */
    public function getAuthTokenCookie(): string
    {
        return $this->authTokenCookie;
    }

    /**
     * Get files
     *
     * @return array
     */
    public function getFiles(): array
    {
        return array_filter(
            $this->files, static fn ($file) => ($file instanceof \SplFileInfo && $file->isFile())
        );
    }
}
