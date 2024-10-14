<?php
class EzHttp extends AbstractTcpServer
{
    protected $_root;
    protected $staticCache = [];

    protected function setPropertyCustom() {
        $this->_root = "./";
    }

    protected function initInterpreter():Interpreter {
        $interpreter = new HttpInterpreter();
        return $interpreter;
    }

    protected function createRequest(EzConnection $connection): IRequest {
        /**
         * @var Request $request
         */
        $request = $this->interpreter->decode($connection->getBuffer());
        DBC::assertLessThan(Config::get("application.server.http_server_request_limit", 1024 * 1024 * 2),
            $request->getContentLen(),
            "[HTTP] Request body is too large!", 0, GearShutDownException::class);
        return $request;
    }

    protected function appendRequest(EzConnection $connection, IRequest $request):IRequest {
        if ($request->isInit()) {
            return $request;
        }
        /**
         * @var $request Request
         */
        $requestSource = $request->getRequestSource();
        $requestSource->bodyContent .= $connection->getBuffer();
        $request->setContentLenActual(strlen($requestSource->bodyContent));
        $request->setIsInit($requestSource->contentLengthActual === $requestSource->contentLength);
        if ($request->isInit()) {
            $bodyArr = $this->interpreter->buildHttpRequestBody($requestSource);
            $this->interpreter->buildRequestArgs($bodyArr, null, $request);
        }
        return $request;
    }

    protected function buildResponse(EzConnection $connection, IRequest $request):IResponse{
        try {
            $path = $request->getPath();
            if(empty($path) || "/" == $path){
                $content = "<h1>It Works! ENV:".ENV::get()."</h1>";
                return (new Response(HttpStatus::OK(), $content));
            }
            if(($httpStatus = $request->check()) instanceof HttpStatus){
                return (new Response($httpStatus));
            }
            $judged = $this->judgePath($path);
            if(!$judged){
                if(empty($this->_root)){
                    return (new Response(HttpStatus::NOT_FOUND()));
                }
                if (empty(Config::get("application.static_path"))) {
                    Logger::console("[EzHttp] the static path is unset.");
                }
                $fullPath = Config::get("application.static_path", "").DIRECTORY_SEPARATOR.$path;
                if(empty($path) || !is_file($fullPath)) {
                    return (new Response(HttpStatus::NOT_FOUND()));
                }
                if(!isset($this->staticCache[$path])) {
                    $this->staticCache[$path] = file_get_contents($fullPath);

                    if (count($this->staticCache) > 100) {
                        $this->staticCache = array_slice($this->staticCache, 0, 50);
                    }
                }
                return new Response(HttpStatus::OK(), $this->staticCache[$path], $this->getMime($path));
            }else{
                return $this->getDynamicResponse($request);
            }
        } catch (Exception $exception) {
            $message = $exception->getMessage();
            if (!Env::isProd()) {
                $message .= $exception->getTraceAsString();
            }
            Logger::error("[Http] getResponse Exception! Code:{}, Error:{}", $exception->getCode(), $message);
            return new Response(HttpStatus::INTERNAL_SERVER_ERROR());
        } catch (Error $error) {
            $message = $error->getMessage();
            if (!Env::isProd()) {
                $message .= $error->getTraceAsString();
            }
            Logger::error("[Http] getResponse Fail! Code:{}, Error:{}", $error->getCode(), $message);
            return new Response(HttpStatus::INTERNAL_SERVER_ERROR());
        }

    }

    /**
     * 获取资源类型
     * @param string $path
     * @return mixed
     */
    private function getMime($path){
        $type = explode(".",$path);
        return HttpMimeType::MIME_TYPE_LIST[end($type)] ?? HttpMimeType::MIME_HTML;
    }

    private function judgePath($path):bool {
        return (BeanFinder::get()->fetch(EzRouter::class))->judgePath($path);
    }

    private function getDynamicResponse(IRequest $request):IResponse{
        try {
            /**
             * @var Request $request
             * @var EzRouter $router
             * @var IRouteMapping $mapping
             */
            $router = BeanFinder::get()->fetch(EzRouter::class);
            $mapping = $router->getMapping($request->getPath());
            $request->setRequestSource(null);
            $response = $mapping->disPatch($request);
            if ($response instanceof IResponse) {
                return $response;
            } elseif ($response instanceof EzRpcResponse) {
                $response = $response->toJson();
                $contentType = HttpMimeType::MIME_JSON;
            } elseif (is_array($response) || is_object($response)) {
                $response = EzCodecUtils::encodeJson($response);
                $contentType = HttpMimeType::MIME_JSON;
            } else {
                $contentType = HttpMimeType::MIME_HTML;
            }
            return new Response(HttpStatus::OK(), $response, $contentType);
        } catch (GearNotFoundResourceException $exception) {
            Logger::warn("[Http] {}, {} getDynamicResponse NotFound Exception!", $exception->getCode(), trim($exception->getMessage()));
            return $this->getNotFoundResourceResponse($request);
        } catch (GearRunTimeException $e) {
            Logger::error($e->getMessage().$e->getFile().":".$e->getLine());
            $premix = Env::isProd() ? "" : "[".get_class($e)."]";
            $msg = Env::isProd() ? "NetError" : $e->getMessage();
            if (!Env::isProd()) {
                $msg .= $e->getTraceAsString();
            }
            return $this->getNetErrorResponse($request, $premix.$msg);
        }catch (Exception $e){
            Logger::error($e->getMessage());
            $premix = Env::isProd() ? "" : "[".get_class($e)."]";
            $msg = Env::isProd() ? "NetError" : $e->getMessage();
            if (!Env::isProd()) {
                $msg .= $e->getTraceAsString();
            }
            return $this->getNetErrorResponse($request, $premix.$msg);
        }
    }

    public function start() {
        Logger::info("[HTTP]Start HTTP Server...");
        try{
            parent::start();
        } catch (Exception $e) {
            Logger::error("[HTTP] Server Closed! Cause By {}, At{}({})",
                $e->getMessage(), $e->getFile(), $e->getLine());
        } catch (Error $t) {
            Logger::error("[HTTP] Server Closed! Cause By {}, At{}({})",
                $t->getMessage(), $t->getFile(), $t->getLine(), $t);
        }
    }

    protected function getNotFoundResourceResponse(IRequest $request): IResponse
    {
        return new Response(HttpStatus::NOT_FOUND());
    }

    protected function getNetErrorResponse(IRequest $request, string $errorMessage = ""): IResponse
    {
        return new Response(HttpStatus::INTERNAL_SERVER_ERROR(), $errorMessage);
    }
}
