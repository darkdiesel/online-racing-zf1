<?php

class Application_Form_Event_Add extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/event/add');
        $this->setName('eventAdd');
        $this->setAttrib('class', 'white_box white_box_size_m');

        $this->addElement('text', 'name', array(
            'label' => $this->translate('Название'),
            'placeholder' => $this->translate('Название'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => true,
            'class' => 'x_field',
            'width' => '400px',
            'validators' => array(
                'NotEmpty',
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'date_event', array(
            'label' => $this->translate('Дата события'),
            'placeholder' => $this->translate('Дата события'),
            'description' => $this->translate('Формат даты должен быть yyyy-mm-dd hh:mm:ss') . '. ' . $this->translate('Пример') . ': ' . date('Y-m-d H:i:s'),
            'title' => $this->translate('Формат даты yyyy-mm-dd hh:mm:ss (yyyy - год, mm - месяц, dd - день, hh - часы (24), mm - минуты, ss - секунды)'),
            'required' => true,
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field tooltip_field',
            'validators' => array(
                //array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
                array('StringLength', true, array('min' => 19, 'max' => 19)),
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors', 'description',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'url_event', array(
            'label' => $this->translate('Ссылка на событие'),
            'placeholder' => $this->translate('Ссылка на событие'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => false,
            'class' => 'x_field',
            'width' => '400px',
            'validators' => array(
                'NotEmpty',
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('textarea', 'description', array(
            'label' => $this->translate('Описание события'),
            'placeholder' => $this->translate('Описание события'),
            'cols' => 60,
            'rows' => 10,
            'class' => 'white_box_el_size_m',
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
            'class' => 'btn',
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

