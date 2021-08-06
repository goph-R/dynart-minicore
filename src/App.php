<?php

namespace Dynart\Minicore;

abstract class App {

    const CONFIG_PATH = 'app.path';
    const CONFIG_CORE_FOLDER = 'app.core_folder';
    const CONFIG_CACHE_FOLDER = 'app.cache_folder';
    const CONFIG_STATIC_URL = 'app.static_url';
    const CONFIG_MEDIA_URL = 'app.media_url';
    const CONFIG_MEDIA_FOLDER = 'app.media_folder';
    const CONFIG_MODULES_FOLDER = 'app.modules_folder';
    const CONFIG_MODULES_URL = 'app.modules_url';

    protected $configPath;

    /** @var Framework */
    protected $framework;

    /** @var Logger */
    protected $logger;

    /** @var Router */
    protected $router;
    
    /** @var Config */
    protected $config;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var Translation */
    protected $translation;

    /** @var View */
    protected $view;
    
    /** @var Helper */
    protected $helper;

    /** @var Middleware[] */
    protected $middlewares = [];

    public function __construct($env='dev', $configPath='config.ini.php') {
        $this->configPath = $configPath;
        $this->framework = Framework::instance();
        $this->framework->add([
            'config'         => ['Config', $env],
            'logger'         => 'Logger',
            'database'       => ['Database', 'default'],
            'request'        => 'Request',
            'response'       => 'Response',
            'router'         => 'Router',
            'routeAliases'   => 'RouteAliases',
            'helper'         => 'Helper',
            'translation'    => 'Translation',
            'localeResolver' => 'LocaleResolver',
            'mailer'         => 'Mailer',
            'view'           => 'View',
            'userSession'    => 'UserSession', // TODO: JWT
        ],
            '\Dynart\Minicore'
        );
    }

    public function init() {

        $this->config = $this->framework->get('config');
        $this->config->load($this->configPath);

        $coreFolder = $this->getCoreFolder();

        $this->logger = $this->framework->get('logger');        
        $this->request = $this->framework->get('request');
        $this->response = $this->framework->get('response');
       
        $this->translation = $this->framework->get('translation');
        $this->translation->add('core', $coreFolder.'translations');

        $this->router = $this->framework->get('router');

        $this->helper = $this->framework->get('helper');
        $this->helper->add(__FILE__, 'Helpers/view.php');
        
        $this->view = $this->framework->get('view');
        $this->view->addFolder(':app', $coreFolder.'templates');
        $this->view->addFolder(':form', $coreFolder.'templates');
        //$this->view->addFolder(':pager', $coreFolder.'templates');

        $this->addMiddleware('localeResolver');
    }

    public function addMiddleware($middlewareDeclaration) {
        $this->middlewares[] = $middlewareDeclaration;
    }
 
    public function run() {
        $route = $this->router->matchRoute($this->router->getPath());
        if (!$route || !in_array($this->request->getMethod(), $route->getHttpMethods())) {
            $this->error(404);
        }
        $this->router->setCurrentRoute($route);
        $this->setUrlParametersInRequest($route);
        $this->runMiddlewares();
        $this->runRoute($route);
        $this->response->send();
    }

    protected function setUrlParametersInRequest(Route $route) {
        foreach ($route->getUrlParameters() as $name => $value) {
            $this->request->set($name, $value);
        }        
    }

    protected function runMiddlewares() {
        foreach ($this->middlewares as $middlewareDeclaration) {
            $middleware = $this->framework->get($middlewareDeclaration);
            $middleware->run();
        }        
    }

    protected function runRoute(Route $route) {
        $object = $this->framework->get($route->getControllerName());
        $method = $route->getControllerMethod();
        if (!method_exists($object, $method)) {
            throw new FrameworkException('The method '.get_class($object).'::'.$method." doesn't exist.");
        }
        $params = $route->getMethodParameters();
        call_user_func_array([$object, $method], $params);        
    }

    public function getCoreFolder() {
        return $this->config->get(self::CONFIG_CORE_FOLDER);
    }

    public function getCacheFolder() {
        return $this->config->get(self::CONFIG_CACHE_FOLDER);
    }

    public function getPath() {
        return $this->config->get(SELF::CONFIG_PATH);
    }

    public function getMediaPath(string $path='') {
        return $this->config->get(self::CONFIG_MEDIA_FOLDER).$path;
    }

    public function getMediaUrl(string $path='') {
        return $this->getFullUrl(self::CONFIG_MEDIA_URL, $path);
    }  
    
    public function getStaticUrl(string $path) {
        return $this->getFullUrl(self::CONFIG_STATIC_URL, $path);
    }

    protected function getFullUrl(string $configName, string $path) {
        if (substr($path, 0, 1) == '~') {
            $path = $this->router->getBaseUrl().substr($path, 2);
        }
        if (strpos($path, 'https://') === 0 || strpos($path, 'http://') === 0) {
            return $path;
        }
        $prefix = $this->config->get($configName);
        if (substr($prefix, 0, 1) == '~') {
            $prefix = $this->router->getBaseUrl().substr($prefix, 2);
        }
        return $prefix.$path;
    }

    public function redirect($path, $params=[]) {
        if (substr($path, 0, 7) == 'http://' || substr($path, 0, 8) == 'https://') {
            $url = $path;
        } else {
            $url = $this->router->getUrl($path, $params, '&');
        }
        header('Location: '.$url);
        $this->finish();
    }    

    public function error($code, $content='') {
        if (!$content) {
            $path = $this->config->get('app.error_static_folder').$code.'.html';
            if (!file_exists($path)) {
                $content = "Couldn't find error page for ".$code;
            } else {
                $content = file_get_contents($path);
            }
        }
        http_response_code($code);
        $this->finish($content);
    }    
    
}