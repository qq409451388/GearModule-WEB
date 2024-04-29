<?php
class EzTcpServer extends AbstractTcpServer
{
    protected function buildResponse(EzConnection $connection, IRequest $request): IResponse
    {
        try {
            return ($this->responseBuilder)($request);
        } catch (GearNotFoundResourceException $exception) {
            return $this->getNotFoundResourceResponse($request);
        } catch (Exception $exception) {
            return $this->getNetErrorResponse($request);
        }
    }

    /**
     * @var Closure $responseBuilder
     */
    private $responseBuilder;

    public function setRequestHandler(Closure $requestHandler) {
        $this->interpreter->setRequestHandler($requestHandler);
    }

    public function setResponseHandler(Closure $responseHandler) {
        $this->interpreter->setResponseHandler($responseHandler);
    }

    public function setResponseBuilder(Closure $responseBuilder) {
        $this->responseBuilder = $responseBuilder;
    }

    protected function getNotFoundResourceResponse(IRequest $request): IResponse
    {
        $tcp = new TcpMessage();
        $tcp->data = "NotFound";
        return $tcp;
    }

    protected function getNetErrorResponse(IRequest $request, string $errorMessage = ""): IResponse
    {
        $tcp = new TcpMessage();
        $tcp->data = "NetError";
        return $tcp;
    }

}
