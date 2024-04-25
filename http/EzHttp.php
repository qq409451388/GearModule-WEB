<?php
class EzHttp extends BaseEzHttp
{

    public static function create(string $ip, int $port) {
        (new EzHttp($ip, $port))->start();
    }

    protected function setTcpServerInstance() {
        $this->socket = new EzTcpServer($this->ip, $this->port, $this->interpreter->getSchema());
        $this->socket->setRequestHandler(function (EzConnection $connection, $request = null):IRequest {
            $buf = $connection->getBuffer();
            /**
             * @var Request $request
             */
            if (is_null($request)) {
                $request = $this->buildRequest($buf);
                $request->setConnection($connection);
                DBC::assertLessThan(Config::get("application.server.http_server_request_limit", 1024 * 1024 * 2),
                    $request->getContentLen(),
                    "[HTTP] Request body is too large!", 0, GearShutDownException::class);
            } else {
                $request->setConnection($connection);
                $this->appendRequest($request, $buf);
            }
            return $request;
        });
        $this->socket->setResponseHandler(function (EzConnection $connection, IRequest $request):IResponse {
            /**
             * @var Request $request
             */
            if ($request->getContentLenActual() != $request->getContentLen()) {
                return new Response(HttpStatus::EXPECTATION_FAIL(), "body is too large!");
            }
            return $this->getResponse($request);
        });
        $this->socket->setKeepAlive();
    }

    public function start() {
        Logger::console("[HTTP]Start HTTP Server...");
        try{
            $this->socket->init();
            $this->socket->start();
        } catch (Exception $e) {
            Logger::error("[HTTP] Server Closed! Cause By {}, At{}({})",
                $e->getMessage(), $e->getFile(), $e->getLine());
        } catch (Error $t) {
            Logger::error("[HTTP] Server Closed! Cause By {}, At{}({})",
                $t->getMessage(), $t->getFile(), $t->getLine(), $t);
        }
    }

}
