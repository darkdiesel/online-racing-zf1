<?php

class Application_Form_Race_Add extends Zend_Form {

	protected function translate($str) {
		$translate = new Zend_View_Helper_Translate();
		$lang = Zend_Registry::get('Zend_Locale');
		return $translate->translate($str, $lang);
	}

	public function init() {
		$this->setMethod('post')
				->setName('default-race-add');

		$this->setAttribs(array(
			'class' => 'block-item block-item-form',
			'id' => 'default-race-add',
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
			'validators' => array(
				'NotEmpty',
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('text', 'race_number', array(
			'label' => $this->translate('Номер гонки'),
			'placeholder' => $this->translate('Номер гонки'),
			'maxlength' => 255,
			'filters' => array('StripTags', 'StringTrim'),
			'required' => true,
			'class' => 'form-control',
			'validators' => array(
				'NotEmpty',
				'Int',
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));
		
		$this->addElement('text', 'race_date', array(
			'label' => $this->translate('Дата гонки'),
			'placeholder' => $this->translate('Дата гонки'),
			'description' => $this->translate('Формат даты должен быть yyyy-mm-dd hh:mm:ss') . '. ' . $this->translate('Пример') . ': ' . date('Y-m-d H:i:s'),
			'title' => $this->translate('Формат даты yyyy-mm-dd hh:mm:ss (yyyy - год, mm - месяц, dd - день, hh - часы (24), mm - минуты, ss - секунды)'),
			'required' => true,
			'filters' => array('StripTags', 'StringTrim'),
			'class' => 'form-control tooltip-field',
			'validators' => array(
				//array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
				array('StringLength', true, array('min' => 19, 'max' => 19)),
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));
		
		$this->addElement('text', 'race_laps', array(
			'label' => $this->translate('Количество кругов в гонке'),
			'placeholder' => $this->translate('Количество кругов в гонке'),
			'maxlength' => 255,
			'filters' => array('StripTags', 'StringTrim'),
			'required' => false,
			'class' => 'form-control',
			'validators' => array(
				'NotEmpty',
				'Int',
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		// artcile type
		$this->addElement('select', 'championship', array(
			'label' => $this->translate('Чемпионат'),
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

		// artcile type
		$this->addElement('select', 'track', array(
			'label' => $this->translate('Трасса для гонки'),
			//'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
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

		$this->addElement('textarea', 'description', array(
			'label' => $this->translate('Описание гонки'),
			'placeholder' => $this->translate('Описание гонки'),
			'cols' => 60,
			'rows' => 10,
			'maxlength' => 65535,
			'class' => 'form-control',
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
			'label' => $this->translate('Создать'),
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
