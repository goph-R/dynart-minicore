<?php

namespace Dynart\Minicore\Form;

use Dynart\Minicore\Framework;

abstract class Input { // TODO: extends Widget?

    /** @var View */
    protected $view;

    /** @var Request */
    protected $request;

    /** @var Form */
    protected $form;

    /** @var Config */
    protected $config;

    protected $name;
    protected $description;
    protected $error;
    protected $defaultValue;
    protected $scripts = [];
    protected $styles = [];
    protected $classes = [];
    protected $value;
    protected $trimValue = true;
    protected $required = true;
    protected $label = '';
    protected $hidden = false; // TODO: properties?
    protected $locale = null; // TODO: properties?
    protected $bind = true;
    protected $rowBegin = true; // TODO: Form::startRow() ?
    protected $rowEnd = true; // TODO: Form::endRow() ?
    protected $attributes = [];

    /*

    Input::setProperty()?
    $this->setProperty('label')
    Input::getProperty()?

    */

    protected $mustValidate = false;
    protected $file = false;
    protected $readOnly = false;
    
    abstract public function fetch();

    public function __construct($name, $defaultValue = '') {
        $framework = Framework::instance();
        $this->config = $framework->get('config');
        $this->view = $framework->get('view');
        $this->request = $framework->get('request');
        $this->name = $name;
        $this->defaultValue = $defaultValue;
        $this->value = $defaultValue;
    }
    
    public function isReadOnly() {
        return $this->readOnly;
    }
    
    public function setReadOnly($value) {
        if ($value) {
            $this->attributes['readonly'] = true;
        } else if (isset($this->attributes['readonly'])) {
            unset($this->attributes['readonly']);
        }
        $this->readOnly = $value;
    }
    
    public function isMustValidate() {
        return $this->mustValidate;
    }
    
    public function setMustValidate($value) {
        $this->mustValidate = $value;
    }
    
    public function setLocale($value) {
        $this->locale = $value;
    }
    
    public function getLocale() {
        return $this->locale;
    }
            
    public function setAttribute($name, $value) {
        $this->attributes[$name] = $value;
    }
    
    public function isFile() {
        return $this->file;
    }
    
    public function getAttributesHtml() { // TODO: fetchAttributes()?
        $result = '';
        foreach ($this->attributes as $name => $value) {
            if ($value === null) {
                continue;
            }
            $result .= ' '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            if ($value === true) {
                continue;
            }
            $result .= '="';
            $result .= htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"';
        }
        return $result;
    }

    public function needsBind() {
        return $this->bind;
    }
    
    public function setRowBegin($value) {
        $this->rowBegin = $value;
    }
    
    public function hasRowBegin() { // TODO: isRowBegin()?
        return $this->rowBegin;
    }
    
    public function setRowEnd($value) {
        $this->rowEnd = $value;
    }
    
    public function hasRowEnd() { // TODO: isRowEnd()?
        return $this->rowEnd;
    }
    
    public function addClass($class) {
        $this->classes[] = $class;
    }

    public function setForm($form) {
        $this->form = $form;
    }

    public function escapeName($name) {
        return preg_replace('/[^0-9a-zA-Z_]+/', '_', $name);
    }
    
    public function getId() {
        $safeName = $this->escapeName($this->getName());
        $formSafeName = $this->escapeName($this->form->getName());
        return $formSafeName.'_'.$safeName;
    }

    public function setTrimValue($trimValue) {
        $this->trimValue = $trimValue;
    }

    public function setError($error) {
        $this->error = $error;
    }

    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function setRequired($required) {
        $this->required = $required;
    }
    
    public function isRequired() {
        return $this->required;
    }

    public function getClasses() {
        $classes = $this->classes;
        if ($this->hasError()) {
            $classes[] = 'error';
        }
        return $classes;
    }

    public function getClassHtml() { // TODO: fetchClass()?
        $classes = $this->getClasses();
        return $classes ? ' class="'.join(' ', $classes).'"' : '';
    }

    public function hasError() {
        return (boolean)$this->error;
    }

    public function getError() {
        return $this->error;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getScripts() {
        return $this->scripts;
    }

    public function getStyles() {
        return $this->styles;
    }

    public function getName() {
        return $this->name;
    }
    
    public function isEmpty() {
        return empty($this->getValue());
    }

    public function getValue() {
        return $this->trimValue ? trim($this->value) : $this->value;
    }

    public function setValue($value) {
        $this->value = $value;
    }
    
    public function setLabel($label) {
        $this->label = $label;
    }
    
    public function getLabel() {
        return $this->label;
    }

    public function getDefaultValue() { // $input->get('default_value');
        return $this->defaultValue;
    }

    public function isHidden() { // $input->is('hidden');
        return $this->hidden;
    }

    public function setHidden($value) { // $input->set('hidden', true);
        $this->hidden = $value;
    }

}