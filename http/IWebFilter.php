<?php
interface IWebFilter extends EzBean
{
    public function filter(Request $request);
}
