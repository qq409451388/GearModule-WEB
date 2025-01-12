<?php
class Response extends EzObject implements IResponse,EzDataObject
{
    /**
     * @var HttpStatus
     */
    private $httpStatus;
    private $content;
    private $contentType;
    private $charset;
    private $customHeaders;

    public function __construct(HttpStatus $header, $content = null, $contentType = HttpMimeType::MIME_HTML, $charset = "charset=utf-8;"){
        $this->httpStatus = $header;
        $this->setContent($content);
        if (empty($contentType)) {
            $this->setContentType($this->guessContent());
        } else {
            $this->setContentType($contentType);
        }
        $this->setCharset($charset);
    }

    public function setContent($content){
        $this->content = $content;
    }

    public function getContent(){
        return is_null($this->content) ? "" : $this->content;
    }

    public function setContentType($contentType){
        $this->contentType = $contentType;
    }

    public function setCharset($charset) {
        $this->charset = $charset;
    }

    public function getContentType(){
        return $this->contentType;
    }

    private function guessContent(){
        if(isset($this->contentType)){
            return $this->contentType;
        }
        if ($this->content instanceof EzRpcResponse) {
            $this->content = $this->content->toJson();
            return HttpMimeType::MIME_JSON;
        } elseif (null !== json_decode($this->content, true)) {
            return HttpMimeType::MIME_JSON;
        } else {
            return HttpMimeType::MIME_JSON;
        }
    }

    public function getCharset() {
        return $this->charset;
    }

    public function getHeader(){
        return $this->httpStatus;
    }

    public function toString():string{
        return (new HttpInterpreter())->encode($this);
    }

    /**
     * @return mixed
     */
    public function getCustomHeaders()
    {
        return $this->customHeaders;
    }

    /**
     * @param mixed $customHeaders
     */
    public function setCustomHeaders($customHeaders): void
    {
        $this->customHeaders = $customHeaders;
    }

    public function setCustomHeader($key, $customHeader): void
    {
        $this->customHeaders[$key] = $customHeader;
    }
}
