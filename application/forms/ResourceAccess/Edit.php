<?php

class Application_Form_ResourceAccess_Edit extends Zend_Form {

	protected function translate($str) {
		$translate = new Zend_View_Helper_Translate();
		$lang = Zend_Registry::get('Zend_Locale');
		return $translate->translate($str, $lang);
	}

	public function init() {
		$this->setMethod('post')
				->setName('admin-resource-access-add');

		$this->setAttribs(array(
			'class' => 'block-item block-item-form',
			'id' => 'admin-resource-access-add',
		));

		// decorators for this form
		$this->addDecorators(array('formElements', 'form'));

		// role list
		$this->addElement('select', 'role', array(
			'label' => $this->translate('Роль'),
			'multiOptions' => array('' => ''),
			'required' => true,
			'class' => 'form-control',
			'registerInArrayValidator' => false,
			'validators' => array('NotEmpty'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$role_db = new Application_Model_DbTable_Role();
		$roles_data = $role_db->getAll(FALSE, array("id", "name"));

		if ($roles_data) {
			foreach ($roles_data as $role) {
				$this->role->addMultiOptions(array(
					$role->id => $role->name
				));
			}
		}

		// resource list
		$this->addElement('select', 'resource', array(
			'label' => $this->translate('Ресурс'),
			'multiOptions' => array('' => ''),
			'required' => true,
			'class' => 'form-control',
			'registerInArrayValidator' => false,
			'validators' => array('NotEmpty'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$resource_db = new Application_Model_DbTable_Resource();
		$resources_data = $resource_db->getAll(FALSE, array("id", "name"));

		if ($resources_data) {
			foreach ($resources_data as $resource) {
				$this->resource->addMultiOptions(array(
					$resource->id => $resource->name
				));
			}
		}

		// privilege list
		$this->addElement('select', 'privilege', array(
			'label' => $this->translate('Привилегия'),
			'multiOptions' => array('' => ''),
			'required' => false,
			'class' => 'form-control',
			'registerInArrayValidator' => false,
			'validators' => array('NotEmpty'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$privilege_db = new Application_Model_DbTable_Privilege();
		$privileges_data = $privilege_db->getAll(FALSE, array("id", "name"));

		if ($privileges_data) {
			foreach ($privileges_data as $privilege) {
				$this->privilege->addMultiOptions(array(
					$privilege->id => $privilege->name
				));
			}
		}
		
		$this->addElement('checkbox', 'allow', array(
			'label' => $this->translate('Разрешить доступ?'),
			'data-title' => $this->translate('Отметьте поле, если нужно дать доступ и оставьте пустым в противном случае.'),
			'data-placeholder' => 'left',
			'class' => 'tooltip-field',
			'data-placement' => 'bottom',
			'decorators' => array(array('ViewScript', array(
						'viewScript' => 'viewScript/form_checkbox_bootstrap3.phtml'
					)))
		));

		$this->addElement('submit', 'submit', array(
			'ignore' => true,
			'class' => 'btn btn-primary',
			'label' => $this->translate('Изменить'),
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

		$this->addElement('button', 'cancel', array(
			'ignore' => true,
			'class' => 'btn btn-default',
			'label' => $this->translate('Отмена'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
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
