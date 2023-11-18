<?php

namespace Simple\Routing;

use ReflectionClass;
use Simple\Request;

class ControllerDispatcher
{

    private array $params;

    /**
     * new instance of ControllerDispatcher
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Get All parameters pass on controller action
     *
     * @param BaseController $controller
     * @param string $action
     * @return void
     * @throws \ReflectionException
     */
    public function dispatch(BaseController $controller, string $action)
    {
        $params = [];
        $refClass = new ReflectionClass($controller);
        $method = $refClass->getMethod($action);
        $isRequestClassCalled = false;
        foreach ($method->getParameters() as $methodParameter) {
            $name = $methodParameter->getName();
            $type = $methodParameter->getType();

            if ($type !== null && $type->isBuiltin() === false) {

                $classRef = new ReflectionClass($type->getName());
                if($type->getName()==='Simple\Request') {
                    $requestClass = $classRef->newInstance($_GET,
                        $_POST,
                        [],
                        $_COOKIE,
                        $_FILES,
                        $_SERVER);
                    $requestClass->bootstrap();
                    $params[$name] = $requestClass;
                    $isRequestClassCalled=true;
                } else {
                    $params[$name] = $classRef->newInstance();
                }
            } else {
                $arg_valid = $this->validateParameter($name);
                if($arg_valid) {
                    $params[$name] = $this->params[$name];
                } else {
                    $params[$name] = null;
                }
            }
        }
        if(!$isRequestClassCalled) {
            (new Request($_GET,
                $_POST,
                [],
                $_COOKIE,
                $_FILES,
                $_SERVER))->bootstrap();
        }

        return $controller->$action(...$params);
    }

    /**
     * Validate parameter if exists on route variable
     *
     * @param string $name
     * @return bool
     */
    public function validateParameter(string $name): bool
    {
        if (!isset($this->params[$name])){
            return false;
        }
        return true;
    }
}
