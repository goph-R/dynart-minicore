<?php

namespace Dynart\Minicore\Form\Validator;

use Dynart\Minicore\Framework;
use Dynart\Minicore\Form;
use Dynart\Minicore\Session;
use Dynart\Minicore\Form\Validator;

class Csrf extends Validator {
    
    /** @var Session */
    private $session;
    private $sessionName;
    
    /** @var Form */
    private $form;
    private $inputName;

    public function __construct(string $sessionName, Form $form, string $inputName) {
        parent::__construct();
        $framework = Framework::instance();
        $this->message = $this->translation->get('core', 'not_valid_csrf');
        $this->session = $framework->get('session');
        $this->sessionName = $sessionName;
        $this->form = $form;
        $this->inputName = $inputName;
    }

    protected function doValidate($value) {
        return $this->form->getValue($this->inputName) == $this->session->get($this->sessionName);
    }

}
