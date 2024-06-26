<?php

/**
 * Tcp连接对象
 */
class EzConnection
{
    private $connectionId;

    private $clientSocket;

    /**
     * 请求报文体
     * @var string $buf
     */
    private $buf;

    public function __construcct() {
        $this->connectionId = SysUtils::generateThreadId();
    }

    /**
     * @return string
     */
    public function getBuffer()
    {
        return $this->buf;
    }

    /**
     * @param string $buf
     */
    public function setBuffer($buf): void
    {
        $this->buf = $buf;
    }

    /**
     * @return mixed
     */
    public function getClientSocket()
    {
        return $this->clientSocket;
    }

    public function getClientIp() {
        socket_getpeername($this->clientSocket, $ip, $port);
        return $ip;
    }

    /**
     * @param mixed $socket
     */
    public function setClientSocket($socket): void
    {
        $this->clientSocket = $socket;
    }

}
