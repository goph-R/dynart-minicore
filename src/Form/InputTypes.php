<?php

namespace Dynart\Minicore\Form;

class InputTypes extends Types {
    
    public function __construct() {
        $this->add([
            'Checkbox',
            'CheckboxGroup',
            'File',
            'Hidden',
            'Password',
            'Select',
            'Separator',
            'Submit',
            'Text',
            'Textarea'
        ],
            '\Dynart\Minicore\Form\Input'
        );
    }

}