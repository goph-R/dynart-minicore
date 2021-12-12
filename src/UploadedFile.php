<?php

namespace Dynart\Minicore;

class UploadedFile {
    
    private $name;
    private $tempPath;
    private $error;
    private $size;
    private $type;
    
    public function __construct(array $data, $index=null) {
        if ($index === null) {
            $this->name = $data['name'];
            $this->tempPath = $data['tmp_name'];
            $this->error = $data['error'];
            $this->type = $data['type'];
            $this->size = $data['size'];
        } else {
            $this->name = $data['name'][$index];
            $this->tempPath = $data['tmp_name'][$index];
            $this->error = $data['error'][$index];
            $this->type = $data['type'][$index];
            $this->size = $data['size'][$index];
        }
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getTempPath() {
        return $this->tempPath;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function getSize() {
        return $this->size;
    }
    
    public function getType() {
        return $this->type;
    }
    
    public function isUploaded() {
        return is_uploaded_file($this->tempPath);
    }
    
    public function moveTo(string $path) {
        move_uploaded_file($this->tempPath, $path);
        $this->tempPath = null;
    }
    
}
