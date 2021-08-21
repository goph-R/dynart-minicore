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

    public function setUseLayout(bool $value) {
        $this->useLayout = $value;
    }
    
    public function changePath(string $original, string $new) {
        $this->pathChanges[$original] = $new;
    }

    public function addFolder(string $name, string $folder) {
        $this->folders[$name] = $folder;
    }

    public function getRealPath(string $path, string $extension) {
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
        $result = $this->folders[$name].'/'.substr($path, $perPos + 1, strlen($path) - $perPos).'.'.$extension;
        return $result;
    }

    public function addScript(string $path) {
        $this->scripts[$path] = $path;
    }

    public function addStyle(string $path, string $media='all') {
        $this->styles[$path.$media] = ['path' => $path, 'media' => $media];
    }

    public function hasBlock(string $name) {
        return isset($this->blocks[$name]);
    }

    public function startBlock(string $name) {
        $this->blocks[$name] = '';
        $this->blockNames[] = $name;
        ob_start();
    }

    public function appendBlock(string $name) {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = '';
        }
        if (!in_array($name, $this->blockNames)) {
            $this->blockNames[] = $name;
        }
        ob_start();
    }

    public function write(string $content) {
        echo $content;
    }

    public function endBlock() {
        $content = ob_get_clean();
        $name = array_pop($this->blockNames);
        $this->blocks[$name] .= $content;
    }

    public function fetchBlock(string $name) {
        return $this->hasBlock($name) ? $this->blocks[$name] : '';
    }

    public function useLayout(string $path) {
        $this->layout[] = $path;
    }

    public function getScripts() {
        return $this->scripts;
    }

    public function getStyles() {
        return $this->styles;
    }

    public function set(array $vars) {
        foreach ($vars as $name => $value) {
            $this->setVariable($name, $value);
        }
    }

    public function setVariable(string $name, $value) {
        $this->vars[$name] = $value;
    }

    public function escape(string $value) {
        return htmlspecialchars($value);
    }

    public function fetch(string $path, array $vars=[]) {
        $content = $this->tryToInclude($path, $vars);
        return $content;
    }

    public function fetchWithLayout(string $path, array $vars=[]) {
        $content = $this->tryToInclude($path, $vars);
        if ($this->useLayout && $this->layout) { // TODO: recursive layout
            $path = array_pop($this->layout);
            $content .= $this->fetchWithLayout($path, $vars);
        }
        return $content;
    }

    public function fetchScripts(bool $useTimestamp=true) {
        $result = '';
        foreach ($this->getScripts() as $script) {
            $result .= script($script, $useTimestamp);
        }
        if ($this->hasBlock('scripts')) {
            $result .= $this->fetchBlock('scripts');
        }
        return $result;        
    }

    public function fetchStyles(bool $useTimestamp=true) {
        $result = '';
        foreach ($this->getStyles() as $style) {
            $result .= css($style['path'], $style['media'], $useTimestamp);
        }
        return $result;        
    }

    private function tryToInclude(string $__path, array $__vars=[]) {
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
