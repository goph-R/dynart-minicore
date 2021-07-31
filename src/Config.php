<?php

namespace Dynart\Minicore;

class Config {

    private $environment;
    private $data = [];

    public function __construct($environment) {
        $this->environment = $environment;
    }

    public function load($path) {

        // parse ini
        if (!file_exists($path)) {
            throw new FrameworkException("Couldn't load config: $path");
        }
        $iniData = parse_ini_file($path, true);

        // include config
        if (isset($iniData['include'])) {
            foreach ($iniData['include'] as $name => $path) {
                $this->load($path);
            }
            unset($iniData['include']);
        }

        // copy the data
        foreach ($iniData as $environment => $data) {
            if (!isset($this->data[$environment])) {
                $this->data[$environment] = [];
            }
            foreach ($iniData[$environment] as $name => $value) {
                $this->data[$environment][$name] = $value;
            }
        }

    }

    public function get($name, $defaultValue=null) {
        if ($this->exists($name)) {
            return $this->data[$this->environment][$name];
        }
        if ($this->existsInEnvironment($name, 'all')) {
            return $this->data['all'][$name];
        }
        return $defaultValue;
    }

    public function exists($name) {
        return $this->existsInEnvironment($name, $this->environment);
    }

    public function existsInEnvironment($name, $environment) {
        return isset($this->data[$environment])
            && isset($this->data[$environment][$name]);
    }

    public function getEnvironment() {
        return $this->environment;
    }

}
