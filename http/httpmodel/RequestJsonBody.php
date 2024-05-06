<?php

class RequestJsonBody extends RequestBody
{
    /**
     * @var string $contentType
     */
    public $contentType = HttpMimeType::MIME_JSON;

    private $contentCache;

    public function get($key) {
        if (is_null($this->contentCache)) {
            $this->contentCache = EzCodecUtils::decodeJson($this->content);
        }
        return $this->contentCache[$key]??null;
    }

    public function getObject(Clazz $clazz) {
        return EzObjectUtils::createFromJson($this->content, $clazz->getName());
    }
}
