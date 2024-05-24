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
        $this->localCache = BeanFinder::get()->pull(EzLocalCache::class);
        $this->localCache->memoryCost = memory_get_usage(true);
        $this->localCache->memoryLimit = 100 * 1024 * 1024;
        $this->localCache->memoryLimit = $this->localCache->memoryLimit + $this->localCache->memoryCost;
    }

    protected function buildResponse(EzConnection $connection, IRequest $request): IResponse
    {
        var_dump($request);
        return $this->interpreter->getDynamicResponse($request);
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
