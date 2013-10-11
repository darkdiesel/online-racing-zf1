<?php

class Application_Form_Country_Add extends Zend_Form
{

    protected function translate($str)
    {
	$translate = new Zend_View_Helper_Translate();
	$lang = Zend_Registry::get('Zend_Locale');
	return $translate->translate($str, $lang);
    }

    public function init()
    {
	$this->setMethod('post')
		->setName('admin-country-add');

	$this->setAttribs(array(
	    'class' => 'block-item block-item-form',
	    'id' => 'admin-country-add',
	));

	$this->addElement('text', 'native_name', array(
	    'label' => $this->translate('Родное название'),
	    'placeholder' => $this->translate('Родное название'),
	    'maxlength' => 255,
	    'filters' => array('StripTags', 'StringTrim', new App_Filter_Upper()),
	    'required' => true,
	    'class' => 'form-control',
	    'validators' => array(
		'NotEmpty',
		new App_Validate_NoDbRecordExists('country', 'native_name')
	    ),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('text', 'english_name', array(
	    'label' => $this->translate('Английское название'),
	    'placeholder' => $this->translate('Английское название'),
	    'maxlength' => 255,
	    'filters' => array('StripTags', 'StringTrim', new App_Filter_Upper()),
	    'required' => true,
	    'class' => 'form-control',
	    'validators' => array(
		'NotEmpty',
		new App_Validate_NoDbRecordExists('country', 'english_name')
	    ),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('text', 'abbreviation', array(
	    'label' => $this->translate('Аббревиатура'),
	    'placeholder' => $this->translate('Аббревиатура'),
	    'maxlength' => 5,
	    'filters' => array('StripTags', 'StringTrim', new App_Filter_AllToUpper()),
	    'required' => true,
	    'class' => 'form-control',
	    'validators' => array(
		'NotEmpty',
		new App_Validate_NoDbRecordExists('country', 'abbreviation')
	    ),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('file', 'image_round', array(
	    'label' => $this->translate('Круговая картинка флага (32х24)'),
	    'required' => true,
	    'class' => "form-control",
	    'destination' => APPLICATION_PATH . '/../public_html/data-content/data-uploads/flags/',
	    'validators' => array(
		array('Size', false, 102400),
		array('Extension', false, 'jpg,png,gif'),
		array('Count', false, 1)
	    ),
	    'decorators' => array(
		'File', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'control-label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('class' => '')),
	    )
	));

	$this->addElement('file', 'image_glossy_wave', array(
	    'label' => $this->translate('Волнистая картинка флага (64х48)'),
	    'required' => true,
	    'class' => "form-control",
	    'destination' => APPLICATION_PATH . '/../public_html/data-content/data-uploads/flags/',
	    'validators' => array(
		array('Size', false, 102400),
		array('Extension', false, 'jpg,png,gif')
	    ),
	    'decorators' => array(
		'File', 'HtmlTag', 'label', 'Errors',
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
		array('HtmlTag', array('tag' => 'span', 'class' => 'block-align-center')),
	    )
	));

	$this->addElement('reset', 'reset', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'label' => $this->translate('Сбросить'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('tag' => 'span', 'class' => 'block-align-center')),
	    )
	));

	$this->addElement('button', 'cancel', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'label' => $this->translate('Отмена'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
		array('HtmlTag', array('tag' => 'span', 'class' => 'block-align-center')),
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
	    array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'block-item-form-actions text-align-center clearfix')),
	));
    }

}

