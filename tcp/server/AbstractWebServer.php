<?php
abstract class AbstractWebServer
{
    protected $ip;
    protected $port;
    protected $schema;

    /**
     * @var Interpreter $interpreter 协议解释器
     */
    protected $interpreter;

    public function __construct(string $ip = "", int $port = 0) {
        if (empty($ip)) {
            $ip = Config::get('application.server.ip');
            if (empty($ip)) {
                Logger::warn("[Server] Unset server ip, use default.");
                $ip = Env::getIp() ?? "127.0.0.1";
            }
        }
        if (empty($port)) {
            $port = Config::get('application.server.port');
            if (empty($port)) {
                Logger::warn("[Server] Unset server port, use default.");
                $port = 8080;
            }
        }
        $this->ip = $ip;
        $this->port = $port;
        Config::set(['application.server.ip'=>$ip, 'application.server.port'=>$port]);
        $this->interpreter = $this->initInterpreter();
        $this->schema = $this->interpreter->getSchema();
        Config::setOne('application.server.schema', $this->interpreter->getSchema());
        $this->setPropertyCustom();
    }

    /**
     * 注入协议解释器
     * @return void
     */
    abstract protected function initInterpreter():Interpreter;

    /**
     * 根据interpreter将IResponse对象转码成响应字符流
     * @param IResponse $response
     * @return string
     */
    protected function encodeResponse(IResponse $response):string {
        return $this->interpreter->encode($response);
    }

    /**
     * 将请求报文转为IRequest接口实例对象
     * @param EzConnection $connection
     * @param IRequest|NULL $request
     * @return IRequest
     */
    protected function buildRequest(EzConnection $connection, $request): IRequest {
        if (is_null($request)) {
            return $this->createRequest($connection);
        } else {
            return $this->appendRequest($connection, $request);
        }
    }

    /**
     * 将请求通过interpreter解码成IRequest对象
     * @param EzConnection $connection
     * @return IRequest
     */
    protected function createRequest(EzConnection $connection): IRequest {
        return $this->interpreter->decode($connection->getBuffer());
    }

    /**
     * 将请求通过interpreter解码成IRequest对象，追加未读取完整的部分
     * @param EzConnection $connection
     * @return IRequest
     */
    protected function appendRequest(EzConnection $connection, IRequest $request): IRequest {
        if (!$request->isInit()) {
            $request->setIsInit(true);
        }
        return $request;
    }

    /**
     * 业务逻辑层
     * 将IRequest接口实例对象转换为IResponse接口实例对象
     * @param EzConnection $connection
     * @param IRequest $request
     * @return IResponse
     */
    abstract protected function buildResponse(EzConnection $connection, IRequest $request): IResponse;

    /**
     * 为自定义属性赋值
     * @return void
     */
    abstract protected function setPropertyCustom();

    /**
     * 启动服务
     */
    public abstract function start();

    /**
     * 关闭Server
     * @return void
     */
    public abstract function close();

    /**
     * 获取资源未找到响应
     * @param IRequest $request
     * @return IResponse
     */
    protected abstract function getNotFoundResourceResponse(IRequest $request):IResponse;

    /**
     * 获取网络错误响应
     * @param IRequest $request
     * @param string $errorMessage
     * @return IResponse
     */
    protected abstract function getNetErrorResponse(IRequest $request, string $errorMessage = ""):IResponse;

}
