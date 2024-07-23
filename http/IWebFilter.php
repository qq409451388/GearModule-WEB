<?php
interface IWebFilter extends EzBean
{
    public function filter(Request $request);
    public function ifFail(Request $request):Response;
}
