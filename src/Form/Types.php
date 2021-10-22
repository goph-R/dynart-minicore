<?php

namespace Dynart\Minicore\Form;

use Dynart\Minicore\FrameworkException;

class Types {
    
    protected $data = [];

    public function get($name) {
        if (!isset($this->data[$name])) {
            throw new FrameworkException("Type doesn't exist: ".$name);
        }
        return $this->data[$name];
    }

    public function add($newTypes, $namespace='') {
        $types = [];
        if ($namespace) {
            foreach ($newTypes as $name => $type) {
                if (is_integer($name)) {
                    $name = $type;
                }
                $types[$name] = $namespace.'\\'.$type;
            }
        }
        $this->data = array_merge($this->data, $types);
    }

}