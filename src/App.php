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

    /** @var Module[] */
    //protected $modules = [];

    /** @var Middleware[] */
    protected $middlewares = [];

    public function __construct($env='dev', $configPath='config.ini.php') {
        $this->configPath = $configPath;
        $this->framework = Framework::instance();
        $ns = '\Dynart\Minicore\\';
        $this->framework->add([
            'config'         => [$ns.'Config', $env],
            'logger'         => $ns.'Logger',
            'database'       => [$ns.'Database', 'default'],
            'request'        => $ns.'Request',
            'response'       => $ns.'Response',
            'router'         => $ns.'Router', // TODO
            'routeAliases'   => $ns.'RouteAliases',
            'view'           => $ns.'View', // TODO
            'helper'         => $ns.'Helper',
            'translation'    => $ns.'Translation',
            // TODO: 'mailer'        => 'Mailer',
            'userSession'    => $ns.'UserSession', // TODO
            'localeResolver' => $ns.'LocaleResolverMiddleware'
        ]);
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
        //$this->view->addFolder(':form', $coreFolder.'form/templates');
        //$this->view->addFolder(':pager', $coreFolder.'pager/templates');

        $this->addMiddleware($this->framework->get('localeResolver'));
    }

    public function addMiddleware(Middleware $middleware) {
        $this->middlewares[] = $middleware;
    }
 
    public function runMiddlewares() {
        foreach ($this->middlewares as $middleware) {
            $middleware->run();
        }        
    }

    public function run() {
        $route = $this->router->matchRoute($this->router->getPath(), $this->request->getMethod());
        if (!$route) {
            $this->framework->error(404);
        }
        foreach ($route->getParameters() as $name => $value) {
            $this->request->set($name, $value);
        }
        $this->runMiddlewares();
        $route->run();
        $this->response->send();
    }

    /*
    protected function initModules() {
        // TODO: dependency tree
        foreach ($this->modules as $module) {
            $module->init();
        }
    }    

    public function addModule($moduleClass) {
        $module = $this->framework->create($moduleClass);
        $this->modules[$module->getId()] = $module;
    }

    public function hasModule($moduleId) {
        return isset($this->modules[$moduleId]);
    }

    public function getModule($moduleId) {
        if (!$this->hasModule($moduleId)) {
            throw new RuntimeException("Can't get module: ".$moduleId);
        }
        return $this->modules[$moduleId];
    }

    public function getModulesFolder() {
        return $this->config->get(self::CONFIG_MODULES_FOLDER);
    }

    public function getModulesUrl() {
        return $this->config->get(self::CONFIG_MODULES_URL);
    }
    */

    public function getCoreFolder() {
        return $this->config->get(self::CONFIG_CORE_FOLDER);
    }

    public function getCacheFolder() {
        return $this->config->get(self::CONFIG_CACHE_FOLDER);
    }

    public function getPath() {
        return $this->config->get(SELF::CONFIG_PATH);
    }

    public function getMediaPath($path='') {
        return $this->config->get(self::CONFIG_MEDIA_FOLDER).$path;
    }

    public function getMediaUrl($path='') {
        return $this->getFullUrl(self::CONFIG_MEDIA_URL, $path);
    }  
    
    public function getStaticUrl($path) {
        return $this->getFullUrl(self::CONFIG_STATIC_URL, $path);
    }

    protected function getFullUrl($configName, $path) {
        if (substr($path, 0, 1) == '/') {
            $path = $this->router->getBaseUrl().substr($path, 1);
        }
        if (strpos($path, 'https://') === 0 || strpos($path, 'http://') === 0) {
            return $path;
        }
        return $this->config->get($configName).$path;
    }
    
}
