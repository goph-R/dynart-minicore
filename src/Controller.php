<?php

namespace Dynart\Minicore;

abstract class Controller {

    /** @var Framework */
    protected $framework;

    /** @var App */
    protected $app;

    /** @var Config */
    protected $config;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var Router */
    protected $router;

    /** @var View */
    protected $view;

    /** @var Translation */
    protected $translation;

    /** @var UserSession */
    protected $userSession;

    public function __construct() {
        $this->framework = Framework::instance();
        $this->app = $this->framework->get('app');
        $this->config = $this->framework->get('config');
        $this->request = $this->framework->get('request');
        $this->response = $this->framework->get('response');
        $this->router = $this->framework->get('router');
        $this->view = $this->framework->get('view');
        $this->translation = $this->framework->get('translation');
        $this->userSession = $this->framework->get('userSession');
    }

    public function render($path, $vars=[]) {
        $content = $this->view->fetchWithLayout($path, $vars);
        $this->response->setContent($content);
    }

    public function renderContent($path, $vars=[]) {
        $content = $this->view->fetch($path, $vars);
        $content .= $this->view->fetchBlock('content');
        $scripts = $this->view->fetchScripts();
        $this->response->setContent($content.$scripts);
    }

    public function json($data) {
        $this->response->setContent(json_encode($data));
    }

    public function error($code, $content='') {
        $this->app->error($code, $content);
    }

    public function redirect($path=null, $params=[]) {
        $this->app->redirect($path, $params);
    }

}
