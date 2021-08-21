<?php

namespace Dynart\Minicore;

class Request {
    
    const CONFIG_URI_PREFIX = 'request.uri_prefix';

    protected $config;
    protected $data;
    protected $cookies;
    protected $method;
    protected $server;
    protected $headers;
    protected $uploadedFiles = [];

    public function __construct() {
        $framework = Framework::instance();        
        $this->config = $framework->get('config');
        $this->data = $_REQUEST;
        $this->cookies = $_COOKIE;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->server = $_SERVER;
        $this->headers = getallheaders();
        $this->createUploadedFiles($framework);
        $this->processJsonData();
    }
    
    protected function createUploadedFiles(Framework $framework) {
        if (empty($_FILES)) {
            return;
        }
        foreach ($_FILES as $name => $file) {
            if (!is_array($file['name'])) {
                $uploadedFile = $framework->create(['\\Dynart\\Minicore\\UploadedFile', $file]);
                $this->uploadedFiles[$name] = $uploadedFile;
            } else {
                $this->createUploadedFilesFromArray($framework, $name, $file);
            }            
        }
    }
    
    protected function createUploadedFilesFromArray(Framework $framework, $name, array $file) {
        $this->uploadedFiles[$name] = [];
        foreach (array_keys($file['name']) as $index) {
            $uploadedFile = $framework->create(['UploadedFile', $file, $index]);
            $this->uploadedFiles[$name][$index] = $uploadedFile;
        }
    }

    public function isJson() {
        return $this->getHeader('Content-Type') == 'application/json';
    }

    public function getRawInput() {
        return file_get_contents('php://input');
    }

    private function processJsonData() {
        if (!$this->isJson()) {
            return;
        }
        $json = $this->getRawInput();
        if (!$json) {
            return;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return;
        }
        foreach ($data as $name => $value) {
            $this->set($name, $value);
        }
    }
    
    public function getAll() {
        return $this->data;
    }

    public function get(string $name, $default=null) {
        return $this->has($name) ? $this->data[$name] : $default;
    }

    public function has(string $name) {
        return array_key_exists($name, $this->data);
    }

    public function set(string $name, $value) {
        $this->data[$name] = $value;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getHeader(string $name, $default=null) {
        return isset($this->headers[$name]) ? $this->headers[$name] : $default;
    }

    public function getServer(string $name, $default=null) {
        return isset($this->server[$name]) ? $this->server[$name] : $default;
    }

    public function getCookie(string $name, $default=null) {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : $default;
    }

    public function getIp() {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            $ip = $this->server['HTTP_CLIENT_IP'];
        } else if (!empty($this->server['HTTP_X_FORWARDED_FOR'])){
            $ip = $this->server['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $this->server['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public function getUri() {
        $uriPrefix = $this->config->get(self::CONFIG_URI_PREFIX);
        return substr($this->server['REQUEST_URI'], strlen($uriPrefix));
    }

    public function getUploadedFile(string $name) {
        return isset($this->uploadedFiles[$name]) ? $this->uploadedFiles[$name] : null;
    }
    
}