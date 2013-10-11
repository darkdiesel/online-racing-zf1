<?php

class Application_Form_User_Admin_Edit extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('admin/user/edit');
        $this->setName('userAdminEdit');
        $this->setAttrib('class', 'fieldset_white_box');
        $this->setEnctype('multipart/form-data');
	
	// user role
        $this->addElement('select', 'user_role', array(
            'label' => $this->translate('Роль пользователя'),
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
	    $this->user_role->addMultiOptions(array(
		$role->id => $role->name
	    ));
	}
	
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