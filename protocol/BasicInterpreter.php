<?php
class BasicInterpreter implements Interpreter {
    /**
     * @var Closure $requestHandler
     */
    private $requestHandler;

    /**
     * @var Closure $responseHandler
     */
    private $responseHandler;

    public function getSchema(): string
    {
        return SchemaConst::TCP;
    }

    public function encode(IResponse $response): string
    {
        return ($this->responseHandler)($response);
    }

    public function decode(string $content): IRequest
    {
        return ($this->requestHandler)($content);
    }

    /**
     * 请求对象构建函数
     * @param Closure $requestHandler
     * @return $this
     */
    public function setRequestHandler(Closure $requestHandler) {
        $this->requestHandler = $requestHandler;
        return $this;
    }

    /**
     * 响应对象构建函数
     * @param Closure $responseHandler}
     * @return $this
     */
    public function setResponseHandler(Closure $responseHandler) {
        $this->responseHandler = $responseHandler;
        return $this;
    }
}
