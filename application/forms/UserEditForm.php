<?php

class Application_Form_UserEditForm extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/user/edit');
        $this->setName('userEdit');
        $this->setAttrib('class', 'white_box');

        // user info
        $this->addElement('text', 'name', array(
            'label' => $this->translate('Имя'),
            'class' => 'x_field',
        ));

        $this->addElement('text', 'surname', array(
            'label' => $this->translate('Фамилия'),
            'class' => 'x_field',
        ));

        $this->addElement('text', 'birthday', array(
            'label' => $this->translate('Дата рождения'),
            'class' => 'x_field',
        ));

        $this->addElement('text', 'country', array(
            'label' => $this->translate('Страна'),
            'class' => 'x_field',
        ));

        $this->addElement('text', 'city', array(
            'label' => $this->translate('Город'),
            'class' => 'x_field',
        ));
        
        // lang
        $this->addElement('text', 'lang', array(
            'label' => $this->translate('Язык'),
            'class' => 'x_field',
        ));

        //contacts
        $this->addElement('text', 'skype', array(
            'label' => $this->translate('Skype'),
            'class' => 'x_field',
        ));

        $this->addElement('text', 'icq', array(
            'label' => $this->translate('ICQ'),
            'class' => 'x_field',
        ));

        $this->addElement('text', 'mail', array(
            'label' => $this->translate('E-mail'),
            'class' => 'x_field',
        ));

        $this->addElement('text', 'www', array(
            'label' => $this->translate('www'),
            'class' => 'x_field',
        ));
        
        // social networks
        $this->addElement('text', 'vk', array(
            'label' => $this->translate('Вконтакте'),
            'class' => 'x_field',
        ));
        
        $this->addElement('text', 'fb', array(
            'label' => $this->translate('FaceBook'),
            'class' => 'x_field',
        ));
        
        $this->addElement('text', 'tw', array(
            'label' => $this->translate('Twitter'),
            'class' => 'x_field',
        ));
        
        $this->addElement('text', 'gp', array(
            'label' => $this->translate('Google+'),
            'class' => 'x_field',
        ));
        
        // additional information
        $this->addElement('textarea', 'about', array(
            'label' => $this->translate('О себе'),
            'class' => 'x_field',
        ));

        // conrols
        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => $this->translate('Сохранить'),
        ));

        $this->addElement('reset', 'reset', array(
            'label' => "",
            'ignore' => true,
            'class' => 'btn',
            'label' => $this->translate('Сбросить'),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('name'),
            $this->getElement('surname'),
            $this->getElement('birthday'),
            $this->getElement('country'),
            $this->getElement('city')
                ), 'contact', array('legend' => $this->translate('Личные данные')));
        
        $this->addDisplayGroup(array(
            $this->getElement('lang')
                ), 'langsettings', array('legend' => $this->translate('Языковые настройки')));

        $this->addDisplayGroup(array(
            $this->getElement('skype'),
            $this->getElement('icq'),
            $this->getElement('mail'),
            $this->getElement('www')
                ), 'contacts', array('legend' => $this->translate('Контактноя информация')));
        
        $this->addDisplayGroup(array(
            $this->getElement('vk'),
            $this->getElement('fb'),
            $this->getElement('tw'),
            $this->getElement('gp')
                ), 'contacts', array('legend' => $this->translate('Социальные сети')));
        
        $this->addDisplayGroup(array(
            $this->getElement('about')
                ), 'additionalInf', array('legend' => $this->translate('Дополнительная информация')));

        $this->addDisplayGroup(array(
            $this->getElement('submit'),
            $this->getElement('reset')
                ), 'controls', array());
    }

}

