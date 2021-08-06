<?php

namespace Dynart\Minicore;

class Route {

    protected $path;
    protected $httpMethods;
    protected $partCount;
    protected $parts;
    protected $parameterNameByIndex = [];
    protected $controllerClass;
    protected $controllerMethod;
    protected $parameters;

    public function __construct($path, $controllerClass, $controllerMethod, $httpMethods) {

        $this->path = $path;
        $this->controllerClass = $controllerClass;
        $this->controllerMethod = $controllerMethod;
        $this->httpMethods = $httpMethods;
        $this->parts = explode('/', $path);

        // store the part count
        $this->partCount = count($this->parts);

        // store parameter names by index
        foreach ($this->parts as $i => $part) {
            $found = [];
            preg_match('/{([a-z0-9_]+)}/', $part, $found);
            if (isset($found[1])) {
                $this->parameterNameByIndex[$i] = $found[1];
            }
        }
    }

    public function getPath() {
        return $this->path;
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function match($path, $httpMethod) {
        if (!in_array($httpMethod, $this->httpMethods)) {
            return false;
        }
        $currentParts = explode('/', $path);
        if ($this->partCount != count($currentParts)) {
            return false;
        }
        $this->parameters = [];
        foreach ($this->parts as $i => $part) {
            if (isset($this->parameterNameByIndex[$i])) {
                $name = $this->parameterNameByIndex[$i];
                $this->parameters[$name] = $currentParts[$i];

            } else if ($part != $currentParts[$i]) {
                return false;
            }
        }
        return true;
    }

    public function run() {
        $framework = Framework::instance();
        $controller = $framework->get($this->controllerClass);
        if (!method_exists($controller, $this->controllerMethod)) {
            throw new FrameworkException('The method '.get_class($controller).'::'.$this->controllerMethod." doesn't exist.");
        }
        call_user_func_array([$controller, $this->controllerMethod], array_values($this->parameters));
    }

}

