<?php

class Application_Form_Role_Add extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/role/add');
        $this->setName('role_add');
        $this->setAttrib('class', 'form-role white_box white_box_size_m');
        
        $this->addElement('text', 'name', array(
            'label' => $this->translate('Название'),
            'filters' => array('StripTags', 'StringTrim'),
            'required' => true,
	    'placeholder' => $this->translate('Название'),
	    'class' => 'form-control  white_box_el_size_s',
	    'maxlength' => 255,
	    'validators' => array(
                'NotEmpty',
                new App_Validate_NoDbRecordExists('role', 'name')
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));
	
	// parent role
        $this->addElement('select', 'parent_role', array(
            'label' => $this->translate('Родительская роль'),
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
	
	$role_db = new Application_Model_DbTable_Role();
	
	$roles = $role_db->getAll(FALSE, "id, name");
	
	foreach ($roles as $role){
	    $this->parent_role->addMultiOptions(array(
		$role->id => $role->name
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

