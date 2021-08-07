<?php

namespace Dynart\Minicore\Form\Validator;

use Dynart\Minicore\Form\Validator;

class Password extends Validator {

    const DEFAULT_OPTIONS = [
        'minLength'      => 8,
        'minLowerCase'   => 0,
        'minUpperCase'   => 0,
        'minNumber'      => 0,
        'minSpecialChar' => 0,
        'specialChars'   => '!@#$%^&+-*/=_'
    ];
    
    const MESSAGE = [
        'minLowerCase'   => 'password_use_more_lowercase',
        'minUpperCase'   => 'password_use_more_uppercase',
        'minNumber'      => 'password_use_more_numbers',
        'minSpecialChar' => 'password_use_more_specialchars'
    ];
    
    private $removerRegex = [
        'minLowerCase'   => '/[^a-z]+/',
        'minUpperCase'   => '/[^A-Z]+/',
        'minNumber'      => '/[^0-9]+/',
        'minSpecialChar' => '' // set at runtime based on 'specialChars'
    ];

    private $options;
    
    public function __construct($options=[]) {
        parent::__construct();
        $this->message = $this->translation->get('core', 'password_not_valid');        
        $this->options = self::DEFAULT_OPTIONS + $options;
        $regex = '';
        for ($i = 0; $i < strlen($this->options['specialChars']); $i++) {
            $regex .= '\\'.$this->options['specialChars'][$i];
        }
        $this->removerRegex['minSpecialChar'] = '/[^'.$regex.']+/';
    }

    protected function doValidate($value) {
        if (function_exists('iconv')) {
            $value = iconv('UTF-8','ASCII//TRANSLIT', $value);
        }
        $min = $this->options['minLength'];
        if ($this->options['minLength'] && mb_strlen($value) < $min) {
            $this->message = $this->translation->get('core', 'password_too_short', ['min' => $min]);
            return false;
        }        
        foreach ($this->removerRegex as $name => $regex) {
            $min = $this->options[$name];      
            $count = mb_strlen(preg_replace($regex, '', $value));
            if ($this->options[$name] && $count < $min) {
                $params = ['min' => $min, 'chars' => $this->options['specialChars']];
                $this->message = $this->translation->get('core', self::MESSAGE[$name], $params);
                return false;
            }
        }
        return true;
    }

}