<?php

class EzRequestBody extends RuntimeAnnotation
{
    private $paramType;
    private $paramName;

    public function combine($values) {
        if (1 == count($values)) {
            $this->paramName = $values[0];
        } else {
            $this->paramType = $values[0];
            $this->paramName = $values[1];
        }
    }

    public function getParamName() {
        return $this->paramName;
    }

    /**
     * @return Clazz|null
     */
    public function getParamClass() {
        return Clazz::get($this->paramType);
    }

    public function constTarget()
    {
        return AnnoElementType::TYPE_METHOD;
    }

    public function constStruct()
    {
        return AnnoValueTypeEnum::TYPE_LIST;
    }

}
