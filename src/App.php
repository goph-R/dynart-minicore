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
    protected $routePath;

    /** @var Framework */
    protected $framework;

    /** @var Logger */
    protected $logger;

    /** @var Router */
    protected $router;
    
    /** @var RouteAliases */
    protected $routeAliases;

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
    protected $modules = [];

    public function __construct($env='dev', $configPath='config.ini.php') {
        $this->configPath = $configPath;
        $this->framework = Framework::instance();
        $ns = '\Dynart\Minicore\\';
        $this->framework->add([
            'config'        => [$ns.'Config', $env],
            'logger'        => $ns.'Logger',
            'database'      => [$ns.'Database', 'default'],
            'request'       => $ns.'Request',
            'response'      => $ns.'Response',
            'router'        => $ns.'Router', // TODO
            'routeAliases'  => $ns.'RouteAliases',
            'view'          => $ns.'View', // TODO
            'helper'        => $ns.'Helper',
            'translation'   => $ns.'Translation',
            // TODO: 'mailer'        => 'Mailer',
            'userSession'   => $ns.'UserSession' // TODO
        ]);
    }

    public function init() {
        $this->config = $this->framework->get('config');
        $this->config->load($this->configPath);
        $this->logger = $this->framework->get('logger');
        $this->initInstances();
        $this->initRoutePath();
        $this->initLocale();
        $this->initModules();
    }

    public function run() {
        $this->callRoute();
        $this->response->send();
    }
 
    /*
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

    public function getStaticUrl($path) {
        return $this->getFullUrl(self::CONFIG_STATIC_URL, $path);
    }

    public function getMediaPath($path='') {
        return $this->config->get(self::CONFIG_MEDIA_FOLDER).$path;
    }

    public function getMediaUrl($path='') {
        return $this->getFullUrl(self::CONFIG_MEDIA_URL, $path);
    }  
    
    protected function initInstances() {
        
        $coreFolder = $this->getCoreFolder();

        $this->request = $this->framework->get('request');
        $this->response = $this->framework->get('response');
        $this->router = $this->framework->get('router');
        $this->routeAliases = $this->framework->get('routeAliases');       
        
        $this->translation = $this->framework->get('translation');
        $this->translation->add('core', $coreFolder.'translations');
        
        $this->helper = $this->framework->get('helper');
        $this->helper->add(__FILE__, 'Helpers/view.php');
        
        $this->view = $this->framework->get('view');
        $this->view->addFolder(':app', $coreFolder.'templates');
        //$this->view->addFolder(':form', $coreFolder.'form/templates');
        //$this->view->addFolder(':pager', $coreFolder.'pager/templates');
    }

    protected function initRoutePath() {
        $routeParameter = $this->router->getParameter();
        $this->routePath = $this->request->get($routeParameter);
        if ($this->routeAliases->hasAlias($this->routePath)) {
            $this->routePath = $this->routeAliases->getPath($this->routePath);
        }    
    }
    
    public function getRoutePath() {
        return $this->routePath;
    }

    protected function initLocale() {
        if (!$this->translation->hasMultiLocales()) {
            return;
        }
        $this->translation->setLocale($this->getAcceptLocale());

        // TODO: remove this static locale position! bad idea, instead:
        // + use {variables} in routes
        // + global prefix/postfix feature

        $allLocales = $this->translation->getAllLocales();
        foreach ($allLocales as $locale) {
            $len = strlen($locale);
            $routeStart = substr($this->routePath, 0, $len);
            $startsWithLocale = $this->routePath == $locale || $routeStart.'/' == $locale.'/';
            if (!$startsWithLocale) {
                continue;
            }
            $this->translation->setLocale($locale);
            $this->routePath = substr($this->routePath, $len + 1, strlen($this->routePath) - $len);
            break;
        }
    }

    protected function getAcceptLocale() {
        $result = $this->translation->getLocale();
        $acceptLanguage = $this->request->getServer('HTTP_ACCEPT_LANGUAGE');
        if ($acceptLanguage) {
            $locales = $this->translation->getAllLocales();
            $acceptLocale = strtolower(substr($acceptLanguage, 0, 2));
            if (in_array($acceptLocale, $locales)) {
                $result = $acceptLocale;
            }
        }
        return $result;
    }

    protected function initModules() {
        // TODO: dependency tree
        foreach ($this->modules as $module) {
            $module->init();
        }
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
    
    protected function callRoute() {
        $route = $this->router->get($this->routePath, $this->request->getMethod());
        if ($route) {
            $route->call();
        } else {
            $this->framework->error(404);
        }
    }

}
