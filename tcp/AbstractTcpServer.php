<?php
abstract class AbstractTcpServer
{
    protected $ip;
    protected $port;

    /**
     * @var EzTcpServer $socket tcp服务器
     */
    protected $socket;

    /**
     * @var Interpreter $interpreter 协议解释器
     */
    protected $interpreter;

    public function __construct(string $ip = "", int $port = -1) {
        if (empty($ip)) {
            $ip = Config::get('application.server.ip');
            if (empty($ip)) {
                $ip = "127.0.0.1";
            }
        }
        if (-1 === $port) {
            $port = Config::get('application.server.port');
            if (empty($port)) {
                $port = 8080;
            }
        }
        $this->ip = $ip;
        $this->port = $port;
        Config::set(['application.server.ip'=>$ip, 'application.server.port'=>$port]);
        $this->setInterpreterInstance();
        Config::setOne('application.server.schema', $this->interpreter->getSchema());
        $this->setTcpServerInstance();
        $this->setPropertyCustom();
    }

    /**
     * 注入协议解释器
     * @return void
     */
    abstract protected function setInterpreterInstance();

    /**
     * 注入TcpServer
     * @return void
     */
    abstract protected function setTcpServerInstance();

    /**
     * 为自定义属性赋值
     * @return void
     */
    abstract protected function setPropertyCustom();

    /**
     * 启动服务
     */
    public function start() {
        $this->socket->init();
        $this->socket->start();
    }
}
