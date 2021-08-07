<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;

class Separator extends Input {
    
    public function __construct($name, $defaultValue='') {
        parent::__construct($name, $defaultValue);
        $this->required = false;
        $this->bind = false;
    }

    public function fetch() {
        return $this->defaultValue;
    }

}