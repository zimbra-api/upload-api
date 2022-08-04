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

use SplFileInfo;

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
    /**
     * Request id
     * @var string
     */
    private string $requestId;

    /**
     * Upload files
     * @var array
     */
    private array $files = [];

    /**
     * Constructor
     *
     * @param  array  $files
     * @param  string $requestId
     * @return self
     */
    public function __construct(array $files = [], string $requestId = '')
    {
        $this->setFiles($files)
             ->setRequestId($requestId);
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
     * Set request id
     *
     * @param  string $requestId
     * @return self
     */
    public function setRequestId(string $requestId): self
    {
        $this->requestId = trim($requestId);
        return $this;
    }

    /**
     * Get files
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Set files
     *
     * @param array $files
     * @return self
     */
    public function setFiles(array $files = []): self
    {
        $this->files = array_filter(
            $files, static fn ($file) => ($file instanceof SplFileInfo && $file->isFile())
        );
        return $this;
    }

    /**
     * Add a file
     *
     * @param SplFileInfo $file
     * @return self
     */
    public function addFile(SplFileInfo $file)
    {
        $this->files[] = $file;
        return $this;
    }
}
