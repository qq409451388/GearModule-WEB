<?php

class WebFilterIgnore extends RuntimeAnnotation
{

    /**
     * 指定注解可以放置的位置（默认: 所有）@see AnnoElementType
     */
    public function constTarget()
    {
        return AnnoElementType::TYPE_METHOD;
    }

    /**
     * 指定注解的value设置规则 @see AnnoValueTypeEnum
     */
    public function constStruct()
    {
        return AnnoValueTypeEnum::TYPE_LITE;
    }
}
