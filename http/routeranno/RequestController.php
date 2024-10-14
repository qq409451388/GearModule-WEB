<?php

class RequestController extends BuildAnnotation implements AnnoationCombination
{
    private static $depend = [
        GetMapping::class,
        PostMapping::class,
        RequestMapping::class
    ];

    public function getPath() {
        return $this->value;
    }

    public function constTarget()
    {
        return AnnoElementType::TYPE_CLASS;
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
        return self::$depend;
    }
}
