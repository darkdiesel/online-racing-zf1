<?php

class App_Controller_Plugin_LangSelector extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        $lang = $request->getParam('lang', '');
        //var_dump($request->getParams());
        //die("Language: " . $lang);

        if ($lang !== 'en' && $lang !== 'ru')
            $request->setParam('lang', 'ru');
        $lang = $request->getParam('lang');

        if ($lang == 'en')
            $locale = 'en_US';
        else
            $locale = 'ru_RU';

        $zl = new Zend_Locale();
        $zl->setLocale($locale);
        Zend_Registry::set('Zend_Locale', $zl);

        $translate = new Zend_Translate(
                        array(
                            'adapter' => 'gettext',
                            'content' => APPLICATION_PATH . '/languages/' . $lang . '.mo',
                            'locale' => $locale
                        )
        );
        
        Zend_Registry::set('Zend_Translate', $translate);
    }
}