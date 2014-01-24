<?php

class Application_Form_Right_Add extends Zend_Form {

    protected function translate($str) {
	$translate = new Zend_View_Helper_Translate();
	$lang = Zend_Registry::get('Zend_Locale');
	return $translate->translate($str, $lang);
    }

    public function init() {
	$this->setMethod('post')
		->setName('admin-right-add');

	$this->setAttribs(array(
	    'class' => 'block-item block-item-form',
	    'id' => 'admin-right-add',
	));

	// decorators for this form
	$this->addDecorators(array('formElements', 'form'));

	$this->addElement('text', 'name', array(
	    'label' => $this->translate('Название'),
	    'placeholder' => $this->translate('Название'),
	    'maxlength' => 255,
	    'filters' => array('StripTags', 'StringTrim'),
	    'required' => true,
	    'class' => 'form-control',
	    'validators' => array('NotEmpty'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('textarea', 'description', array(
	    'label' => $this->translate('Описание правила'),
	    'placeholder' => $this->translate('Описание правила'),
	    'cols' => 60,
	    'rows' => 10,
	    'class' => 'form-control',
	    'maxlength' => 500,
	    'required' => false,
	    'filters' => array('StringTrim'),
	    'validators' => array('NotEmpty'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('submit', 'submit', array(
	    'ignore' => true,
	    'class' => 'btn btn-primary',
	    'label' => $this->translate('Добавить'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('tag' => 'span', 'class' => 'block-center')),
	    )
	));

	$this->addElement('reset', 'reset', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'label' => $this->translate('Сбросить'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('tag' => 'span', 'class' => 'block-center')),
	    )
	));

	$this->addElement('button', 'cancel', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'label' => $this->translate('Отмена'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('tag' => 'span', 'class' => 'block-center')),
	    )
	));

	$this->addDisplayGroup(array(
	    $this->getElement('submit'),
	    $this->getElement('reset'),
	    $this->getElement('cancel'),
		), 'form_actions', array());

	$this->getDisplayGroup('form_actions')->setDecorators(array(
	    'FormElements',
	    //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
	    //'Fieldset',
	    array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'block-item-form-actions text-center clearfix')),
	));
    }

}

