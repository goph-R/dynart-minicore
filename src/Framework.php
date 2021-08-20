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
            throw new FrameworkException("The Framework was initialized before.");
        }
        self::$instance = $instance;
    }

    public static function run($appClass, array $configPaths) {
        $framework = new Framework();        
        self::setInstance($framework);        
        $framework->add(['app' => [$appClass, $configPaths]]);
        $app = $framework->get('app');
        $app->init();
        $app->run();
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

    public function add(array $newDeclarations, string $namespace='') {
        $declarations = $newDeclarations;        
        if ($namespace) {
            $this->addNamespace($namespace, $declarations);
        }
        $this->declarations = array_merge($this->declarations, $declarations);
    }

    protected function addNamespace(string $namespace, array &$declarations) {
        foreach ($declarations as $index => $decl) {
            if (is_array($decl)) {
                $declarations[$index][0] = $namespace.'\\'.$decl[0];
            } else if (is_string($decl)) {
                $declarations[$index] = $namespace.'\\'.$decl;
            }
        }
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
     * @return array
     * @throws FrameworkException
     */
    public function extractDeclaration($declaration)
    {
        if (is_string($declaration)) {
            $class = $declaration;
            $args = [];
        } else if (is_array($declaration)) {
            $class = array_shift($declaration);
            $args = $declaration;
        } else {
            throw new FrameworkException("Declaration is not a string or an array: ".json_encode($declaration));            
        }
        return [$class, $args];
    }

    /**
     * @return mixed
     * @throws FrameworkException
     */
    public function create($declaration) {
        list($class, $args) = $this->extractDeclaration($declaration);
        try {
            $reflect = new \ReflectionClass($class);
            return $reflect->newInstanceArgs($args);
        }
        catch (\ReflectionException $e)
        {
            throw $e;
            //throw new FrameworkException("Couldn't create instance of ".$class.", arguments were: ".json_encode($args));
        }        
    }

    public function finish($content='') {
        die($content);
    }    

}