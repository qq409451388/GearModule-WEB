<?php
class BasicInterpreter implements Interpreter {
    private $requestHandler;
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

    public function getNotFoundResourceResponse(IRequest $request): IResponse
    {
        return null;
    }

    public function getNetErrorResponse(IRequest $request, string $errorMessage = ""): IResponse
    {
        return null;
    }

    public function getDynamicResponse(IRequest $request): IResponse
    {
        return null;
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
