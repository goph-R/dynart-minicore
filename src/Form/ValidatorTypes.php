<?php

namespace Dynart\Minicore\Form;

class ValidatorTypes extends Types {
    
    public function __construct() {
        $this->add([
            'Email',
            'Csrf',
            'Password',
            'MinimumSelect',
        ],
            '\Dynart\Minicore\Form\Validator'
        );
    }

}