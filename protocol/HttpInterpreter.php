<?php

class HttpInterpreter implements Interpreter
{
    public function getSchema():string {
        return SchemaConst::HTTP;
    }

    /**
     * @param Response $response
     * @return string
     */
    public function encode(IResponse $response): string {
        $header = "HTTP/1.1 {$response->getHeader()->getCode()} {$response->getHeader()->getStatus()}\r\n";
        $header .= "Server: Gear2\r\n";
        $header .= "Date: ".gmdate('D, d M Y H:i:s T')."\r\n";
        $header .= "Content-Type: {$response->getContentType()}\r\n";
        $header .= "Content-Length: ".strlen($response->getContent());
        $header .= "\r\n";
        if (!empty($response->getContent())) {
            $header .= "\r\n";
            $header .= $response->getContent();
        } else if ($response->getHeader()->getCode() != 200) {
            $header .= "\r\n";
        }
        return $header;
    }

    public function decode(string $buf): IRequest {
        $httpRequestInfos = $this->buildHttpRequestSource($buf);
        //检查请求类型
        $this->check($httpRequestInfos->accept);
        //获取web路径
        list($path, $args) = $this->parseUri($httpRequestInfos->path);
        $request = new Request();
        $request->setPath($path);
        $request->setQuerys($args);
        $request->setContentLen($httpRequestInfos->contentLength);
        $request->setContentLenActual($httpRequestInfos->contentLengthActual);
        $request->setContentType($httpRequestInfos->contentType);
        $request->setRequestMethod(HttpMethod::get($httpRequestInfos->requestMethod));
        $request->setIsInit($httpRequestInfos->contentLength == $httpRequestInfos->contentLengthActual);
        $request->setHeaders($httpRequestInfos->getCustomHeaders());
        if ($request->isInit()) {
            $requestBody = $this->buildHttpRequestBody($httpRequestInfos);
            $this->buildRequestArgs($requestBody, $args, $request);
        }
        $request->setRequestSource($httpRequestInfos);
        return $request;
    }

    private function buildHttpRequestSource($buf):RequestSource{
        $requestSource = new RequestSource();
        $httpOptions = explode(Env::eol(Env::OS_WINDOWS), $buf);
        $firstLine = explode(" ", array_shift($httpOptions));
        $requestSource->requestMethod = strtolower($firstLine[0]);
        $requestSource->path = $firstLine[1]??"";
        $requestSource->httpVer = $firstLine[2]??"";
        $requestSource->contentLengthActual = 0;
        $requestSource->contentLength = 0;
        $whenBody = false;
        $body = "";
        while(true){
            $httpOption = array_shift($httpOptions);
            if(false === $httpOption || is_null($httpOption)){
                break;
            }
            if($whenBody){
                $body .= $httpOption.Env::eol(Env::OS_WINDOWS);
            }else{
                if(empty($httpOption)){
                    $whenBody = true;
                    continue;
                }
                $pos = strpos($httpOption, ":");
                $key = EzStringUtils::camelCase(substr($httpOption, 0, $pos), "-");
                $value = trim(substr($httpOption, $pos+1));
                if($key == "contentType"){
                    $contentType = explode(";", $value);
                    $value = new HttpContentType();
                    $value->contentType = trim($contentType[0]);
                    $value->boundary = trim(str_replace("boundary=", "", $contentType[1]??""));
                }
                if (property_exists($requestSource, $key)) {
                    $requestSource->$key = is_numeric($value) ? EzObjectUtils::convertScalarToTrueType($value, "int") : $value;
                } else {
                    $requestSource->setCustomHeader($key, $value);
                }
            }

        }
        $body = substr($body, 0, -strlen(Env::eol(Env::OS_WINDOWS)));
        $requestSource->contentLengthActual = strlen($body);
        $requestSource->bodyContent = $body;
        return $requestSource;
    }

    protected function check($type){
        if(empty($type)) {
            return;
        }
        if(empty(array_diff(HttpMimeType::MIME_TYPE_LIST, explode(",", $type)))){
            Logger::console("[EzServer] UnSupport Type : $type");
        }
    }

    protected function parseUri($webPath){
        $webPath = trim($webPath, "/");
        $pathArr = parse_url($webPath);
        $path = $pathArr['path'] ?? '';
        $query = $pathArr['query'] ?? '';
        parse_str($query, $args);
        return [$path, $args];
    }

