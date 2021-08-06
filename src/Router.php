<?php

namespace Dynart\Minicore;

class Router {
    
    const CONFIG_INDEX = 'router.index';
    const CONFIG_BASE_URL = 'router.base_url';
    const CONFIG_USE_REWRITE = 'router.use_rewrite';
    const CONFIG_PARAMETER = 'router.parameter';

    /** @var Framework */
    protected $framework;

    /** @var Config */
    protected $config;

    /** @var RouteAliases */
    protected $aliases;

    /** @var Translation */
    protected $translation;

    /** @var RouteAliases */
    protected $routeAliases;

    protected $routes = [];
    protected $path;

    public function __construct() {
        $this->framework = Framework::instance();
        $this->config = $this->framework->get('config');
        $this->aliases = $this->framework->get('routeAliases');
        $this->translation = $this->framework->get('translation');
        $this->routeAliases = $this->framework->get('routeAliases');
        
        $request = $this->framework->get('request');
        $this->path = $request->get($this->getParameter());
        if ($this->routeAliases->hasAlias($this->path)) {
            $this->path = $this->routeAliases->getPath($this->path);
        }        
    }

    public function add($data) {
        foreach ($data as $d) {
            $this->addRoute($d[0], $d[1], $d[2], isset($d[3]) ? $d[3] : ['GET']);
        }
    }

    /**
     * @param string $path
     * @param string $method
     * @return Route
     */
    public function matchRoute($path, $method) {
        if ($this->aliases->hasAlias($path)) {
            $path = $this->aliases->getPath($path);
        }
        foreach ($this->routes as $route) {
            if ($route->match($path, $method)) {
                return $route;
            }
        }
        return null;
    }

    public function getParameter() {
        return $this->config->get(self::CONFIG_PARAMETER);
    }
    
    public function usingRewrite() {
        return $this->config->get(self::CONFIG_USE_REWRITE);
    }
    
    public function getBaseUrl() {
        return $this->config->get(self::CONFIG_BASE_URL);
    }
    
    public function getIndex() {
        return $this->config->get(self::CONFIG_INDEX);
    } 
        
    public function getUrl($path=null, $params=[], $amp='&amp;', $locale=null) {
        $paramsSeparator = '';
        $paramsString = '';
        $routeParam = $this->getParameter();
        if ($params) {
            // remove route param if exists
            if (isset($params[$routeParam])) {
                unset($params[$routeParam]);
            }
            $paramsString = http_build_query($params, '', $amp);
            $paramsSeparator = $this->usingRewrite() ? '?' : $amp;
        }

        $pathWithLocale = $this->getPathWithLocale($path, $locale);
        $prefix = $this->getPrefix($pathWithLocale);
        $pathAlias = $this->getPathAlias($pathWithLocale);
        $result = $prefix.$pathAlias.$paramsSeparator.$paramsString;
        return $result;
    }

    protected function getPathWithLocale($path, $locale) {
        $result = $path;
        if ($this->translation->hasMultiLocales() && $path !== null) {
            $path = str_replace('{locale}', $locale, $path);
        }
        return $result;
    }    

    protected function addRoute($path, $controllerClass, $controllerMethod, $httpMethods) {
        $result = $this->framework->create([
            '\Dynart\Minicore\Route',
            $path, $controllerClass, $controllerMethod, $httpMethods
        ]);
        $this->routes[$path] = $result;
        return $result;
    }

    protected function getPrefix($path) {
        $result = $this->getBaseUrl();
        if (!$this->usingRewrite() && $path !== null) {
            $result .= $this->getIndex();
            if ($path) {
                $result .= '?'.$this->getParameter().'=';
            }
        }
        return $result;
    }
    
    protected function getPathAlias($path) {
        $result = $path;
        if ($this->aliases->hasPath($path)) {
            $result = $this->aliases->getAlias($path);
        }
        return $result;        
    }

    public function getPath() {
        return $this->path;
    }    

}
