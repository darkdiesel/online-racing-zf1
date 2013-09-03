<?php

class Application_Form_Championship_Edit extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/championship/edit');
        $this->setName('championshipEdit');
        $this->setAttrib('class', 'white_box white_box_size_xl');

        $this->addElement('text', 'name', array(
            'label' => $this->translate('Название'),
            'placeholder' => $this->translate('Название'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => true,
            'class' => 'form-control white_box_el_size_l',
            'validators' => array(
                'NotEmpty',
                //new App_Validate_NoDbRecordExists('championship', 'name')
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('file', 'logo', array(
            'label' => $this->translate('Логотип чемпионата'),
            'required' => false,
            'height' => '30px',
            'class' => 'white_box_el_size_m',
            'destination' => APPLICATION_PATH . '/../public_html/data-uploads/championship/logo/',
            'decorators' => array(
                'File', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            ),
            'validators' => array(
                array('Size', false, 5120000),
                array('Extension', false, 'jpg,png,gif'),
                array('Count', false, 1)
            )
        ));

        // artcile type
        $this->addElement('select', 'league', array(
            'label' => $this->translate('Лига'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
            'class' => 'form-control white_box_el_size_m',
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        // artcile type
        $this->addElement('select', 'rule', array(
            'label' => $this->translate('Регламент'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
            'class' => 'form-control white_box_el_size_m',
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        // artcile type
        $this->addElement('select', 'game', array(
            'label' => $this->translate('Игра для проведения'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
            'class' => 'form-control white_box_el_size_m',
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));
        // artcile type
        $this->addElement('select', 'admin', array(
            'label' => $this->translate('Администратор чемпионата'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
            'class' => 'form-control white_box_el_size_m',
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));


        $this->addElement('text', 'date_start', array(
            'label' => $this->translate('Дата начала'),
            'placeholder' => $this->translate('Дата начала'),
            'description' => $this->translate('Формат даты yyyy-mm-dd (yyyy - год, mm - месяц, dd - день)'),
            'required' => true,
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'form-control',
            'validators' => array(
                array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
                array('StringLength', true, array('min' => 10, 'max' => 10)),
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors', 'description',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'date_end', array(
            'label' => $this->translate('Дата окончания'),
            'placeholder' => $this->translate('Дата окончания'),
            'description' => $this->translate('Формат даты yyyy-mm-dd (yyyy - год, mm - месяц, dd - день)'),
            'required' => true,
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'form-control',
            'validators' => array(
                array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
                array('StringLength', true, array('min' => 10, 'max' => 10)),
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors', 'description',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));
	
	$this->addElement('text', 'hotlap_ip', array(
            'label' => $this->translate('Hot-Laps IP'),
            'placeholder' => $this->translate('Hot-Laps IP'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => false,
            'class' => 'form-control white_box_el_size_l',
            'validators' => array(
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('textarea', 'description', array(
            'label' => $this->translate('Описание чемпионата'),
            'placeholder' => $this->translate('Описание чемпионата'),
            'cols' => 60,
            'rows' => 10,
            'class' => 'form-control white_box_el_size_xl',
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
            'label' => $this->translate('Редактировать'),
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
            'onClick' => "location.href='/championship/all'",
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