    public function buildHttpRequestBody(RequestSource $requestSource){
        if ($requestSource->isBlank()) {
            return null;
        }
        $contentType = @$requestSource->contentType->contentType??null;
        switch ($contentType) {
            case HttpMimeType::MIME_WWW_FORM_URLENCODED:
                return $this->buildHttpRequestFormBody($requestSource);
            case HttpMimeType::MIME_JSON:
                return $this->buildHttpRequestJsonBody($requestSource);
            case HttpMimeType::MIME_MULTI_FORM:
                return $this->buildHttpRequestBodyMultiPartForm($requestSource);
            default:
                return $this->buildHttpRequestDefaultBody($requestSource, $contentType);
        }
    }

    private function buildHttpRequestDefaultBody(RequestSource $requestSource, $contentType) {
        $requestBodyObj = new RequestBody();
        $requestBodyObj->contentType = $contentType;
        $requestBodyObj->content = $requestSource->bodyContent;
        $requestBodyObj->contentDispostion = "DEFAULT";
        return $requestBodyObj;
    }

    private function buildHttpRequestFormBody(RequestSource $requestSource) {
        parse_str($requestSource->bodyContent, $requestBodyArr);
        $requestBodyObj = new RequestFormBody();
        $requestBodyObj->addAll($requestBodyArr);
        return $requestBodyObj;
    }

    private function buildHttpRequestJsonBody(RequestSource $requestSource) {
        $requestBodyObj = new RequestJsonBody();
        $requestBodyObj->content = $requestSource->bodyContent;
        return $requestBodyObj;
    }

    private function buildHttpRequestBodyMultiPartForm(RequestSource $requestSource) {
        $requestBodyObj = new RequestMultiBody();

        $requestBody = $requestSource->bodyContent;
        $requestBodyArrInit = explode("\r\n", $requestBody);
        /**
         * @var $requestBodyArr array<string, RequestBody>
         */
        $requestBodyArr = [];
        foreach ($requestBodyArrInit as $requestBodyLine) {
            $matchBoundary = false;
            if (!empty($requestBodyLine)) {
                $matchBoundary = false !== strpos($requestBodyLine, $requestSource->contentType->boundary);
            }
            if ($matchBoundary) {
                $requestBodyItemObj = new RequestBody();
                //是否完成body行参数取值
                $flag = true;
            } else if (($flag && !empty($requestBodyLine))) {
                if (is_null($requestBodyObj->contentDispostion)) {
                    preg_match('/Content-Disposition: (?<contentDispostion>\S+);.*/', $requestBodyLine, $matches);
                    $requestBodyObj->contentDispostion = $matches['contentDispostion']??null;
                }
                if (is_null($requestBodyItemObj->requestName)) {
                    preg_match('/(.*)name="(?<requestName>[\/a-zA-Z0-9]+)"(.*)/', $requestBodyLine, $matches);
                    $requestBodyItemObj->requestName = $matches['requestName']??null;
                    //初始化
                    $requestBodyArr[$requestBodyItemObj->requestName] = $requestBodyItemObj;
                }
                preg_match('/filename="(?<fileName>(.*))"/', $requestBodyLine, $matches);
                if (isset($matches['fileName'])) {
                    $requestBodyItemObj = RequestFileBody::copyOfRequestBody($requestBodyItemObj);
                    $requestBodyArr[$requestBodyItemObj->requestName] = $requestBodyItemObj;
                    $requestBodyItemObj->setFileName($matches['fileName']);
                }
                preg_match('/Content-Type: (?<contentType>[\/a-zA-Z0-9]+)(.*)/', $requestBodyLine, $matches);
                if (is_null($requestBodyItemObj->contentType) || !empty($matches['contentType'])) {
                    if (!in_array($matches['contentType'], HttpMimeType::MIME_TYPE_LIST)) {
                        Logger::warn("[EzServer] Unknow Content-Type : ".$matches['contentType']);
                    }
                    $requestBodyItemObj->contentType = $matches['contentType']??null;
                }
            } elseif (empty($requestBodyLine)) {
                $flag = false;
            } elseif (!$flag && $isEmptyLine) {
                if (is_null($requestBodyItemObj->content)) {
                    $requestBodyItemObj->content = $requestBodyLine;
                } else {
                    $requestBodyItemObj->content .= "\r\n".$requestBodyLine;
                }
                $isEmptyLine = true;
                continue;
            }
            //为下一行数据使用
            $isEmptyLine = empty($requestBodyLine);
        }
        $requestBodyObj->data = $requestBodyArr;
        return $requestBodyObj;
    }

    public function buildRequestArgs($requestBody, $args, IRequest $request){
        $request->setBody($requestBody);
        if (empty($args)) {
            return;
        }
        foreach ($args as $k => $v) {
            $request->setQuery($k, $v);
        }
    }

}
