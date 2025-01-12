<?php

class GetMapping extends RequestMapping implements AnnoationCombination {
    /**
     * @var array 路由参数匹配正则
     */
    private $argMatcher;

    /**
     * @var string 路径
     */
    public function getPath() {
        return $this->value;
    }

    public function combine($values)
    {
        parent::combine($values);
        return;
        preg_match_all("/(?<path>[\/a-zA-Z0-9]+)(?<args>[#{a-zA-z}]+)/", $values, $matched);
        $newPath = $values;
        $argMatcher = $values;
        foreach ($matched['args'] as $arg) {
            $newPath = str_replace($arg, EzRouter::URL_WILDCARD, $newPath);
        }

        foreach ($matched['args'] as $arg) {
            $arg = trim($arg, "{}");
            $argMatcher = str_replace($arg, "(?<$arg>[{a-zA-z0-9}]+)", $argMatcher);
        }
        $this->path = $newPath;
        $this->argMatcher = $argMatcher;
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
