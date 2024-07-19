<?php
class WebFilterAspect extends Aspect implements RunTimeAspect
{
    public function before(RunTimeProcessPoint $rpp): void
    {
        /**
         * @var AnnoationElement
         */
        $ignore = $rpp->getClassInstance()->getMethod($rpp->getFunctionName())->getAnnoation(Clazz::get(WebFilterIgnore::class));
        if (!is_null($ignore) && $ignore->annoName === WebFilterIgnore::class) {
            return;
        }
        $authClass = $this->getValue()->getClassName();
        $atClass = $this->getAtClass()->getName();
        DBC::assertTrue(BeanFinder::get()->has($authClass), "[WebFilter] the class $atClass has use a webfilter class $authClass, but not found!");

        /**
         * @var AuthCheck $checker
         */
        $checker = BeanFinder::get()->pull($authClass);
        $request = null;
        foreach ($rpp->getContextInstanceList() as $contextInstance) {
            if ($contextInstance instanceof Request) {
                $request = $contextInstance;
            }
        }
        if (is_null($request)) {
            return;
        }
        $checkRes = $checker->filter($request);
        if (!$checkRes) {
            //$rpp->setReturnValue(EzRpcResponse::error(403));
            $rpp->setIsSkip(true);
            $response = new Response(HttpStatus::FOUND_302());
            $response->setCustomHeader("Location", "http://".Config::get("application.domain")."/admin/login");
            $rpp->tampering($response);
        }
    }

    public function after(RunTimeProcessPoint $rpp): void
    {
    }
}
