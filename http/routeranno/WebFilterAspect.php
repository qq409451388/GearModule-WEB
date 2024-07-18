<?php
class WebFilterAspect extends Aspect implements RunTimeAspect
{
    public function before(RunTimeProcessPoint $rpp): void
    {
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
            $rpp->tampering(EzRpcResponse::error(403, "No Auth."));
        }
    }

    public function after(RunTimeProcessPoint $rpp): void
    {
        var_dump("after");
    }
}
