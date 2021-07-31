<?php

namespace Dynart\Minicore;

class Framework
{
    protected static $instance = null;
    protected $declarations = [];
    protected $singletons = [];

    /** 
     * @throws FrameworkException
     */
    public static function setInstance(Framework $instance) {
        if (self::$instance) {
            throw new FrameworkException("Framework was initialized before.");
        }
        self::$instance = $instance;
    }

    /** 
     * @return Framework
     * @throws FrameworkException
     */
    public static function instance() {
        if (!self::$instance) {
            throw new FrameworkException("Framework wasn't initialized.");
        }
        return self::$instance;
    }

    public function add(array $declarations) {
        $this->declarations = array_merge($this->declarations, $declarations);
    }

    /**
     * @return string|array
     * @throws FrameworkException
     */
    public function getDeclaration(string $name) {
        if (!isset($this->declarations[$name])) {
            throw new FrameworkException("'$name' doesn't have a declaration.");
        }
        return $this->declarations[$name];
    }

    /**
     * @return mixed
     * @throws FrameworkException
     */
    public function get(string $name) {
        if (isset($this->singletons[$name])) {
            return $this->singletons[$name];
        }
        $declaration = $this->getDeclaration($name);
        $result = $this->create($declaration);
        $this->singletons[$name] = $result;
        return $result;
    }

    /**
     * @return mixed
     * @throws FrameworkException
     */
    public function create($declaration) {
        if (is_array($declaration)) {
            $class = array_shift($declaration);
            $args = $declaration;
        } else if (is_string($declaration)) {
            $class = $declaration;
            $args = [];
        } else {
            throw new FrameworkException("Declaration is not a string or an array.");            
        }
        try {
            $reflect = new \ReflectionClass($class);
            return $reflect->newInstanceArgs($args);
        }
        catch (\ReflectionException $e)
        {
            throw new FrameworkException("Couldn't create instance of ".$class.", arguments were: ".json_encode($args));
        }        
    }

}