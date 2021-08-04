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

    public static function run($appClass, $env, $configPath='config.ini.php') {
        $framework = new Framework();        
        self::setInstance($framework);        
        $framework->add(['app' => [$appClass, $env, $configPath]]);
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
            throw new FrameworkException("Declaration is not a string or an array.");            
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
            throw new FrameworkException("Couldn't create instance of ".$class.", arguments were: ".json_encode($args));
        }        
    }

    public function redirect($path, $params=[]) {
        if (substr($path, 0, 7) == 'http://' || substr($path, 0, 8) == 'https://') {
            $url = $path;
        } else {
            /** @var Router $router */
            $router = $this->get('router');
            $url = $router->getUrl($path, $params, '&');
        }
        header('Location: '.$url);
        $this->finish();
    }    

    public function error($code, $content='') {
        if (!$content) {
            /** @var Config $config */
            $config = $this->get('config');
            $path = $config->get('app.error_static_folder').$code.'.html';
            if (!file_exists($path)) {
                $content = "Couldn't find error page for ".$code;
            } else {
                $content = file_get_contents($path);
            }
        }
        http_response_code($code);
        $this->finish($content);
    }

    public function finish($content='') {
        die($content);
    }    

}