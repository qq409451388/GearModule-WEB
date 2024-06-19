<?php
abstract class AbstractTcpServer extends AbstractWebServer
{
    private $responseBuilder;

    protected $keepAlive = false;
    /**
     * @var EzTcpServerConnection $serverConnection
     */
    protected $serverConnection;

    /**
     * @var int 连接超时时间（单位：s）
     */
    protected $timeOut = 3;

    /**
     * socket read长度
     */
    const SOCKET_READ_LENGTH = 1024000;

    /**
     * 保留字 MASTER alias
     */
    const MASTER = "EZTCP_MASTER";

    protected $isInit = false;

    protected $requestPool = [];

    public function __construct($ip, $port) {
        parent::__construct($ip, $port);
        $master = socket_create(AF_INET, SOCK_STREAM, 0);
        $this->serverConnection = new EzTcpServerConnection($master);
        //复用地址
        socket_set_option($this->getMaster(), SOL_SOCKET, SO_REUSEADDR, 1);
        @socket_bind($this->getMaster(), $this->ip, $this->port);
        $this->detection();
        @socket_listen($this->getMaster(), 511);
        $this->detection();
        //设置 SO_LINGER 套接字选项
        $linger = array('l_onoff' => 1, 'l_linger' => 0);
        socket_set_option($this->getMaster(), SOL_SOCKET, SO_LINGER, $linger);
        //接收超时
        socket_set_option($this->getMaster(),SOL_SOCKET,SO_RCVTIMEO,["sec"=>3, "usec"=>0]);
        //发送超时
        socket_set_option($this->getMaster(),SOL_SOCKET,SO_SNDTIMEO,["sec"=>3, "usec"=>0]);
        socket_set_nonblock($this->getMaster());
        $this->addConnectPool($this->getMaster(), self::MASTER);

        $this->isInit = true;
    }

    private function getMaster() {
        return $this->serverConnection->getMaster();
    }

    private function detection() {
        $errCode = socket_last_error($this->getMaster());
        DBC::assertEquals(0, $errCode, socket_strerror($errCode), $errCode, GearShutDownException::class);
    }


    /**
     * 当一个新的client进来后，将其加入连接池
     * @param $clientSocket
     * @param $alias
     * @return void
     */
    protected function addConnectPool($clientSocket, $alias) {
        if (is_null($clientSocket)) {
            return;
        }
        DBC::assertTrue(self::MASTER != $alias || $this->getMaster() == $clientSocket,
            "[EzWebSocketServer Exception] Cant Set Alias To ".self::MASTER);
        DBC::assertFalse($this->hasConnect($alias), "[EzWebSocketServer Exception] {$alias} Already Connected!");
        if (self::MASTER != $alias) {
            socket_set_nonblock($clientSocket);
            //Logger::console($clientSocket." CONNECTED!");
        }
        $this->serverConnection->clientInPool($clientSocket, $alias);
    }

    /**
     * 是否存在连接
     * @param $alias
     * @return bool
     */
    private function hasConnect($alias) {
        return $this->serverConnection->hasClient($alias);
    }

    /**
     * 监听master拿到新的client socket
     * @return socket
     */
    protected function newConnect() {
        //新连接加入
        $client = socket_accept($this->getMaster());
        if ($client < 0) {
            Logger::console("Client Connect Fail!");
            return null;
        }
        if ($this->serverConnection->countConnections() > $this->serverConnection->getMaxConnectNum()) {
            Logger::console("Over MaxConnectNum!");
            return null;
        }
        return $client;
    }

    /**
     * 客户端断联
     * @param $clientSocket
     * @return void
     */
    protected function disConnect($clientSocket) {
        if ($this->getMaster() == $clientSocket) {
            return;
        }
        $this->serverConnection->disconnect($clientSocket);
        //Logger::console(spl_object_id($clientSocket)." CLOSED!");
    }

