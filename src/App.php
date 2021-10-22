<?php

namespace Dynart\Minicore;

abstract class App {

    const CONFIG_PATH = 'app.path';
    const CONFIG_CORE_FOLDER = 'app.core_folder';
    const CONFIG_CACHE_FOLDER = 'app.cache_folder';
    const CONFIG_STATIC_URL = 'app.static_url';
    const CONFIG_STATIC_FOLDER = 'app.static_folder';
    const CONFIG_MEDIA_URL = 'app.media_url';
    const CONFIG_MEDIA_FOLDER = 'app.media_folder';
    const CONFIG_MODULES_FOLDER = 'app.modules_folder';
    const CONFIG_MODULES_URL = 'app.modules_url';

    protected $configPaths;

    /** @var Framework */
    protected $framework;

    /** @var Config */
    protected $config;

    /** @var Router */
    protected $router;
    
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

    protected $middlewares = [];

    public function __construct(array $configPaths) {
        $this->configPaths = $configPaths;
        $this->framework = Framework::instance();
        $this->framework->add([
            // instance name => declaration
            'config'         => 'Config',
            'logger'         => 'Logger',
            'database'       => ['Database\MariaDatabase', 'default'],
            'request'        => 'Request',
            'response'       => 'Response',
            'router'         => 'Router',
            'routeAliases'   => 'RouteAliases',
            'translation'    => 'Translation',
            'localeResolver' => 'LocaleResolver',
            'mailer'         => 'Mailer',
            'view'           => 'View',
            'helper'         => 'Helper',            
            'session'        => 'Session',
            'inputTypes'     => 'Form\InputTypes',
            'validatorTypes' => 'Form\ValidatorTypes',
        ],
            '\Dynart\Minicore'
        );
    }

    public function init() {

        $this->config = $this->framework->get('config');
        foreach ($this->configPaths as $path) {
            $this->config->load($path); // TODO: config cache?
        }

        $coreFolder = $this->getCoreFolder();

        $this->request = $this->framework->get('request');
        $this->response = $this->framework->get('response');
        
        $this->translation = $this->framework->get('translation');
        $this->translation->add('core', $coreFolder.'/translations');

        $this->router = $this->framework->get('router');

        $this->helper = $this->framework->get('helper');
        $this->helper->add(__FILE__, 'Helpers/view.php');
        
        $this->view = $this->framework->get('view');
        $this->view->addFolder(':core', $coreFolder.'/templates');
        $this->view->addFolder(':app', $coreFolder.'/templates');
        //$this->view->addFolder(':pager', $coreFolder.'templates');

        $this->addMiddleware('localeResolver');
    }

    public function addMiddleware($middlewareDeclaration) {
        $this->middlewares[] = $middlewareDeclaration;
    }
 
    public function run() {
        $route = $this->initCurrentRoute();
        $this->setUrlParametersInRequest($route);
        $this->runMiddlewares();
        $this->runRoute($route);
        $this->response->send();
    }

    public function initCurrentRoute() {
        $route = $this->router->matchRoute($this->router->getPath());
        if (!$route || !in_array($this->request->getMethod(), $route->getHttpMethods())) {
            $this->error(404);
        }
        $this->router->setCurrentRoute($route);
        return $route;
    }

    public function getPath() {
        return $this->config->get(self::CONFIG_PATH);
    }

    public function getCoreFolder() {
        $path = $this->config->get(self::CONFIG_CORE_FOLDER);
        return $this->getFullPath($path);
    }

    public function getCacheFolder() {
        $path = $this->config->get(self::CONFIG_CACHE_FOLDER);
        return $this->getFullPath($path);
    }

    public function getMediaPath(string $path='') {
        $path = $this->config->get(self::CONFIG_MEDIA_FOLDER).$path;
        return $this->getFullPath($path);
    }

    public function getMediaUrl(string $path='') {
        $url = $this->config->get(self::CONFIG_MEDIA_URL);
        return $this->getFullUrl($url).$path;
    }  
    
    public function getStaticUrl(string $path, bool $useTimestamp=true) {
        if ($this->isStartWithHttp($path)) {
            return $path;
        }
        if ($useTimestamp) {
            $folder = $this->config->get(self::CONFIG_STATIC_FOLDER);
            $filePath = $this->getFullPath($folder.$path);
            $path .= '?'.filemtime($filePath);
        }
        $url = $this->config->get(self::CONFIG_STATIC_URL);
        return $this->getFullUrl($url).$path;
    }

    public function redirect(string $path, array $params=[]) {
        if ($this->isStartWithHttp($path)) {
            if ($params) {
                $url = $path.'?'.http_build_query($params, '', '&');
            } else {
                $url = $path;
            }
        } else {
            $url = $this->router->getUrl($path, $params, '&');
        }
        header('Location: '.$url);
        $this->framework->finish();
    }    

    public function error($code, $content='') {
        if (!$content) {
            $path = $this->config->get('app.error_static_folder').'/'.$code.'.html';
            if (!file_exists($path)) {
                $content = "Couldn't find error page for ".$code;
            } else {
                $content = file_get_contents($path);
            }
        }
        http_response_code($code);
        $this->framework->finish($content);
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

    protected function isStartWithHttp(string $path) {
        return substr($path, 0, 7) == 'http://' || substr($path, 0, 8) == 'https://';
    }

    protected function getFullPath(string $path) {
        if (substr($path, 0, 1) == '~') {
            $path = $this->getPath().substr($path, 1);
        }
        return $path;
    }

    protected function getFullUrl(string $path) {
        if ($this->isStartWithHttp($path)) {
            return $path;
        }
        if (substr($path, 0, 1) == '~') {
            $path = $this->router->getBaseUrl().substr($path, 1);
        }
        return $path;
    }

}
