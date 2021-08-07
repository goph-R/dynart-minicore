<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;

class File extends Input {
    
    protected $trimValue = false;
    protected $file = true;
    
    public function fetch() {
        $result = '<input type="file"';
        $result .= ' id="'.$this->getId().'"';
        $result .= ' name="'.$this->form->getName().'['.$this->getName().']"';
        $result .= $this->getClassHtml();
        $result .= $this->getAttributesHtml();
        $result .= '>';
        return $result;
    }

    public function isEmpty() {
        /** @var UploadedFile $value */
        $value = $this->getValue();        
        return !$value || $value->getError() == UPLOAD_ERR_NO_FILE;
    }
    
}
