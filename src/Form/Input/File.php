<?php

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input;

class File extends Input {

    protected $trimValue = false;
    protected $file = true;
    protected $classes = ['file-input'];

    public function fetch() {
        $result = '<div class="file"><label class="file-label">';
        $result .= '<input type="file"';
        $result .= ' id="'.$this->getId().'"';
        $result .= ' name="'.$this->form->getName().'['.$this->getName().']"';
        $result .= $this->getClassHtml();
        $result .= $this->getAttributesHtml();
        $result .= '>';
        $result .= '<span class="file-cta">';
        $result .= '<span class="file-icon"><i class="fas fa-upload"></i></span>';
        $result .= '<span class="file-label">'.text('core', 'choose_a_file').'</span>';
        $result .= '</span>';
        $result .= '</label></div>';
        return $result;        
    }

    public function isEmpty() {
        /** @var UploadedFile $value */
        $value = $this->getValue();        
        return !$value || $value->getError() == UPLOAD_ERR_NO_FILE;
    }

}
