<?php

class Application_Form_User_Login extends Zend_Form
{

    protected function translate($str)
    {
	$translate = new Zend_View_Helper_Translate();
	$lang = Zend_Registry::get('Zend_Locale');
	return $translate->translate($str, $lang);
    }

    public function init()
    {
	// Set the method for the display form to POST
	$this->setMethod('post');
	$this->setAction('/user/login');
	$this->setName('userlogin');
	$this->setAttrib('class', 'block_item block-item-form block-item-form-w-270 align-center-block');

	$this->addDecorators(array('formElements', 'form'));

	// Add an email element
	$this->addElement('text', 'loginemail', array(
	    'label' => 'E-mail:',
	    'placeholder' => 'E-mail',
	    'title' => $this->translate('Введите свой электронный почтовый ящик. Пример: example@mail.com.'),
	    'required' => true,
	    'class' => 'tooltip_field form-control',
	    'data-placement' => 'bottom',
	    'filters' => array('StripTags', 'StripTags', 'StringTrim', 'StringToLower'),
	    'maxlength' => 255,
	    'validators' => array(
		'EmailAddress',
		array('StringLength', true, array('min' => 5, 'max' => 255)),
		new App_Validate_DbRecordExists('user', 'email')
	    ),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('password', 'loginpassword', array(
	    'label' => $this->translate('Пароль'),
	    'placeholder' => $this->translate('Пароль'),
	    'title' => $this->translate('Введите пароль от своей учетной записи.'),
	    'required' => true,
	    'class' => 'tooltip_field form-control',
	    'data-placement' => 'bottom',
	    'filters' => array('StripTags', 'StringTrim'),
	    'maxlength' => 25,
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

	/* $this->addElement('checkbox', 'remember', array(
	  'label' => $this->translate('Запомнить меня'),
	  'title' => $this->translate('Отметьте поле, чтобы не авторизовываться при следующем посещении сайта.'),
	  'data-placeholder' => 'left',
	  'class' => 'tooltip_field',
	  'data-placement' => 'bottom',
	  'decorators' => array(
	  'ViewHelper', 'HtmlTag', 'Errors',
	  array('label', array('class' => '','placement' => 'PREPEND')),
	  array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
	  array('HtmlTag', array('tag' => 'div', 'class' => 'checkbox1')),
	  )
	  ));
	  $this->getElement('remember')->getDecorator('label')->setOptions(array('placement' => 'APPEND')); */

	$this->addElement('checkbox', 'remember', array(
	    'label' => $this->translate('Запомнить меня'),
	    'title' => $this->translate('Отметьте поле, чтобы не авторизовываться при следующем посещении сайта.'),
	    'data-placeholder' => 'left',
	    'class' => 'tooltip_field',
	    'data-placement' => 'bottom',
	    'decorators' => array(array('ViewScript', array(
			'viewScript' => 'form_elements/bootstrap3_checkbox.phtml'
		    )))
	));

	$this->addElement('submit', 'submit', array(
	    'ignore' => true,
	    'class' => 'btn btn-primary',
	    'label' => $this->translate('Войти'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group col-lg-offset-0 col-lg-5 pull-left')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('reset', 'reset', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'label' => $this->translate('Сбросить'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group col-lg-offset-0 col-lg-5 pull-right')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addDisplayGroup(array(
	    $this->getElement('submit'),
	    $this->getElement('reset')
		), 'form_actions', array());

	$this->getDisplayGroup('form_actions')->setDecorators(array(
	    'FormElements',
	    //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
//	    /'Fieldset',
	    array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'clearfix')),
	));
    }

}