<?php

namespace Dynart\Minicore;

class View {

    private $scripts = [];
    private $styles = [];
    private $vars = [];
    private $blocks = [];
    private $blockNames = [];
    private $layout = [];
    private $folders = [];
    private $useLayout = true;
    private $pathChanges = [];

    public function setUseLayout($value) {
        $this->useLayout = $value;
    }
    
    public function changePath($original, $new) {
        $this->pathChanges[$original] = $new;
    }

    public function addFolder($name, $folder) {
        $this->folders[$name] = $folder;
    }

    public function getRealPath($path, $extension) {
        if (isset($this->pathChanges[$path])) {
            $path = $this->pathChanges[$path];
        }
        $result = $path.'.'.$extension;
        if ($path[0] != ':') {
            return $result;
        }
        $perPos = strpos($path,'/');
        if ($perPos == -1) {
            return $result;
        }
        $name = substr($path, 0, $perPos);
        if (!isset($this->folders[$name])) {
            return $result;
        }
        $result = $this->folders[$name].substr($path, $perPos + 1, strlen($path) - $perPos).'.'.$extension;
        return $result;
    }

    public function addScript($path) {
        $this->scripts[$path] = $path;
    }

    public function addStyle($path, $media='all') {
        $this->styles[$path.$media] = ['path' => $path, 'media' => $media];
    }

    public function hasBlock($name) {
        return isset($this->blocks[$name]);
    }

    public function startBlock($name) {
        $this->blocks[$name] = '';
        $this->blockNames[] = $name;
        ob_start();
    }

    public function appendBlock($name) {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = '';
        }
        if (!in_array($name, $this->blockNames)) {
            $this->blockNames[] = $name;
        }
        ob_start();
    }

    public function write($content) {
        echo $content;
    }

    public function endBlock() {
        $content = ob_get_clean();
        $name = array_pop($this->blockNames);
        $this->blocks[$name] .= $content;
    }

    public function fetchBlock($name) {
        if ($this->hasBlock($name)) {
            return $this->blocks[$name];
        }
        return '';
    }

    public function useLayout($path) {
        $this->layout[] = $path;
    }

    public function getScripts() {
        return $this->scripts;
    }

    public function getStyles() {
        return $this->styles;
    }

    public function set($vars) {
        foreach ($vars as $name => $value) {
            $this->setVariable($name, $value);
        }
    }

    public function setVariable($name, $value) {
        $this->vars[$name] = $value;
    }

    public function escape($value) {
        return htmlspecialchars($value);
    }

    public function fetch($path, $vars=[]) {
        $content = $this->tryToInclude($path, $vars);
        return $content;
    }

    public function fetchWithLayout($path, $vars=[]) {
        $content = $this->tryToInclude($path, $vars);
        if ($this->useLayout && $this->layout) {
            $path = array_pop($this->layout);
            $content .= $this->fetchWithLayout($path, $vars, $content);
        }
        return $content;
    }

    private function tryToInclude($__path, $__vars=[]) {
        $__realPath = $this->getRealPath($__path, 'phtml');
        if (!file_exists($__realPath)) {
            throw new FrameworkException("Can't include view: ".$__realPath);
        }
        ob_start();
        extract($this->vars);
        extract($__vars);
        include $__realPath;
        return ob_get_clean();
    }

}
