<?php
class RequestFileBody extends RequestBody
{

    /**
     * @var string $fileName 文件名
     */
    public $fileName;

    public $fileSize;

    /**
     * @return bool 传入内容是否是文件
     */
    public function isFile () {
        return !is_null($this->fileName);
    }

    /**
     * @param RequestBody $body
     * @return RequestFileBody
     */
    public static function copyOfRequestBody(RequestBody $body):RequestFileBody {
        $o = new self();
        $o->content = $body->content;
        $o->contentType = $body->contentType;
        $o->requestName = $body->requestName;
        $o->contentDispostion = $body->contentDispostion;
        return $o;
    }

    public function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    public function getFileType() {
        $key = array_search($this->contentType, HttpMimeType::MIME_TYPE_LIST);
        if (empty($key)) {
            return HttpMimeType::EXT_JPEG;
        }
        return $key;
    }
}
