<?php

namespace Simple\Routing;

use ReflectionClass;

class ControllerDispatcher
{

    private $params;

    /**
     * new instance of ControllerDispatcher
     *
     * @param [type] $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Get All parameters pass on controller action
     *
     * @param [type] $controller
     * @param [type] $action
     * @return void
     */
    public function dispatch(BaseController $controller, string $action)
    {
        $params = [];
        $refClass = new ReflectionClass($controller);
        $method = $refClass->getMethod($action);

        foreach ($method->getParameters() as $methodParameter) {
            $name = $methodParameter->getName();
            $type = $methodParameter->getType();

            if ($type !== null && $type->isBuiltin() === false) {
                $classRef = new ReflectionClass($type->getName());
                $params[$name] = $classRef->newInstance();
            } else {        
                $this->validateParameter($name, $controller, $action);
                $params[$name] = $this->params[$name];
            }
        }
        
        
        return $controller->$action(...$params);
    }

    /**
     * Validate parameter if exists on route variable
     *
     * @param string $name
     * @param BaseController $controller
     * @param string $action
     * @return void
     */
    public function validateParameter(string $name, BaseController $controller, string $action)
    {
        $class =  get_class($controller) .  "::$action";
        $style="";
        $style_closing= "";
        
        if(ERROR_HANDLER=="simply") {
            $class = "<span style='color:#ffbf6b'>" . get_class($controller) . "</span>" . "<span style='color:cyan'>::$action</span>";
            $style="<span style='color:cyan'>";
            $style_closing= "</span>";
        } 
        
        if (!isset($this->params[$name])){
            throw new \RuntimeException("Invalid route variable $style $$name $style_closing pass on $class. Please check your route");
        }
    }
}