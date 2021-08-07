<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;

class Submit extends Input {

    public function __construct($name, $defaultValue = '') {
        parent::__construct($name, $defaultValue);
        $this->required = false;
        $this->bind = false;
    }

    public function fetch() {
        $result = '<button type="submit"';
        $result .= $this->getAttributesHtml();
        $result .= $this->getClassHtml();
        $result .= ' id="'.$this->getId().'">';
        $result .= $this->getValue();
        $result .= '</button>';
        return $result;
    }

}