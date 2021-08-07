<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input as Input;

class Text extends Input {

    protected $type = 'text';    
    protected $placeholder = '';
    protected $autocomplete = true;

    public function setPlaceholder($placeholder) {
        $this->setAttribute('placeholder', $placeholder);
    }
    
    public function setAutocomplete($autocomplete) {
        $this->setAttribute('autocomplete', $autocomplete ? 'on' : 'off');
    }

    public function fetch() {
        $result = '<input type="'.$this->type.'"';
        $result .= ' id="'.$this->getId().'"';
        $result .= ' name="'.$this->form->getName().'['.$this->getName().']"';
        $result .= ' value="'.$this->view->escape($this->getValue()).'"';
        $result .= $this->getAttributesHtml();
        $result .= $this->getClassHtml();
        $result .= '>';
        return $result;
    }

}