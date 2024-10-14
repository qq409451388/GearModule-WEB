<?php

class RequestMapping extends BuildAnnotation implements AnnoationCombination
{

    public function getPath() {
        return $this->value;
    }

    public function constTarget()
    {
        return AnnoElementType::TYPE_METHOD;
    }

    public function constStruct()
    {
        return AnnoValueTypeEnum::TYPE_NORMAL;
    }

    public function constAspect()
    {
        return RouterAspect::class;
    }

    public function constDepend()
    {
        return null;
    }
}
