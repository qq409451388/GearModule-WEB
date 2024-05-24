<?php
class EzTcp extends BaseTcpClient
{
    private $read = [];
    private $readCallback = [];

    public function init($ip, $port):BaseTcpClient{
        parent::init($ip, $port);
        $this->conn = @stream_socket_client("tcp://{$ip}:{$port}", $errno, $errstr);
        DBC::assertEquals(0, $errno, "[EzTcp] Exception Caused by $errstr", $errno);
        //$this->setNonBlock();
        $this->addMain();
        return $this;
    }

    private function addMain() {
        $this->read["MAIN"] = $this->conn;
        $this->readCallback['MAIN'] = function($read) {
            // 读取服务端发送的数据
            if(in_array($this->conn, $read)) {
                $data = fgets($this->conn);
                if($data === false){
                    return false;
                }else{
                    Logger::console($data);
                    return $data;
                }
            }
            return null;
        };
    }

    /**
     * 读入console输入的数据
     * @return void
     */
    public function addStdin(Closure $inputHandler = null) {
        $this->read['STDIN'] = STDIN;
        $this->readCallback['STDIN'] = function($read) use ($inputHandler) {
            if(in_array(STDIN, $read)) {
                $input = fgets(STDIN);
                $input = trim($input);
                if (empty($input)) {
                    return;
                }
                if (!is_null($inputHandler)) {
                    $input = $inputHandler($input);
                }
                fwrite($this->conn, $input);
            }
        };
    }

    public function send($msg){
        $t1 = microtime(true);
        fwrite($this->conn, $msg); // 发送消息
        $ret = "";
        $read = [$this->conn];
        $write = null;
        $exception = null;
        $maxWait = 10; // 最大等待时间（秒）
        $startTime = microtime(true);

        while (true) {
            // 计算已等待时间
            $elapsedTime = microtime(true) - $startTime;
            if ($elapsedTime >= $maxWait) {
                break; // 已等待超过最大等待时间，跳出循环
            }

            // 计算剩余等待时间
            $remainingTime = $maxWait - $elapsedTime;

            // 使用 stream_select 等待数据到达
            if (stream_select($read, $write, $exception, 0, $remainingTime) > 0) {
                $data = fgets($this->conn, 128);
                if ($data === false) {
                    break; // 读取结束，跳出循环
                }
                $ret .= $data;
            } else {
                break; // 没有数据可读，跳出循环
            }
        }

        $t2 = microtime(true);
        var_dump($t2 - $t1); // 输出时间差以调试性能
        return $ret;
    }

    public function listen() {
        $feof = false;
        $write = null;  // 输出流
        $exception = null; // 异常流
        while(!$feof) {
            $read = $this->read; // 读入流

            if(stream_select($read, $write, $exception, 0) > 0) {
                $mainResult = $this->readCallback['MAIN']($read);
                if (false === $mainResult) {
                    // 关闭过滤器以清理 strea_socket_client 上缓存的非持久句柄资源，避免浪费
                    $this->destory();
                    $feof = true;
                }

                $this->listenStdin($read);
            }
        }
    }

    private function listenStdin($read) {
        if (!isset($this->readCallback['STDIN'])) {
            return;
        }
        $this->readCallback["STDIN"]($read);
    }

    protected function destory(){
        if(null != $this->conn){
            fclose($this->conn);
        }
        $this->conn = null;
    }

    public function setNonBlock()
    {
        socket_set_blocking($this->conn, false);
    }
}
