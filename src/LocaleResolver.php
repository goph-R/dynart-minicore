<?php

namespace Dynart\Minicore;

class LocaleResolver {

    protected $framework;
    protected $request;
    protected $translation;
    protected $parameter = 'locale'; // TODO: use config for this

    public function __construct() {
        $this->framework = Framework::instance();
        $this->request = $this->framework->get('request');
        $this->translation = $this->framework->get('translation');
    }

    public function getParameter() {
        return $this->parameter;
    }

    public function run() {

        // if no multi locales, don't do anything
        if (!$this->translation->hasMultiLocales()) {
            return;
        }

        $allLocales = $this->translation->getAllLocales();        

        // set locale via the accept language header (browser's default locale)
        $acceptLanguage = $this->request->getServer('HTTP_ACCEPT_LANGUAGE');
        if ($acceptLanguage) {
            $acceptLocale = strtolower(substr($acceptLanguage, 0, 2)); // we use only neutral locale for now
            if (in_array($acceptLocale, $allLocales)) {
                $this->translation->setLocale($acceptLocale);
            }
        }

        // if locale parameter exists and it's value in all locales, set that
        $locale = $this->request->get($this->parameter);
        if (in_array($locale, $allLocales)) {
            $this->translation->setLocale($locale);
        }
        
        // set the locale variable for the route prefix
        $router = $this->framework->get('router');
        $router->addPrefixVariable($this->parameter, [$this->translation, 'getLocale']);
    }

}