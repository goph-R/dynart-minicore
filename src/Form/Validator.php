<?php 

namespace Dynart\Minicore\Form;

use Dynart\Minicore\Framework;

abstract class Validator {

    protected $message = '';
    protected $label = '';

    /** @var Translation */
    protected $translation;

    public function __construct() {
        $framework = Framework::instance();
        $this->translation = $framework->get('translation');
    }

    public function validate($label, $value) {
        $this->label = $label;
        return $this->doValidate($value);
    }

    public function getMessage() {
        return str_replace('{label}', $this->label, $this->message);
    }
    
    public function setMessage($message) {
        $this->message = $message;
    }
    
    abstract protected function doValidate($value);

}