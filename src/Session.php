<?php

namespace Dynart\Minicore;

class Session {

    public function __construct($start=true) {
        if ($start) {
            $this->start();
        }
    }
    
    public function start() {
        session_start();
    }

    public function getId() {
        return session_id();
    }

    public function get(string $name, $default=null) {
        return isset($_SESSION[$this->getKey($name)]) ? $_SESSION[$this->getKey($name)] : $default;
    }

    public function set(string $name, $value) {
        $_SESSION[$this->getKey($name)] = $value;
    }

    public function remove(string $name) {
        unset($_SESSION[$this->getKey($name)]);
    }

    public function destroy() {
        session_destroy();
    }
    
    public function finish() {
    }

    private function getKey(string $name) {
        return 'user.'.$name;
    }

}
