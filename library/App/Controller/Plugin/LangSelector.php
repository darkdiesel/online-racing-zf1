<?php

class App_Controller_Plugin_LangSelector extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        $lang = $request->getParam('lang', '');
        //var_dump($request->getParams());
        //die("Language: " . $lang);
        
        if (strtolower($lang)!== 'en' || $lang !== 'ru')
            $request->setParam ('lang', 'ru');
    }

}