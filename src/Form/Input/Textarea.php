<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;

class Textarea extends Input {

    protected $type = 'text';    
    protected $placeholder = '';
    protected $classes = ['textarea'];

    public function __construct($name, $defaultValue = '') {
        parent::__construct($name, $defaultValue);
        $this->trimValue = false;
    }

    public function fetch() {
        $result = '<textarea';
        $result .= ' id="'.$this->getId().'"';
        $result .= ' name="'.$this->form->getName().'['.$this->getName().']"';
        $result .= $this->getAttributesHtml();
        $result .= $this->getClassHtml();
        $result .= '>'.$this->getValue().'</textarea>';
        return $result;
    }

}