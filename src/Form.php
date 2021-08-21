<?php

namespace Dynart\Minicore;

use Dynart\Minicore\Form\Input;
use Dynart\Minicore\Form\InputTypes;
use Dynart\Minicore\Form\Validator;
use Dynart\Minicore\Form\ValidatorTypes;


class Form {

    /** @var View */
    protected $view;

    /** @var Request */
    protected $request;

    /** @var Translation */
    protected $translation;

    /** @var Input[] */
    protected $inputs = [];

    /** @var Validator[][] */
    protected $validators = [];

    /** @var Validator[] */
    protected $postValidators = [];
    
    /** @var Session */
    protected $session;

    /** @var InputTypes */
    protected $inputTypes;

    /** @var ValidatorTypes */
    protected $validatorTypes;

    protected $order = [];
    protected $errors = [];
    protected $name = '';
    protected $useCsrf = true;

    public function __construct($name='form') {
        $framework = Framework::instance();
        $this->request = $framework->get('request');
        $this->view = $framework->get('view');
        $this->translation = $framework->get('translation');
        $this->session = $framework->get('session');
        $this->inputTypes = $framework->get('inputTypes');
        $this->validatorTypes = $framework->get('validatorTypes');
        $this->name = $name;
    }
    
    public function setUseCsrf($useCsrf) {
        $this->useCsrf = $useCsrf;
    }

    public function getName() {
        return $this->name;
    }

    private function getText($data) {
        if (is_array($data) && count($data) == 2) {
            return $this->translation->get($data[0], $data[1]);
        }
        return $data;
    }

    public function addInput(string $name, $label, array $newInputData) {        
        $type = $this->inputTypes->get(array_shift($newInputData));
        $input = Framework::instance()->create(array_merge([$type, $name], $newInputData));
        if (!in_array($name, $this->order)) {
            $this->order[] = $name;
        }        
        $this->inputs[$name] = $input;
        $input->setForm($this);
        $input->setLabel($this->getText($label));
        return $input;
    }

    public function removeInput($name) {
        $this->checkInputExistance($name);
        unset($this->inputs[$name]);
        if (isset($this->validators[$name])) {
            unset($this->validators[$name]);
        }
    }

    public function getInput($name) {
        $this->checkInputExistance($name);
        return $this->inputs[$name];
    }

    public function getValues() {
        $result = [];
        foreach ($this->inputs as $input) {
            if ($input->needsBind()) {
                $result[$input->getName()] = $input->getValue();                    
            }
        }
        return $result;
    }
    
    public function getInputs() {
        $result = [];
        foreach ($this->order as $name) {
            $result[] = $this->inputs[$name];
        }
        return $result;
    }
    
    public function getInputErrors() {
        $result = [];
        foreach ($this->inputs as $name => $input) {
            $result[$name] = $input->getError();
        }
        return $result;
    }

    public function hasInput($inputName) {
        return isset($this->inputs[$inputName]);
    }
    
    public function checkInputExistance($inputName) {
        if (!$this->hasInput($inputName)) {
            throw new FrameworkException("Input doesn't exist: $inputName");
        }
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function addError($error) {
        $this->errors[] = $error;
    }

    public function addValidator($inputName, $newValidator) {
        $this->checkInputExistance($inputName);
        $framework = Framework::instance();
        $validator = $framework->create(
            $this->validatorTypes->get($newValidator)
        );
        if (!isset($this->validators[$inputName])) {
            $this->validators[$inputName] = [];
        }
        $this->validators[$inputName][] = $validator;
    }

    public function addPostValidator($newValidator) {
        $framework = Framework::instance();
        $this->postValidators[] = $framework->create(
            $this->validatorTypes->get($newValidator)
        );
    }

    public function getValue($inputName) {
        $this->checkInputExistance($inputName);
        return $this->inputs[$inputName]->getValue();
    }

    public function setValue($inputName, $value) {
        $this->checkInputExistance($inputName);
        $this->inputs[$inputName]->setValue($value);
    }
    
    public function setRequired($inputName, $required) {
        $this->checkInputExistance($inputName);
        $this->inputs[$inputName]->setRequired($required);
    }

    public function bind() {
        $this->errors = [];
        $this->addCsrfInput();
        $values = $this->request->get($this->getName());
        $files = $this->request->getUploadedFile($this->getName());
        foreach ($this->inputs as $input) {
            if (!$input->needsBind()) {
                continue;
            }
            $name = $input->getName();
            if ($input->isFile()) {
                $value = isset($files[$name]) ? $files[$name] : null;
            } else {
                $value = isset($values[$name]) ? $values[$name] : null;
            }
            $input->setValue($value);
        }
    }

    public function process() {
        if ($this->request->getMethod() != 'POST') {
            return false;
        }
        $this->bind();
        return $this->validate();
    }

    public function validate() {
        $result = $this->validateInputs();
        if ($result) {
            $result = $this->postValidate();
        }
        return $result;
    }

    protected function validateInputs() {
        $result = true;        
        foreach ($this->inputs as $inputName => $input) {            
            if (!$input->isRequired() && $input->isEmpty()) {
                continue;
            }            
            if ($input->isRequired() && $input->isEmpty()) {
                $input->setError($this->translation->get('core', 'cant_be_empty'));
                $result = false;
            } else if (isset($this->validators[$inputName])) {
                $result &= $this->validateInput($input, $this->validators[$inputName]);
            } 
        }
        return $result;
    }

    /**
     * @param Input $input
     * @param Validator[] $validators
     * @return bool
     */
    protected function validateInput($input, $validators) {
        foreach ($validators as $validator) {
            $result = $validator->validate($input->getLabel(), $input->getValue());
            if (!$result) {
                $input->setError($validator->getMessage());
                return false;
            }                
        }        
        return true;
    }

    protected function postValidate() {
        $result = true;
        foreach ($this->postValidators as $validator) {
            $subResult = $validator->validate('', null);
            if (!$subResult) {
                $this->errors[] = $validator->getMessage();
                $result = false;
            }
        }
        return $result;
    }

    protected function fetchHead() {
        foreach ($this->inputs as $input) {
            foreach ($input->getStyles() as $style => $media) {
                $this->view->addStyle($style, $media);
            }
            foreach ($input->getScripts() as $script) {
                $this->view->addScript($script);
            }
        }
    }
    
    protected function setCsrfSession() {
        if (!$this->useCsrf) {
            return;
        }
        try {
            $csrf = bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            throw new FrameworkException("Couldn't create random bytes.");
        }
        $this->session->set('csrf', $csrf);
    }
    
    protected function addCsrfInput() {
        if (!$this->useCsrf) {
            return;
        }
        $csrf = $this->session->get('csrf');
        $this->addInput('_csrf', null, ['Hidden', $csrf]);
        //$this->addPostValidator(['Csrf', 'csrf', $this, 'csrf']);
    }

    public function fetch(string $path = ':core/form', $params=[]) {
        $this->setCsrfSession();
        $this->addCsrfInput();
        $this->fetchHead();
        $allParams = array_merge($params, ['form' => $this]);
        $result = $this->view->fetch($path, $allParams);
        return $result;
    }

}
