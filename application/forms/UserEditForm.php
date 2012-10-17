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
            'filters' => array('StripTags', 'StringTrim'),
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
            'filters' => array('StripTags', 'StringTrim'),
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
            'filters' => array('StripTags', 'StringTrim'),
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
            'filters' => array('StripTags', 'StringTrim'),
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
            'filters' => array('StripTags', 'StringTrim'),
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
            'filters' => array('StripTags', 'StringTrim'),
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
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'gtalk', array(
            'label' => $this->translate('Google Talk'),
            'placeholder' => $this->translate('Google Talk'),
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'www', array(
            'label' => $this->translate('WWW'),
            'placeholder' => $this->translate('WWW'),
            'filters' => array('StripTags', 'StringTrim'),
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
            'placeholder' => $this->translate('Вконтакте'),
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'fb', array(
            'label' => $this->translate('FaceBook'),
            'placeholder' => $this->translate('FaceBook'),
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'tw', array(
            'label' => $this->translate('Twitter'),
            'placeholder' => $this->translate('Twitter'),
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'gp', array(
            'label' => $this->translate('Google+'),
            'placeholder' => $this->translate('Google+'),
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        // additional information
        $this->addElement('textarea', 'about', array(
            'label' => $this->translate('О себе'),
            'placeholder' => $this->translate('О себе').' ...',
            'cols' => 60,
            'rows' => 5,
            'maxlength' => 500,
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(0, 500))
                ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'aboutTextArea_Label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'aboutTextArea_box')),
            )
        ));
        
        // element for saving tab name
        $this->addElement('hidden','tab_name', array(
            'value' => '',
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('class' => 'tab_name hidden_group'))
            )
        ));

        // conrols
        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => $this->translate('Сохранить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'span', 'class' => 'submit form_actions_group'))
            )
        ));

        $this->addElement('reset', 'reset', array(
            'label' => "",
            'ignore' => true,
            'class' => 'btn',
            'label' => $this->translate('Сбросить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'span', 'class' => 'reset form_actions_group'))
            )
        ));

        $this->addDisplayGroup(array(
            $this->getElement('name'),
            $this->getElement('surname'),
            $this->getElement('birthday'),
            $this->getElement('country'),
            $this->getElement('city')
                ), 'personal_Inf', array('legend' => $this->translate('Личные данные')));

        $this->getDisplayGroup('personal_Inf')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'personal_Inf display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('lang')
                ), 'lang_settings', array('legend' => $this->translate('Языковые настройки')));

        $this->getDisplayGroup('lang_settings')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'lang_settings display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('skype'),
            $this->getElement('icq'),
            $this->getElement('gtalk'),
            $this->getElement('www')
                ), 'contacts_Inf', array('legend' => $this->translate('Контактноя информация')));

        $this->getDisplayGroup('contacts_Inf')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'contacts_Inf display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('vk'),
            $this->getElement('fb'),
            $this->getElement('tw'),
            $this->getElement('gp')
                ), 'social_netwoks', array('legend' => $this->translate('Социальные сети')));

        $this->getDisplayGroup('social_netwoks')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'social_netwoks display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('about')
                ), 'additional_Inf', array('legend' => $this->translate('Дополнительная информация')));

        $this->getDisplayGroup('additional_Inf')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'additional_Inf display_group')),
        ));

        $this->addDisplayGroup(array(
            $this->getElement('submit'),
            $this->getElement('reset')
                ), 'form_actions', array());

        $this->getDisplayGroup('form_actions')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form_actions display_group')),
        ));
    }

}

