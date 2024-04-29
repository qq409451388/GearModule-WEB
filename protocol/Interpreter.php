<?php
interface Interpreter
{
    /**
     * 获取协议名
     * @see SchemaConst
     * @return string
     */
    public function getSchema():string;

    /**
     * 解析response对象为tcp响应
     * @param IResponse $response
     * @return string
     */
    public function encode(IResponse $response):string;

    /**
     * 解析tcp请求为request对象
     * @param string $content
     * @return IRequest
     */
    public function decode(string $content):IRequest;

}
