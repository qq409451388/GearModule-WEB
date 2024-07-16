<?php
class BaseController implements EzBean
{
    public function __get($obj){
        DBC::assertTrue(BeanFinder::get()->has($obj), '['.__CLASS__.'] class '.$obj.' is not exists!');
        return BeanFinder::get()->pull($obj);
    }

    protected function show($response, $path) {
        extract($response);
        $template = Application::getContext()->getAppPath().DIRECTORY_SEPARATOR.$path;
        //$template = strtolower();
        ob_start();
        include($template);
        $res = ob_get_contents();
        ob_end_clean();
        return $this->response($res);
    }

    /**
     * @param $contentType HttpMimeType
     * @return IResponse
     */
    protected function response(string $content, $contentType = HttpMimeType::MIME_HTML):IResponse{
        return new Response(HttpStatus::OK(), $content, $contentType);
    }

    protected function response404() {
        return new Response(HttpStatus::NOT_FOUND());
    }

    protected function response400() {
        return new Response(HttpStatus::BAD_REQUEST());
    }

    protected function response500() {
        return new Response(HttpStatus::INTERNAL_SERVER_ERROR());
    }
}
