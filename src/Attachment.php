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
 * Attachment class in Zimbra Upload API.
 * 
 * @package   Zimbra
 * @category  Upload
 * @author    Nguyen Van Nguyen - nguyennv1981@gmail.com
 * @copyright Copyright © 2016-present by Nguyen Van Nguyen.
 */
class Attachment
{
    /**
     * Constructor
     *
     * @param  string $attachmentId
     * @param  string $fileName
     * @param  string $contentType
     * @param  int $size
     * @return self
     */
    public function __construct(
        private string $attachmentId = '',
        private string $fileName = '',
        private string $contentType = '',
        private int $size = 0
    )
    {
    }

    /**
     * Get attachment id
     *
     * @return string
     */
    public function getAttachmentId(): string
    {
        return $this->attachmentId;
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Get content type
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Get size
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
}
