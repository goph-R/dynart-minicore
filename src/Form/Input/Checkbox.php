<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;
use Dynart\Minicore\Framework;

class Checkbox extends Input {

    private $checked;
    private $text;

    protected $classes = ['checkbox'];

    public function __construct($name, $defaultValue='1', $text='', $checked=false) {
        parent::__construct($name, $defaultValue);
        $this->checked = $checked;
        if (is_array($text) && count($text) == 2) {
            $this->text = text($text[0], $text[1]);
        } else {
            $this->text = $text;
        }
    }

    public function setValue($value) {
        parent::setValue($value);        
        $this->checked = $value == $this->defaultValue;
    }

    public function fetch() {
        $result = '';
        if ($this->text) {
            $result .= '<label class="form-check-label">';
        }
        $result .= '<input type="checkbox"';
        $result .= ' id="'.$this->getId().'"';
        $result .= ' name="'.$this->form->getName().'['.$this->getName().']"';
        $result .= ' value="'.$this->view->escape($this->defaultValue).'"';
        $result .= $this->getAttributesHtml();
        $result .= $this->getClassHtml();
        if ($this->checked) {
            $result .= ' checked="checked"';
        }
        $result .= '>';
        if ($this->text) {
            $result .= ' '.$this->view->escape($this->text).'</label>';
        }
        return $result;
    }

}