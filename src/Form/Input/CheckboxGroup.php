<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;

class CheckboxGroup extends Input {

    protected $defaultValue = [];
    protected $classes = ['checkbox'];

    private $checks;
    private $labels;

    public function __construct($name, $checks=[], $labelsByValues=[]) {        
        parent::__construct($name, null);
        $this->labels = $labelsByValues;
        $this->trimValue = false;
        $this->required = false;
        $this->setValue($checks);
    }

    public function setValue($values) {
        if (!is_array($values)) {
            $values = [];
        }
        $this->checks = [];
        foreach (array_keys($this->labels) as $value) {
            if (in_array($value, $values)) {
                $this->checks[] = $value;
            }
        }
        parent::setValue($this->checks);
    }

    public function fetch() { 
        $result = '<div class="checkbox-group">';
        foreach (array_keys($this->labels) as $value) {
            $id = $this->getId().'_'.$this->escapeName($value);
            $inputName = $this->form->getName().'['.$this->getName().'][]';
            $result .= '<div class="checkbox-group-row">';
            $result .= '<input type="checkbox" id="'.$id.'" name="'.$inputName.'"';
            $result .= ' value="'.$this->view->escape($value).'"';
            $result .= $this->getAttributesHtml();
            $result .= $this->getClassHtml();
            if (in_array($value, $this->checks)) {
                $result .= ' checked="checked"';
            }
            $result .= '>';            
            if ($this->labels[$value]) {
                $result .= '<label for="'.$id.'">'.$this->labels[$value].'</label>';
            }
            $result .= '</div>';
        }
        $result .= '</div>';
        return $result;
    }

}

