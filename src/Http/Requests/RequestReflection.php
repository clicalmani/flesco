<?php
namespace Clicalmani\Flesco\Http\Requests;

use Clicalmani\Flesco\Support\Log;

class RequestReflection 
{
    private $reflect;

    public function __construct(string $controller, string $method)
    {
        $this->reflect = new \ReflectionMethod($controller, $method);
    }

    public function getParameters()
    {
        return  $this->reflect->getParameters(); 
    }

    public function getParamsTypes()
    {
        $ret = [];

        foreach ($this->reflect->getParameters() as $param) 
            $ret[$param->getName()] = $param->getType()?->getName();
        return $ret;
    }

    public function getParamTypeAt(int $position)
    {
        $types = $this->getParamsTypes();
        return array_shift( $types );
    }

    public function getParamsNames()
    {
        $names = [];

        foreach ($this->reflect->getParameters() as $param) {
            $names[] = $param->getName();
        }

        return $names;
    }
}
