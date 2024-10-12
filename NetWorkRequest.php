<?php

abstract class NetWorkRequest extends EzObject implements IRequest
{
    protected $isInit;

    public function toArray(): array {
        return get_object_vars($this);
    }
}
