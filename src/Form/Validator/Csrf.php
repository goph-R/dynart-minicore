<?php

namespace Dynart\Minicore\Form\Validator;

use Dynart\Minicore\Form\Validator;

class Csrf extends Validator {
    
    /** @var UserSession */
    private $userSession;
    private $userSessionName;
    
    /** @var Form */
    private $form;
    private $inputName;

    public function __construct($userSessionName, Form $form, $inputName) {
        parent::__construct();
        $framework = Framework::instance();
        $this->message = $this->translation->get('core', 'not_valid_csrf');
        $this->userSession = $framework->get('userSession');
        $this->userSessionName = $userSessionName;
        $this->form = $form;
        $this->inputName = $inputName;
    }

    protected function doValidate($value) {
        return $this->form->getValue($this->inputName) == $this->userSession->get($this->userSessionName);
    }

}
