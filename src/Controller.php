<?php

namespace Dynart\Minicore;

abstract class Controller {

    /** @var App */
    protected $app;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var View */
    protected $view;

    public function __construct() {
        $framework = Framework::instance();
        $this->app = $framework->get('app');
        $this->request = $framework->get('request');
        $this->response = $framework->get('response');
        $this->view = $framework->get('view');
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
