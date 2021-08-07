<?php

namespace Dynart\Minicore;

class Response {

    private $headers = [];
    private $content;

    public function setCookie($name, $value, $time=null) {
        setcookie($name, $value, $time ? $time : time() + 31536000);
    }

    public function setHeaders($values) {
        foreach ($values as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function send() {
        foreach ($this->headers as $name => $value) {
            if ($value !== null && $value !== '') {
                header($name . ': ' . $value);
            }
        }
        if ($this->content) {
            echo $this->content;
        }
    }

}
