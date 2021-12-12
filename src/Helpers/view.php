<?php

use Dynart\Minicore\Framework;

function static_url($path, $useTimestamp=true) {
    $app = Framework::instance()->get('app');
    return $app->getStaticUrl($path, $useTimestamp);
}

function route_url($path=null, $params=[], $amp='&amp;') {
    $router = Framework::instance()->get('router');
    return $router->getUrl($path, $params, $amp);
}

function css($src, $media='all', $useTimestamp=true) {
    return '<link rel="stylesheet" type="text/css" href="'.static_url($src, $useTimestamp).'" media="'.$media.'">'."\n";
}

function script($src, $useTimestamp=true) {
    return '<script src="'.static_url($src, $useTimestamp).'" type="text/javascript"></script>'."\n";
}

function use_css($src, $media='all') {
    $view = Framework::instance()->get('view');
    $view->addStyle($src, $media);
}
function use_script($src) {
    $view = Framework::instance()->get('view');
    $view->addScript($src);
}

function fetch_scripts($useTimestamp=true) {
    $view = Framework::instance()->get('view');
    return $view->fetchScripts($useTimestamp);
}

function fetch_styles($useTimestamp=true) {
    $view = Framework::instance()->get('view');
    return $view->fetchStyles($useTimestamp);
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