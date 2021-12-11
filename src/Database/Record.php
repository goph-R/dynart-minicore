<?php

namespace Dynart\Minicore\Database;

use Dynart\Minicore\FrameworkException;

class Record implements \JsonSerializable {

    protected $modified = [];
    protected $newRecord;
    protected $data;

    public function __construct(array $data, bool $newRecord=true) {
        $this->data = $data;
        $this->newRecord = $newRecord;
    }

    public function isNew() {
        return $this->newRecord;
    }

    public function setNew(bool $value) {
        $this->newRecord = $value;
    }
    
    public function get(string $name) {
        if (!array_key_exists($name, $this->data)) {
            throw new FrameworkException("The record doesn't have the field '$name'.");
        }
        return $this->data[$name];
    }
    
    public function set(string $name, $value) {
        if (!array_key_exists($name, $this->data) || $value !== $this->data[$name]) {
            $this->modified[] = $name;
        }
        $this->data[$name] = $value;
    }

    public function setAll(array $array, array $fields=[]) {
        foreach ($array as $name => $value) {
            if ($fields && !in_array($name, $fields)) {
                continue;
            }
            $this->set($name, $value);
        }
    }
    
    public function asArray(array $fields=[]) {
        if (!$fields) {
            return $this->data;
        }
        $result = [];
        foreach (array_keys($this->data) as $name) {
            if (in_array($name, $fields)) {
                $result[$name] = $this->get($name);
            }
        }
        return $result;
    }

    public function getModified() {
        $result = [];
        foreach (array_keys($this->data) as $name) {
            if (in_array($name, $this->modified)) {
                $result[$name] = $this->get($name);
            }
        }
        return $result;
    }

    public function jsonSerialize() {
        return $this->data;
    }

}