<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;

class Select extends Input {

    protected $options = [];

    public function __construct($name, $defaultValue='', $options=[]) {
        parent::__construct($name, $defaultValue);
        $this->options = $options;
    }

    public function fetch() {
        $result = '<div class="select '.join(' ', $this->getClasses()).'">';
        $result .= '<select';
        $result .= ' id="'.$this->getId().'"';
        $result .= ' name="'.$this->form->getName().'['.$this->getName().']"';
        $result .= $this->getAttributesHtml();
        $result .= $this->getClassHtml();
        $result .= '>';
        $result .= $this->fetchRecursive($this->options, null);
        $result .= '</select>';
        $result .= '</div>';
        return $result;
    }

    private function fetchRecursive($options, $groupName) {
        $result = $groupName ? '<optgroup label="'.$this->view->escape($groupName).'">' : '';
        foreach ($options as $optionValue => $optionText) {
            if (!is_array($optionText)) {
                $selected = $optionValue == $this->getValue() ? ' selected="selected"' : '';
                $value = $this->view->escape($optionValue);
                $result .= '<option value="'.$value.'"'.$selected.'>'.$optionText.'</option>';
            } else {
                $result .= $this->fetchRecursive($optionText['options'], $optionText['label']);
            }
        }
        $result .= $groupName ? '</optgroup>' : '';
        return $result;
    }

}