<?php

namespace Dynart\Minicore;

class Session {

    public function __construct() {
        session_start();
    }

    public function getId() {
        return session_id();
    }

    public function get(string $name, $default=null) {
        $key = 'user.'.$name;
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public function set(string $name, $value) {
        $_SESSION['user.'.$name] = $value;
    }

    public function remove(string $name) {
        unset($_SESSION['user.'.$name]);
    }

    public function destroy() {
        session_destroy();
    }
    
    public function finish() {
    }

}
