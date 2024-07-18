<?php
class WebFilter extends Anno
{
    protected $className;
    protected $order = 0;

    /**
     * 指定注解可以放置的位置（默认: 所有）@see AnnoElementType
     */
    public static function constTarget()
    {
        return AnnoElementType::TYPE_CLASS;
    }

    /**
     * 指定注解的执行模式 @see AnnoPolicyEnum
     */
    public static function constPolicy()
    {
        return AnnoPolicyEnum::POLICY_RUNTIME;
    }

    /**
     * 指定注解的value设置规则 @see AnnoValueTypeEnum
     */
    public static function constStruct()
    {
        return AnnoValueTypeEnum::TYPE_RELATION;
    }
    /**
     * 非必须，切面逻辑类名，触发此注解时，执行的逻辑
     * @example {@see DiAspect}
     */
    public static function constAspect()
    {
        return WebFilterAspect::class;
    }

    public function getClassName() {
        return $this->className;
    }
}
