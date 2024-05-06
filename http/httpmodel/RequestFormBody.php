<?php

class RequestFormBody extends RequestBody
{
    /**
     * @var array<string, string>
     */
    private $data;

    /**
     * @link HttpMimeType
     * @var string $contentType
     */
    public $contentType = HttpMimeType::MIME_WWW_FORM_URLENCODED;

    public function add($k, $v) {
        $this->data[$k] = $v;
    }

    public function addAll(array $arr) {
        foreach ($arr as $k => $v) {
            $this->add($k, $v);
        }
    }

    public function get($key) {
        return $this->data[$key]??null;
    }

    public function getAllForm() {
        return $this->data;
    }
}
