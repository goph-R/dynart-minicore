<?php

namespace Dynart\Minicore;

class Router {
    
    const CONFIG_INDEX = 'router.index';
    const CONFIG_BASE_URL = 'router.base_url';
    const CONFIG_USE_REWRITE = 'router.use_rewrite';
    const CONFIG_PARAMETER = 'router.parameter';    
    const CONFIG_PATH_PREFIX = 'router.path_prefix';

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

    /** @var LocaleResolverMiddleware */
    protected $localeResolver;

    protected $routeClass = '\Dynart\Minicore\Route';
    protected $routes = [];
    protected $path;
    protected $currentRoute;
    protected $prefixVariables = [];

    public function __construct() {
        $this->framework = Framework::instance();
        $this->config = $this->framework->get('config');
        $this->aliases = $this->framework->get('routeAliases');
        $this->translation = $this->framework->get('translation');
        $this->routeAliases = $this->framework->get('routeAliases');
        $this->localeResolver = $this->framework->get('localeResolver');

        // set the path, if it's an alias, get the real path
        $request = $this->framework->get('request');
        $this->path = $request->get($this->getParameter(), '');
        if ($this->routeAliases->hasAlias($this->path)) {
            $this->path = $this->routeAliases->getPath($this->path);
        }        
    }

    public function addPrefixVariable(string $name, array $callable) {
        $this->prefixVariables['{'.$name.'}'] = $callable;
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
    public function matchRoute(string $path) {
        if (!$path) {
            $path = $this->getPathPrefix();
        }
        if ($this->aliases->hasAlias($path)) {
            $path = $this->aliases->getPath($path);
        }
        foreach ($this->routes as $route) {
            if ($route->match($path)) {
                return $route;
            }
        }
        return null;
    }

    public function setCurrentRoute(Route $route) {
        $this->currentRoute = $route;        
    }

    /**
     * @return Route
     */
    public function getCurrentRoute() {
        return $this->currentRoute;
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

    public function getPathPrefix() {
        return $this->config->get(self::CONFIG_PATH_PREFIX);
    }
        
    public function getUrl($path=null, $params=[], $amp='&amp;') {
        $paramsSeparator = '';
        $paramsString = '';        
        if ($params) {
            // remove route param if exists, we will add it in 'getPrefix'
            $routeParam = $this->getParameter();
            if (isset($params[$routeParam])) {
                unset($params[$routeParam]);
            }
            $paramsString = http_build_query($params, '', $amp);
            $paramsSeparator = ($this->usingRewrite() || !$path) && !$this->getPathPrefix() ? '?' : $amp;
        }
        $prefix = $this->getPrefix($path);
        $path = $this->getPathAlias($path);
        $result = $prefix.$path.$paramsSeparator.$paramsString;
        return $result;
    }

    protected function addRoute(string $path, string $controllerName, string $controllerMethod, array $httpMethods) {
        $path = $this->getPathPrefix().$path;
        $result = $this->framework->create([
            $this->routeClass,
            $path, $controllerName, $controllerMethod, $httpMethods
        ]);
        $this->routes[$path] = $result;
        return $result;
    }

    protected function getPrefix($path) {

        // set up the path prefix with variables first
        $routePrefix = $this->getPathPrefix();
        foreach ($this->prefixVariables as $name => $callable) {
            $value = call_user_func($callable);
            $routePrefix = str_replace($name, $value, $routePrefix);
        }
        
        // return with the prefix (index.dev.php?route=en/something)
        $result = $this->getBaseUrl();
        if (!$this->usingRewrite() && $path !== null) {
            $result .= $this->getIndex();
            if ($path || $routePrefix) {
                $result .= '?'.$this->getParameter().'=';
            }
        }
        $result .= $routePrefix;

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
