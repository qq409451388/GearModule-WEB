<?php

class WebFilter extends BuildAnnotation
{
    protected $className;
    protected $order = 0;

    /**
     * 指定注解可以放置的位置（默认: 所有）@see AnnoElementType
     */
    public function constTarget()
    {
        return AnnoElementType::TYPE_CLASS;
    }

    /**
     * 指定注解的value设置规则 @see AnnoValueTypeEnum
     */
    public function constStruct()
    {
        return AnnoValueTypeEnum::TYPE_RELATION;
    }
    /**
     * 非必须，切面逻辑类名，触发此注解时，执行的逻辑
     * @example {@see DiAspect}
     */
    public function constAspect()
    {
        return WebFilterAspect::class;
    }

    public function getClassName() {
        return $this->className;
    }
}
