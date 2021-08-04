<?php

namespace Dynart\Minicore;

class Router {
    
    const CONFIG_INDEX = 'router.index';
    const CONFIG_BASE_URL = 'router.base_url';
    const CONFIG_USE_REWRITE = 'router.use_rewrite';
    const CONFIG_PARAMETER = 'router.parameter';

    /** @var Framework */
    private $framework;

    /** @var Config */
    private $config;

    /** @var RouteAliases */
    private $aliases;

    /** @var Translation */
    private $translation;

    private $routes = [];

    public function __construct() {
        $this->framework = Framework::instance();
        $this->config = $this->framework->get('config');
        $this->aliases = $this->framework->get('routeAliases');
        $this->translation = $this->framework->get('translation');
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
    public function get($path, $method) {
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
    
    public function getCurrentUrlWithLocale($locale, $amp='&amp;') {
        $request = $this->framework->get('request');
        $params = $request->getAll();
        $routeParam = $this->getParameter();
        $path = '';
        if (isset($params[$routeParam])) {
            $path = $params[$routeParam];            
        }
        if ($this->translation->hasMultiLocales()) {
            // remove locale from current path
            $pos = strpos($path, '/');
            if ($pos) {
                $path = substr($path, $pos + 1, strlen($path) - $pos - 1);
            } else {
                $path = '';
            }
        }
        return $this->getUrl($path, $params, $amp, $locale);
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
    
    public function getPathWithLocale($path, $locale=null) {
        $result = $path;
        if ($this->translation->hasMultiLocales() && $path !== null) {
            $postfix = $path ? '/'.$path : '';
            $locale = $locale === null ? $this->translation->getLocale() : $locale;
            $result = $locale.$postfix;
        }
        return $result;
    }

    private function addRoute($path, $controllerClass, $controllerMethod, $httpMethods) {
        $result = $this->framework->create([
            '\Dynart\Minicore\Route',
            $path, $controllerClass, $controllerMethod, $httpMethods
        ]);
        $this->routes[$path] = $result;
        return $result;
    }

    private function getPrefix($path) {
        $result = $this->getBaseUrl();
        if (!$this->usingRewrite() && $path !== null) {
            $result .= $this->getIndex();
            if ($path) {
                $result .= '?'.$this->getParameter().'=';
            }
        }
        return $result;
    }
    
    private function getPathAlias($path) {
        $result = $path;
        if ($this->aliases->hasPath($path)) {
            $result = $this->aliases->getAlias($path);
        }
        return $result;        
    }

}
