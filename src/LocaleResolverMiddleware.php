<?php

namespace Dynart\Minicore;

class LocaleResolverMiddleware implements Middleware {

    protected $request;
    protected $translation;
    protected $localeParameter = 'locale';

    public function __construct() {
        $framework = Framework::instance();
        $this->request = $framework->get('request');
        $this->translation = $framework->get('translation');
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
            $acceptLocale = strtolower(substr($acceptLanguage, 0, 2));
            if (in_array($acceptLocale, $allLocales)) {
                $this->translation->setLocale($acceptLocale);
            }
        }

        // if locale parameter exists and it's value in all locales, set that
        $locale = $this->request->get($this->localeParameter);
        if (in_array($locale, $allLocales)) {
            $this->translation->setLocale($locale);
        }
    }

}