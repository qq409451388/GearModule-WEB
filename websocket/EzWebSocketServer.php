<?php
class EzWebSocketServer extends EzTcpServer
{
    /**
     * socketNo => isHandShake
     * @var array 是否已经握手
     */
    private $isHandShake = [];

    protected function initInterpreter():Interpreter {
        return new WebSocketInterpreter();
    }

    protected function createRequest(EzConnection $connection): IRequest
    {
        $request = new WebSocketRequest();
        $requestId = intval($connection->getClientSocket());
        if(!($this->isHandShake[$requestId]??false)) {
            $request->setPath(EzWebSocketMethodEnum::METHOD_HANDSHAKE);
            $request->sourceData = $connection->getBuffer();
        } else {
            $request = $this->interpreter->decode($connection->getBuffer());
        }
        $request->setRequestId($requestId);
        return $request;
    }

    protected function appendRequest(EzConnection $connection, IRequest $request): IRequest
    {

    }

    protected function setTcpServerInstance() {
        $this->interpreter->setRequestHandler(
            /**
             * @param EzConnection $connection
             * @param WebSocketRequest $request
             * @return IRequest
             */
            function (EzConnection $connection, $request):IRequest {

            }
        );
        $this->socket->setResponseHandler(
        /**
         * @var WebSocketRequest $request
         */
        function(EzConnection $connection, IRequest $request):IResponse {
            $webSocketResposne = new WebSocketResponse();
            $webSocketResposne->method = $request->getPath();
            try {
                switch ($request->getPath()) {
                    case EzWebSocketMethodEnum::METHOD_HANDSHAKE:
                        $webSocketResposne->response = $this->interpreter->doHandShake($request->sourceData);
                        $this->isHandShake[(int)$request->getRequestId()] = true;
                        return $webSocketResposne;
                    case EzWebSocketMethodEnum::METHOD_CALL:
                        return $this->interpreter->getDynamicResponse($request);
                    case EzWebSocketMethodEnum::METHOD_CONTRACT:
                        $webSocketResposne->response = EzCodecUtils::encodeJson(["a" => "hello world!"]);
                        return $webSocketResposne;
                    default:
                        return $this->interpreter->getNotFoundResourceResponse($request);
                }
            } catch (GearIllegalArgumentException $e) {
                return $this->interpreter->getNotFoundResourceResponse($request);
            } catch (GearRunTimeException $e) {
                return $this->interpreter->getNetErrorResponse($request, $e->getMessage());
            } catch (Error $e) {
                if (Env::isDev()) {
                    return $this->interpreter->getNetErrorResponse($request, $e->getMessage());
                } else {
                    return $this->interpreter->getNetErrorResponse($request, "System Error!");
                }
            }
        });
        $this->socket->setKeepAlive();
    }

    protected function setPropertyCustom() {

    }

}
