<?php
class RequestFileBody extends RequestBody
{

    /**
     * @var string $fileName 文件名
     */
    private $fileName;

    private $fileSize;

    /**
     * @return bool 传入内容是否是文件
     */
    public function isFile () {
        return !is_null($this->fileName);
    }
}
