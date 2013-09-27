<?php

class Application_Form_Resource_Edit extends Zend_Form
{

    protected function translate($str)
    {
	$translate = new Zend_View_Helper_Translate();
	$lang = Zend_Registry::get('Zend_Locale');
	return $translate->translate($str, $lang);
    }

    public function init()
    {
	$this->setMethod('post');
	$this->setAction('/resource/edit');
	$this->setName('resource_edit');
	$this->setAttrib('class', 'form-resource white_box white_box_size_m');

	$this->addElement('text', 'name', array(
	    'label' => $this->translate('Название'),
	    'filters' => array('StripTags', 'StringTrim'),
	    'required' => false,
	    'placeholder' => $this->translate('Название'),
	    'class' => 'form-control  white_box_el_size_s',
	    'readonly' => 'readonly',
	    'maxlength' => 255,
	    'validators' => array('NotEmpty'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'element_label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
		array('HtmlTag', array('class' => 'element_tag')),
	    )
	));
	
	$this->addElement('text', 'module', array(
            'label' => $this->translate('Модуль'),
            'placeholder' => $this->translate('Модуль'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => false,
            'class' => 'form-control  white_box_el_size_s',
            'validators' => array(
                'NotEmpty'
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

	$this->addElement('text', 'controller', array(
	    'label' => $this->translate('Контроллер'),
	    'placeholder' => $this->translate('Контроллер'),
	    'maxlength' => 255,
	    'filters' => array('StripTags', 'StringTrim'),
	    'required' => false,
	    'class' => 'form-control  white_box_el_size_s',
	    'validators' => array(
		'NotEmpty'
	    ),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'element_label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
		array('HtmlTag', array('class' => 'element_tag')),
	    )
	));

	$this->addElement('text', 'action', array(
	    'label' => $this->translate('Дейсвие'),
	    'placeholder' => $this->translate('Дейсвие'),
	    'maxlength' => 255,
	    'filters' => array('StripTags', 'StringTrim'),
	    'required' => false,
	    'class' => 'form-control  white_box_el_size_s',
	    'validators' => array(
		'NotEmpty'
	    ),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'element_label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
		array('HtmlTag', array('class' => 'element_tag')),
	    )
	));
	
	// parent resource
	$this->addElement('select', 'parent_resource', array(
            'label' => $this->translate('Родительский ресурс'),
            'multiOptions' => array('' => ''),
            'required' => false,
	    'class' => 'form-control',
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

	$resource = new Application_Model_DbTable_Resource();
	
	$resources = $resource->getAll("id, name");
	
	foreach ($resources as $resource){
	    $this->parent_resource->addMultiOptions(array(
		$resource->id => $resource->name
	    ));
	}

	$this->addElement('textarea', 'description', array(
	    'label' => $this->translate('Описание ресурса'),
	    'placeholder' => $this->translate('Описание ресурса'),
	    'cols' => 60,
	    'rows' => 10,
	    'class' => 'form-control white_box_el_size_m',
	    'maxlength' => 500,
	    'required' => false,
	    'filters' => array('StringTrim'),
	    'validators' => array('NotEmpty'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag', 'label', 'Errors',
		array('Label', array('class' => 'aboutTextArea_Label')),
		array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box textTextArea_box')),
	    )
	));

	$this->addElement('submit', 'submit', array(
	    'ignore' => true,
	    'class' => 'btn btn-primary',
	    'label' => $this->translate('Изменить'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array('HtmlTag', array('tag' => 'div', 'class' => 'submit form_actions_group'))
	    )
	));

	$this->addElement('reset', 'reset', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'label' => $this->translate('Сбросить'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array('HtmlTag', array('tag' => 'div', 'class' => 'reset form_actions_group'))
	    )
	));

	$this->addElement('button', 'cancel', array(
	    'ignore' => true,
	    'class' => 'btn btn-default',
	    'onClick' => "location.href='admin/resource/all'",
	    'label' => $this->translate('Отмена'),
	    'decorators' => array(
		'ViewHelper', 'HtmlTag',
		array('HtmlTag', array('tag' => 'div', 'class' => 'cancel form_actions_group'))
	    )
	));

	$this->addDisplayGroup(array(
	    $this->getElement('submit'),
	    $this->getElement('reset'),
	    $this->getElement('cancel'),
		), 'form_actions', array());

	$this->getDisplayGroup('form_actions')->setDecorators(array(
	    'FormElements',
	    array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
	    'Fieldset',
	    array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form_actions display_group')),
	));
    }

}

