<?php

class Application_Form_User_Settings extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        // Set the method for the display form to POST
        $this->setMethod('post')
		->setName('default-user-settings');
        
        $this->setAttribs(array(
	    'class' => 'block-item block-item-form',
	    'id' => 'default-user-settings',
	));
        
        // lang
        $this->addElement('select', 'lang', array(
            'label' => $this->translate('Язык'),
            'class' => 'form-control',
            'multiOptions' => array('Русский', 'English'),
            'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
        ));

        $this->addElement('password', 'oldpassword', array(
            'label' => $this->translate('Старый пароль'),
            'placeholder' => $this->translate('Старый пароль'),
            'data-title' => $this->translate('Введите старый пароль, чтобы подтвердить свою личность.'),
            'class' => 'form-control tooltip-field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('StringLength', true, array('min' => 6))
            ),
            'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
        ));

        $this->addElement('password', 'newpassword', array(
            'label' => $this->translate('Новый пароль'),
            'placeholder' => $this->translate('Новый пароль'),
            'data-title' => $this->translate('Длина поля должна быть от 6 до 25 символов, содержать только латиские буквы, цифры и символы -_.'),
            'class' => 'form-control tooltip-field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('StringLength', true, array('min' => 6))
            ),
            'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
        ));

        $this->addElement('password', 'confirmnewpassword', array(
            'label' => $this->translate('Подтвердите новый пароль'),
            'placeholder' => $this->translate('Подтвердите новый пароль'),
            'data-title' => $this->translate('Значение поля должно совпадать со значеним предыдущего поля.'),
            'AllowEmpty' => false,
            'class' => 'form-control tooltip-field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                //new App_Validate_EqualInputs('password')
            ),
            'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
        ));

        // element for saving tab name
        $this->addElement('hidden', 'tab_name', array(
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
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
	    )
	));

	$this->addElement('reset', 'reset', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'label' => $this->translate('Сбросить'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
	    )
	));
        
        // lang display group
        $this->addDisplayGroup(array(
            $this->getElement('lang')
                ), 'lang_settings', array('legend' => $this->translate('Языковые настройки')));

        $this->getDisplayGroup('lang_settings')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'lang_settings display_group')),
        ));

        // Change password
        $this->addDisplayGroup(array(
            $this->getElement('oldpassword'),
            $this->getElement('newpassword'),
            $this->getElement('confirmnewpassword'),
                ), 'change_password', array('legend' => $this->translate('Смена пароля')));

        $this->getDisplayGroup('change_password')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'lang_settings display_group')),
        ));

        // form actions display group
         $this->addDisplayGroup(array(
            $this->getElement('submit'),
            $this->getElement('reset')
                ), 'form_actions', array());
        
        $this->getDisplayGroup('form_actions')->setDecorators(array(
	    'FormElements',
	    //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
	    //'Fieldset',
	    array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'block-item-form-actions text-center clearfix')),
	));
    }

}