<?php
abstract class BaseEzHttp extends AbstractTcpServer
{
    protected $_root;
    protected $staticCache = [];

    protected function setPropertyCustom() {
        $this->_root = "./";
    }

    protected function setInterpreterInstance() {
        $this->interpreter = new HttpInterpreter();
    }

    protected function buildRequest($buf):IRequest{
        /**
         * @var Request $request
         */
        $request = $this->interpreter->decode($buf);
        return $request;
    }

    protected function getResponse(IRequest $request):IResponse{
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
        var_dump($path);
    }

    private function getDynamicResponse(IRequest $request):IResponse{
        try {
            return $this->interpreter->getDynamicResponse($request);
        }catch (GearRunTimeException $e) {
            Logger::error($e->getMessage().$e->getFile().":".$e->getLine());
            $premix = Env::isProd() ? "" : "[".get_class($e)."]";
            $msg = Env::isProd() ? "NetError" : $e->getMessage();
            if (!Env::isProd()) {
                $msg .= $e->getTraceAsString();
            }
            return $this->interpreter->getNetErrorResponse($request, $premix.$msg);
        }catch (Exception $e){
            Logger::error($e->getMessage());
            $premix = Env::isProd() ? "" : "[".get_class($e)."]";
            $msg = Env::isProd() ? "NetError" : $e->getMessage();
            if (!Env::isProd()) {
                $msg .= $e->getTraceAsString();
            }
            return $this->interpreter->getNetErrorResponse($request, $premix.$msg);
        }
    }

    protected function appendRequest(IRequest $request, string $buf) {
        if ($request->isInit()) {
            return;
        }
        /**
         * @var $request Request
         */
        $requestSource = $request->getRequestSource();
        $requestSource->bodyContent .= $buf;
        $request->setContentLenActual(strlen($requestSource->bodyContent));
        $request->setIsInit($requestSource->contentLengthActual === $requestSource->contentLength);
        if ($request->isInit()) {
            $bodyArr = $this->interpreter->buildHttpRequestBody($requestSource);
            $this->interpreter->buildRequestArgs($bodyArr, [], $request);
        }
    }
}
