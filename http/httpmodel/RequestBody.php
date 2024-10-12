<?php

/**
 * 基础请求体对象
 */
class RequestBody extends EzObject implements EzDataObject
{
    /**
     * @var string $requestName the key of requestData
     */
    public $requestName;

    /**
     * @link HttpMimeType
     * @var string $contentType
     */
    public $contentType = HttpMimeType::MIME_HTML;

    /**
     * @see EzCurlBody
     * @var string $contentDispostion
     */
    public $contentDispostion;


    /**
     * @var string $content requestData
     */
    public $content;

    public function getContent() {
        return $this->content;
    }

}
