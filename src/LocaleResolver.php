<?php

namespace Dynart\Minicore;

class LocaleResolver {

    protected $parameter = 'locale'; // TODO: use config for this

    public function getParameter() {
        return $this->parameter;
    }

    public function run() {
        $framework = Framework::instance();
        $translation = $framework->get('translation');

        // if no multi locales, don't do anything
        if (!$translation->hasMultiLocales()) {
            return;
        }

        $request = $framework->get('request');
        $router = $framework->get('router');
        $allLocales = $translation->getAllLocales();

        // set locale via the accept language header (browser's default locale)
        $acceptLanguage = $request->getServer('HTTP_ACCEPT_LANGUAGE');
        if ($acceptLanguage) {
            $acceptLocale = strtolower(substr($acceptLanguage, 0, 2)); // we use only neutral locale for now
            if (in_array($acceptLocale, $allLocales)) {
                $translation->setLocale($acceptLocale);
            }
        }

        // if locale parameter exists and it's value in all locales, set that
        $locale = $request->get($this->parameter);
        if (in_array($locale, $allLocales)) {
            $translation->setLocale($locale);
        }
        
        // set the locale variable for the route prefix
        $router->addPrefixVariable($this->parameter, [$translation, 'getLocale']);
    }

}