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

/**
 * Attactment class in Zimbra Upload API.
 * 
 * @package   Zimbra
 * @category  Upload
 * @author    Nguyen Van Nguyen - nguyennv1981@gmail.com
 * @copyright Copyright © 2022-present by Nguyen Van Nguyen.
 */
class Attactment
{
    /**
     * Attachment id
     * @var string
     */
    private string $attachmentId;

    /**
     * File name
     * @var string
     */
    private string $fileName;

    /**
     * Content type
     * @var string
     */
    private string $contentType;

    /**
     * Constructor method for Attactment
     *
     * @param  string $attachmentId
     * @param  string $fileName
     * @param  string $contentType
     * @return self
     */
    public function __construct(
        string $attachmentId = '', string $fileName = '', string $contentType = ''
    )
    {
        $this->attachmentId = $attachmentId;
        $this->fileName = $fileName;
        $this->contentType = $contentType;
    }

    /**
     * Gets attachment id
     *
     * @return string
     */
    public function getAttachmentId(): string
    {
        return $this->attachmentId;
    }

    /**
     * Gets file name
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Gets content type
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }
}
