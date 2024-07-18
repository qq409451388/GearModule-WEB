<?php

/**
 * build for http
 */
class Request extends NetWorkRequest implements IRequest,EzDataObject
{
    /**
     * @var HttpContentType $contentType
     */
    private $contentType;

    //content-length
    private $contentLen;
    private $contentLenActual;

    //get post mixed
    private $requestMethod = null;
    private $path;

    private $query = [];
    /**
     * @var RequestJsonBody|RequestMultiBody|RequestFormBody|RequestBody
     */
    private $body;

    /**
     * @var RequestSource $requestSource
     */
    private $requestSource = null;

    private $requestId;

    /**
     * 自定义的headers
     * @var array<string, string>
     */
    private $customHeaders;

    public function setQuery($key, $value){
        $this->query[$key] = $value;
    }

    public function setQuerys(array $arr) {
        foreach ($arr as $key => $value) {
            $this->setQuery($key, $value);
        }
    }

    public function get($key, $default=null){
        return isset($this->query[$key]) ? $this->query[$key] : $default;
    }

    public function getQuery(){
        return $this->query;
    }

    public function setBody($body){
        $this->body = $body;
    }

    /**
     * 获取请求体
     * @return array<string, string|int|float>
     */
    public function getStructBodyData(){
        $body = $this->getBody();
        if ($body instanceof RequestJsonBody) {
            return $body->getData();
        } else if ($body instanceof RequestFormBody) {
            return $body->getAllForm();
        } else if ($body instanceof RequestMultiBody) {
            return $body->getTextMap();
        } else {
            return [];
        }
    }

    /**
     * 获取请求体
     * @var RequestJsonBody $body when {@link HttpMimeType::MIME_JSON}
     * @var RequestFormBody $body when {@link HttpMimeType::MIME_WWW_FORM_URLENCODED}
     * @var RequestMultiBody $body when {@link HttpMimeType::MIME_MULTI_FORM}
     * @var RequestBody $body when else Content-type
     * @return RequestBody|RequestJsonBody|RequestMultiBody|RequestFormBody
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @return RequestJsonBody|null
     */
    public function getJsonRequestBody() {
        return $this->body instanceof RequestJsonBody ? $this->body : null;
    }

    /**
     * @return RequestFormBody|null
     */
    public function getNormalRequestBody() {
        return $this->body instanceof RequestFormBody ? $this->body : null;
    }

    /**
     * @return RequestMultiBody|null
     */
    public function getMultiRequestBody() {
        return $this->body instanceof RequestMultiBody ? $this->body : null;
    }

    public function filter(){
        //todo
    }

    public function isEmpty():bool{
        DBC::assertTrue($this->isInit(), "[Request] Exception Has Not Inited!");
        return empty($this->body) && empty($this->query);
    }

    public function setRequestMethod($requestMethod){
        $this->requestMethod = $requestMethod;
    }

    public function getRequestMethod(){
        return $this->requestMethod;
    }

    public function getPath():string{
        return $this->path;
    }

    public function setPath($path){
        $this->path = $path;
    }

    public function setContentType($contentType){
        $this->contentType = $contentType;
    }

    public function setContentLen($contentLen){
        $this->contentLen = $contentLen;
    }

    public function setContentLenActual($contentLen){
        $this->contentLenActual = $contentLen;
        if ($this->requestSource instanceof RequestSource) {
            $this->requestSource->contentLengthActual = $contentLen;
        }
    }

    public function check() {
        if(!is_null($this->contentLen) && !is_null($this->contentLenActual)
            && $this->contentLen != $this->contentLenActual){
            return HttpMimeType::MIME_MULTI_FORM == $this->contentType->contentType ?
                HttpStatus::CONTINUE() : HttpStatus::EXPECTATION_FAIL();
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isInit(): bool
    {
        return $this->isInit;
    }

    /**
     * @param bool $isInit
     */
    public function setIsInit(bool $isInit): void
    {
        $this->isInit = $isInit;
    }

    public function getContentLen() {
        return $this->contentLen;
    }

    /**
     * @param RequestSource|null $requestSource
     * @return void
     */
    public function setRequestSource($requestSource) {
        $this->requestSource = $requestSource;
    }

    /**
     * @return RequestSource|null
     */
    public function getRequestSource() {
        return $this->requestSource;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $id)
    {
        $this->requestId = $id;
    }

    public function toString () {
        return EzObjectUtils::toString($this);
    }

    /**
     * @return mixed
     */
    public function getContentLenActual()
    {
        return $this->contentLenActual;
    }

    public function toArray(): array {
        return $this->query;
    }

    public function setHeaders($customHeaders) {
        $this->customHeaders = $customHeaders;
    }

    public function getHeader($key) {
        return $this->customHeaders[$key]??null;
    }

}
