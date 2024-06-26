<?php
class RespRequest extends NetWorkRequest implements IRequest
{
    public $command;

    public $args;

    public $options;

    public $requestId;

    public function getPath(): string
    {
        return $this->command;
    }

    public function check()
    {
        // TODO: Implement check() method.
    }

    public function filter()
    {
        // TODO: Implement filter() method.
    }

    public function isEmpty(): bool
    {
        return empty($this->command);
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $id)
    {
        $this->requestId = $id;
    }

    public function isInit(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function setIsInit(bool $isInit)
    {
        $this->isInit = $isInit;
    }
}
