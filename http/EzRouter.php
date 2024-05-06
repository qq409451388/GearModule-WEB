<?php
class EzRouter implements EzBean
{
    const URL_WILDCARD = "#";
    private static $ins;
    /**
     * @var array<string, IRouteMapping>
     */
    private $urlMap = [];

    /**
     * @var array 模糊匹配路由
     */
    private $urlMapFuzzy = [];

    public function setMapping($path, $class, $func, $httpMethod = null) {
        if (false === strpos($path, self::URL_WILDCARD)) {
            $path = strtolower($path);
            if (array_key_exists($path, $this->urlMap)) {
                Logger::warn("EzRouter Has Setted Path:".$path.", From Obj:".$class."::".$func);
            }
            $this->urlMap[$path] = new UrlMapping($path, $class, $func, $httpMethod);
            Logger::console("[EzRouter] Mapping Path [$httpMethod]".$path." To $func@$class");
        } else {
            $pathExplained = explode("/", $path);
            $endIndex = count($pathExplained) - 1;
            $tmpUrlMapFuzzy = &$this->urlMapFuzzy;
            foreach ($pathExplained as $k => $pathItem) {
                if ($k == $endIndex) {
                    $tmpUrlMapFuzzy[$pathItem] = new UrlMapping($path, $class, $func, $httpMethod);
                } else {
                    $tmpUrlMapFuzzy[$pathItem] = [];
                    $tmpUrlMapFuzzy = &$tmpUrlMapFuzzy[$pathItem];
                }
            }
        }

    }

    /**
     * @param $path
     * @return IRouteMapping
     * @throws Exception|GearNotFoundResourceException
     */
    public function getMapping($path):IRouteMapping {
        $path = strtolower($path);
        $mapping = $this->urlMap[$path]??null;
        DBC::assertNonNull($mapping, "NotFound Path -- [$path].", 4000, GearNotFoundResourceException::class);
        $pathExplained = explode("/", $path);
        $tmpUrlMapFuzzy = $this->urlMapFuzzy;
        foreach ($pathExplained as $pathItem) {
            if ($tmpUrlMapFuzzy instanceof IRouteMapping) {
                return $mapping;
            }
            if (isset($tmpUrlMapFuzzy[$pathItem])) {
                $tmpUrlMapFuzzy = $tmpUrlMapFuzzy[$pathItem];
            } elseif (isset($tmpUrlMapFuzzy[self::URL_WILDCARD])) {
                $tmpUrlMapFuzzy = $tmpUrlMapFuzzy[self::URL_WILDCARD];
            } else {
                return $mapping;
            }
        }
        return $tmpUrlMapFuzzy instanceof IRouteMapping ? $tmpUrlMapFuzzy : $mapping;
    }

    public function judgePath($path):bool {
        if (Env::useFuzzyRouter()) {
            return !is_null($this->getMapping($path));
        }
        $path = strtolower($path);
        return isset($this->urlMap[$path]);
    }
}
