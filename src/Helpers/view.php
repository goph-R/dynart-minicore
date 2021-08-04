<?php

use Dynart\Minicore\Framework;

function static_url($path) {
    $app = Framework::instance()->get('app');
    return $app->getStaticUrl($path);
}

function route_url($path=null, $params=[], $amp='&amp;') {
    $router = Framework::instance()->get('router');
    return $router->getUrl($path, $params, $amp);
}

function css($src, $media='all') {
    return '<link rel="stylesheet" type="text/css" href="'.$src.'" media="'.$media.'">'."\n";
}

function script($src) {
    return '<script src="'.$src.'" type="text/javascript"></script>'."\n";
}

function use_css($src, $media='all') {
    $view = Framework::instance()->get('view');
    $view->addStyle(static_url($src), $media);
}

function use_module_css($moduleId, $src, $media='all') {
    $framework = Framework::instance();
    $view = $framework->get('view');
    $app = $framework->get('app');
    $module = $app->getModule($moduleId);
    $view->addStyle($module->getUrl().$src, $media);
}

function use_script($src) {
    $view = Framework::instance()->get('view');
    $view->addScript(static_url($src));
}

function use_module_script($moduleId, $src) {
    $framework = Framework::instance();
    $view = $framework->get('view');
    $app = $framework->get('app');
    $module = $app->getModule($moduleId);
    $view->addScript($module->getUrl().$src);
}

function fetch_scripts() {
    $view = Framework::instance()->get('view');
    $result = '';
    foreach ($view->getScripts() as $script) {
        $result .= script($script);
    }
    if ($view->hasBlock('scripts')) {
        $result .= $view->fetchBlock('scripts');
    }
    return $result;
}

function fetch_styles() {
    $view = Framework::instance()->get('view');
    $result = '';
    foreach ($view->getStyles() as $style) {
        $result .= css($style['path'], $style['media']);
    }
    return $result;
}

function fetch_content($contentPath, $vars=[]) {
    $view = Framework::instance()->get('view');
    return $view->fetch($contentPath, $vars);
}

function use_layout($path) {
    $view = Framework::instance()->get('view');
    $view->useLayout($path);
}

function start_block($name) {
    $view = Framework::instance()->get('view');
    $view->startBlock($name);
}

function append_block($name) {
    $view = Framework::instance()->get('view');
    $view->appendBlock($name);
}

function end_block() {
    $view = Framework::instance()->get('view');
    $view->endBlock();
}

function fetch_block($name) {
    $view = Framework::instance()->get('view');
    return $view->fetchBlock($name);
}

function esc($value) {
    return htmlspecialchars($value);
}

function use_translation($namespace) {
    $translation = Framework::instance()->get('translation');
    $translation->setNamespace($namespace);
}

function t($name, $params=[]) {
    $translation = Framework::instance()->get('translation');
    return $translation->get($translation->getNamespace(), $name, $params);
}

function text($namespace, $name, $params=[]) {
    $translation = Framework::instance()->get('translation');
    return $translation->get($namespace, $name, $params);
}

function date_view($dateStr) {
    if (!$dateStr) {
        return '';
    }
    $time = strtotime($dateStr);
    return str_replace(' ', '&nbsp;', date('Y-m-d H:i', $time));
}

function date_diff_view($dateStr) {
    $now = new DateTime('now');
    $date = new DateTime($dateStr);
    $interval = date_diff($now, $date);
    if ($interval->y > 0) {
        $result = $interval->format('%y '.text('core', 'diff_years'));
    } else if ($interval->m > 0) {
        $result = $interval->format('%m '.text('core', 'diff_months'));
    } else if ($interval->d > 0) {
        $result = $interval->format('%d '.text('core', 'diff_days'));
    } else if ($interval->h > 0) {
        $result = $interval->format('%h '.text('core', 'diff_hours'));
    } else if ($interval->i > 0) {
        $result = $interval->format('%i '.text('core', 'diff_minutes'));
    } else {
        $result = text('core', 'diff_recently');
    }
    return str_replace(' ', '&nbsp;', $result);
}