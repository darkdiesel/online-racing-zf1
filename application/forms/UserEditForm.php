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
            'placeholder' => $this->translate('Имя'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'surname', array(
            'label' => $this->translate('Фамилия'),
            'placeholder' => $this->translate('Фамилия'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'birthday', array(
            'label' => $this->translate('Дата рождения'),
            'placeholder' => $this->translate('Дата рождения'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'country', array(
            'label' => $this->translate('Страна'),
            'placeholder' => $this->translate('Страна'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'city', array(
            'label' => $this->translate('Город'),
            'placeholder' => $this->translate('Город'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        // lang
        $this->addElement('select', 'lang', array(
            'label' => $this->translate('Язык'),
            'multiOptions' => array('Русский', 'English'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        //contacts information
        $this->addElement('text', 'skype', array(
            'label' => $this->translate('Skype'),
            'placeholder' => $this->translate('Skype'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'icq', array(
            'label' => $this->translate('ICQ'),
            'placeholder' => $this->translate('ICQ'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'mail', array(
            'label' => $this->translate('E-mail'),
            'placeholder' => $this->translate('E-mail'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'www', array(
            'label' => $this->translate('www'),
            'placeholder' => $this->translate('www'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
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
                ), 'personalInf', array('legend' => $this->translate('Личные данные')));
        
        $this->getDisplayGroup('personalInf')->setDecorators(array(
                'FormElements',
                array(array('innerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'fieldset_inner_form')),
                'Fieldset',
                array(array('outerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'personalInf display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('lang')
                ), 'langsettings', array('legend' => $this->translate('Языковые настройки')));

        $this->getDisplayGroup('langsettings')->setDecorators(array(
            'FormElements',
                array(array('innerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'fieldset_inner_form')),
                'Fieldset',
                array(array('outerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'langsettings display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('skype'),
            $this->getElement('icq'),
            $this->getElement('mail'),
            $this->getElement('www')
                ), 'contactsInf', array('legend' => $this->translate('Контактноя информация')));

        $this->getDisplayGroup('contactsInf')->setDecorators(array(
            'FormElements',
                array(array('innerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'fieldset_inner_form')),
                'Fieldset',
                array(array('outerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'contactsInf display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('vk'),
            $this->getElement('fb'),
            $this->getElement('tw'),
            $this->getElement('gp')
                ), 'socialnetwoks', array('legend' => $this->translate('Социальные сети')));

        $this->getDisplayGroup('socialnetwoks')->setDecorators(array(
            'FormElements',
                array(array('innerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'fieldset_inner_form')),
                'Fieldset',
                array(array('outerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'socialnetwoks display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('about')
                ), 'additionalInf', array('legend' => $this->translate('Дополнительная информация')));

        $this->getDisplayGroup('additionalInf')->setDecorators(array(
            'FormElements',
                array(array('innerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'fieldset_inner_form')),
                'Fieldset',
                array(array('outerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'additionalInf display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('submit'),
            $this->getElement('reset')
                ), 'formactions', array('legend' => $this->translate('Сохранить')." / ".$this->translate('Сбросить')." ".$this->translate('изминения')));

        $this->getDisplayGroup('formactions')->setDecorators(array(
            'FormElements',
                array(array('innerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'fieldset_inner_form')),
                'Fieldset',
                array(array('outerHtmlTag'=>'HtmlTag'),array('tag'=>'div', 'class' => 'formactions display_group')),
        ));
    }

}

