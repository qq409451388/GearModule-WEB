<?php
class EzResp extends AbstractTcpServer
{
    /**
     * 本地缓存服务
     * @var EzLocalCache
     */
    private $localCache;

    protected function initInterpreter():Interpreter {
       return new RespInterpreter();
    }

    protected function setPropertyCustom() {
        $this->setKeepAlive();
        $this->localCache = BeanFinder::get()->pull(EzLocalCache::class);
        $this->localCache->memoryCost = memory_get_usage(true);
        $this->localCache->memoryLimit = 100 * 1024 * 1024;
        $this->localCache->memoryLimit = $this->localCache->memoryLimit + $this->localCache->memoryCost;
    }

    protected function buildResponse(EzConnection $connection, IRequest $request): IResponse
    {
        try {
            if ("COMMAND" == $request->command) {
                if ("DOCS" == current($request->args)) {
                    $docs = file_get_contents(Application::getContext()->getGearPath()."/modules/web/ezresp/docs.txt");
                    $result = "$".strlen($docs);
                    $result .= PHP_EOL;
                    $result .= $docs;
                }
            } else {
                DBC::assertTrue(method_exists($this->localCache, $request->command),
                    "[EzResp Exception] Unknow Command $request->command!");
                try {
                    $result = call_user_func_array([$this->localCache, $request->command], $request->args);
                } catch (Throwable $e) {
                    DBC::throwEx("[EzResp Exception] Unknow Command $request->command!", 0);
                    $result = "Unknow";
                }
            }
            $this->localCache->tryRelease();
            $response = new RespResponse();
            if (is_bool($result)) {
                $isSuccess = $result;
                $response->resultDataType = RespResponse::TYPE_BOOL;
            } else if (is_array($result)) {
                $response->resultDataType = RespResponse::TYPE_ARRAY;
                $isSuccess = true;
            } else if (is_int($result)) {
                $response->resultDataType = RespResponse::TYPE_INT;
                $isSuccess = true;
            } else {
                $response->resultDataType = RespResponse::TYPE_NORMAL;
                $isSuccess = true;
            }
            $response->isSuccess = $isSuccess;
            $response->resultData = $result;
        } catch (Exception $e) {
            $response = new RespResponse();
            $response->resultDataType = RespResponse::TYPE_BOOL;
            $response->isSuccess = false;
            $response->msg = $e->getMessage();
        }
        return $response;
    }

    protected function getNotFoundResourceResponse(IRequest $request): IResponse
    {
        return $this->interpreter->getNotFoundResourceResponse($request);
    }

    protected function getNetErrorResponse(IRequest $request, string $errorMessage = ""): IResponse
    {
        return $this->interpreter->getNetErrorResponse($request, $errorMessage);
    }
}
