<?php

abstract class NetWorkRequest implements IRequest
{
    protected $isInit;

    public function toArray(): array {
        return get_object_vars($this);
    }
}
