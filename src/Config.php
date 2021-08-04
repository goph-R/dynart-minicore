<?php

namespace Dynart\Minicore;

class Config {

    private $env;
    private $data = [];

    public function __construct($env) {
        $this->env = $env;
    }

    public function load($path) {

        // parse ini
        if (!file_exists($path)) {
            throw new FrameworkException("Couldn't load config: $path");
        }
        $iniData = parse_ini_file($path, true);

        $this->include($iniData);
        

        // copy the data
        foreach ($iniData as $env => $data) {
            if (!isset($this->data[$env])) {
                $this->data[$env] = [];
            }
            foreach ($iniData[$env] as $name => $value) {
                $this->data[$env][$name] = $value;
            }
        }

    }

    public function include(array &$iniData) {
        if (isset($iniData['include'])) {
            foreach ($iniData['include'] as $name => $path) {
                $this->load($path);
            }
            unset($iniData['include']);
        }        
    }

    public function get($name, $defaultValue=null) {
        if ($this->exists($name)) {
            return $this->data[$this->env][$name];
        }
        if ($this->existsInEnvironment($name, 'all')) {
            return $this->data['all'][$name];
        }
        return $defaultValue;
    }

    public function exists($name) {
        return $this->existsInEnvironment($name, $this->env);
    }

    public function existsInEnvironment($name, $env) {
        return isset($this->data[$env])
            && isset($this->data[$env][$name]);
    }

    public function getEnvironment() {
        return $this->env;
    }

}
