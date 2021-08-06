<?php

namespace Dynart\Minicore;

class Route {

    protected $path;
    protected $httpMethods;
    protected $partCount;
    protected $parts;
    protected $urlParameterNameByIndex = [];
    protected $controllerName;
    protected $controllerMethod;
    protected $urlParameters;
    protected $methodParameters;

    public function __construct(string $path, string $controllerName, string $controllerMethod, array $httpMethods) {

        $this->path = $path;
        $this->controllerName = $controllerName;
        $this->controllerMethod = $controllerMethod;
        $this->httpMethods = $httpMethods;
        $this->parts = explode('/', $path);

        // store the part count
        $this->partCount = count($this->parts);

        // store URL parameter names by index
        foreach ($this->parts as $i => $part) {
            $found = [];
            preg_match('/{([a-z0-9_]+)}/', $part, $found);
            if (isset($found[1])) {
                $this->urlParameterNameByIndex[$i] = $found[1];
            }
        }
    }

    public function getPath() {
        return $this->path;
    }

    public function getUrlParameters() {
        return $this->urlParameters;
    }

    public function getMethodParameters() {
        return $this->methodParameters;
    }
    
    public function getControllerName() {
        return $this->controllerName;
    }

    public function getControllerMethod() {
        return $this->controllerMethod;
    }

    public function getHttpMethods() {
        return $this->httpMethods;
    }

    public function match($path) {
        $currentParts = explode('/', $path);
        if ($this->partCount != count($currentParts)) {
            return false;
        }
        $this->urlParameters = [];
        $this->methodParameters = [];
        foreach ($this->parts as $i => $part) {            
            if (isset($this->urlParameterNameByIndex[$i])) {
                // fetch an URL parameter
                $name = $this->urlParameterNameByIndex[$i];
                $this->urlParameters[$name] = $currentParts[$i];                
            } else if ($part == '?') {
                // fetch a method parameter
                $this->methodParameters[] = $currentParts[$i];
            } else if ($part != $currentParts[$i]) {
                // no matching, return false
                return false;
            }
        }
        return true;
    }

}

