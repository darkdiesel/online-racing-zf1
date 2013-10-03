<?php

class Application_Form_Permission_Add extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/permission/add');
        $this->setName('permission_add');
        $this->setAttrib('class', 'form-permission white_box white_box_size_m');
        
	// parent role
        $this->addElement('select', 'role', array(
            'label' => $this->translate('Роль'),
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
	
	$role = new Application_Model_DbTable_Role();
	
	$roles = $role->getAll("id, name");
	
	foreach ($roles as $role){
	    $this->role->addMultiOptions(array(
		$role->id => $role->name
	    ));
	}
	
	// parent role
        $this->addElement('select', 'resource', array(
            'label' => $this->translate('Ресурс'),
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
	
	$resource_db = new Application_Model_DbTable_Resource();
	
	$resources = $resource_db->getAll("id, name");
	
	foreach ($resources as $resource){
	    $this->resource->addMultiOptions(array(
		$resource->id => $resource->name
	    ));
	}
	
        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => $this->translate('Добавить'),
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
        
        $this->addDisplayGroup(array(
            $this->getElement('submit'),
            $this->getElement('reset')
                ), 'form_actions', array());
        
        $this->getDisplayGroup('form_actions')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form_actions display_group')),
        ));
    }

}

