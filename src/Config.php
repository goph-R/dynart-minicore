<?php

namespace Dynart\Minicore;

class Config {

    private $data = [];

    public function load($path) {
        if (!file_exists($path)) {
            throw new FrameworkException("Couldn't load config: $path");
        }
        $this->data = array_merge($this->data, parse_ini_file($path));
    }

    public function get($name, $default=null) {
        return $this->has($name) ? $this->data[$name] : $default;
    }

    public function has($name) {
        return array_key_exists($name, $this->data);
    }

}
