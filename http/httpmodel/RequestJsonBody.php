<?php

class RequestJsonBody extends RequestBody implements EzDataObject
{
    /**
     * @var string $contentType
     */
    public $contentType = HttpMimeType::MIME_JSON;

    /**
     * @var string $content requestData
     */
    public $content;

    public function toString () {
        return EzObjectUtils::toString(get_object_vars($this));
    }

    public function getData() {
        return EzCodecUtils::decodeJson($this->content);
    }

    public function getObject(Clazz $clazz) {
        return EzObjectUtils::createFromJson($this->content, $clazz->getName());
    }
}
