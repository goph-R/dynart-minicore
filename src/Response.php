<?php

namespace Dynart\Minicore;

class Response {

    private $headers = [];
    private $content;

    public function setCookie(string $name, $value, $time=null) {
        setcookie($name, $value, $time ? $time : time() + 31536000);
    }

    public function setHeaders(array $values) {
        foreach ($values as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    public function setHeader(string $name, string $value) {
        $this->headers[$name] = $value;
    }

    public function setContent(string $content) {
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
