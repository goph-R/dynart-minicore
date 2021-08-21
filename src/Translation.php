<?php

namespace Dynart\Minicore;

class Translation {

    const CONFIG_DEFAULT = 'translation.default';
    const CONFIG_ALL = 'translation.all';

    const DEFAULT_LOCALE = 'en';    
    
    protected $folders;
    protected $data;
    protected $allLocales;
    protected $hasMultiLocales;
    protected $locale;

    public function __construct() {
        $framework = Framework::instance();
        $config = $framework->get('config');
        $this->locale = $config->get(self::CONFIG_DEFAULT, self::DEFAULT_LOCALE);
        $allString = $config->get(self::CONFIG_ALL, $this->locale);
        $all = explode(',', $allString);
        $this->allLocales = array_map(function ($e) { return trim($e); }, $all);
        $this->hasMultiLocales = count($this->allLocales) > 1;
    }

    public function add(string $namespace, string $folder) {
        $this->data[$namespace] = false;
        $this->folders[$namespace] = $folder;
    }
    
    public function getAllLocales() {
        return $this->allLocales;
    }
    
    public function hasMultiLocales() {
        return $this->hasMultiLocales;
    }

    public function getLocale() {
        return $this->locale;
    }
  
    public function setLocale(string $locale) {
        $this->locale = $locale;
    }

    public function get(string $namespace, string $name, array $params=[]) {
        $result = '#'.$namespace.'.'.$name.'#';
        if (!isset($this->folders[$namespace]) || !isset($this->data[$namespace])) {
            return $result;
        }
        if ($this->data[$namespace] === false) {
            $path = $this->folders[$namespace].'/'.$this->locale.'.ini';
            $iniData = file_exists($path) ? parse_ini_file($path) : [];
            $this->data[$namespace] = $iniData;
        }
        if (isset($this->data[$namespace][$name])) {
            $result = $this->data[$namespace][$name];
        }
        foreach ($params as $name => $value) {
            $result = str_replace('{'.$name.'}', $value, $result);
        }
        return $result;
    }

}