    /**
     * 向socket写入
     * @param $socket
     * @param $content
     * @return void
     */
    protected function writeSocket($socket, $content) {
        $allowTryTime = 3;
        $tryTime = 0;
        try {
            do {
                $contentLen = strlen($content);
                $writeByte = socket_write($socket, $content, $contentLen);
                if (false === $writeByte) {
                    if ($tryTime > $allowTryTime) {
                        DBC::throwEx("[TcpServer] write fail! try over $tryTime times!", 0, GearUnsupportedOperationException::class);
                    }
                    $errCode = socket_last_error($socket);
                    if (35 == $errCode) {
                        usleep(10000000);
                        $tryTime++;
                        continue;
                    }
                    $errMsg = socket_strerror($errCode);
                    DBC::throwEx("[TcpServer] write fail! $errMsg", $errCode, GearUnsupportedOperationException::class);
                }
                if (0 == $contentLen || empty($content)) {
                    socket_write($socket, "\r\n");
                    break;
                }
                $content = substr($content, $writeByte);
            } while ($writeByte < $contentLen);
        } catch (Exception $e ) {
            if (Env::isDev()) {
                Logger::warn("[TcpServer] Exception!".PHP_EOL." {} {}", $e->getMessage().PHP_EOL, $e->getTraceAsString());
            } else {
                Logger::warn("[TcpServer] Exception!".PHP_EOL." {}", $e->getMessage().PHP_EOL);
            }
        }
    }

    public function start() {
        DBC::assertTrue($this->isInit, "[TcpServer] Must Run TcpServer::init() first!", 0, GearShutDownException::class);
        Logger::console("Start Server Success! ".$this->schema."://".$this->ip.":".$this->port);
        while (true) {
            $readSockets = $this->serverConnection->getConnectPool();
            $writeSockets = null;
            $except = null;
            $ready = @socket_select($readSockets, $writeSockets, $except, $this->timeOut);
            $startSucc = false !== $ready;
            //$this->periodicityCheck();
            DBC::assertTrue($startSucc, "[EzTcpServer] Srart Fail!".socket_strerror(socket_last_error()));
            foreach ($readSockets as $readSocket) {
                if ($this->getMaster() == $readSocket) {
                    $socket = $this->newConnect();
                    if (!is_null($socket)) {
                        //刚刚建立连接的socket对象没有别名
                        $this->addConnectPool($socket, spl_object_id($socket));
                    }
                } else {
                    $readLength = self::SOCKET_READ_LENGTH;
                    $lastRequest = $this->getLastRequest($readSocket);
                    $recv = @socket_recv($readSocket, $buffer, $readLength, 0);
                    if ($recv == 0) {
                        $this->disConnect($readSocket);
                        continue;
                    }
                    $connection = new EzConnection();
                    $connection->setBuffer($buffer);
                    $connection->setClientSocket($readSocket);
                    //接收并处理消息体
                    $request = $this->buildRequest($connection, $lastRequest);
                    $request->setRequestId(spl_object_id($readSocket));
                    $this->checkAndClearRequest($request);
                    if ($request->isInit()) {
                        $response = $this->buildResponse($connection, $request);
                        $content = $response->toString();
                        $this->writeSocket($readSocket, $content);
                        if (!$this->keepAlive) {
                            $this->disConnect($readSocket);
                        }
                    }
                }
            }
        }
    }

    private function getLastRequest($clientSocket) {
        return $this->requestPool[spl_object_id($clientSocket)]??null;
    }

    private function checkAndClearRequest(IRequest $request) {
        if ($request->isInit()) {
            unset($this->requestPool[$request->getRequestId()]);
        } else {
            $this->requestPool[$request->getRequestId()] = $request;
        }
    }



    public function close() {
        if (is_resource($this->getMaster())) {
            socket_close($this->getMaster());
        }
    }

    /**
     * 状态检查 stop the world
     * @return void
     */
    protected function periodicityCheck(){
        if(time() % 10000 != 0){
            return;
        }
        $connectionPool = $this->serverConnection->getConnectPool();
        foreach ($connectionPool as $alias => $connection) {
            if (self::MASTER == $alias) {
                continue;
            }
            if (!$this->checkClientAlive($connection)) {
                $this->disConnect($connection);
            }
        }
    }

    private function checkClientAlive($connection) {
        if (!is_resource($connection)) {
            Logger::console(spl_object_id($connection));
            return false;
        }
        return socket_read($connection, 0);
    }

    public function setKeepAlive() {
        $this->keepAlive = true;
    }

    public function setNoKeepAlive() {
        $this->keepAlive = false;
    }

    public function __destory() {
        $this->close();
    }

    protected function setPropertyCustom() {
    }

    protected function initInterpreter(): Interpreter
    {
        $interpreter = new BasicInterpreter();
        $interpreter->setRequestHandler(function ($buffer) {
            $msg = new TcpMessage();
            $msg->data = $buffer;
            return $msg;
        });
        $interpreter->setResponseHandler(function (IResponse $response) {
            return $response->toString();
        });
        return $interpreter;
    }


}
