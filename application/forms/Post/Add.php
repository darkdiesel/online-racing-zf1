<?php

class Application_Form_Post_Add extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/post/add');
        $this->setName('postAdd');
        $this->setAttrib('class', 'white_box white_box_size_max');

        $this->addElement('text', 'title', array(
            'label' => $this->translate('Заголовок'),
            'placeholder' => $this->translate('Заголовок'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => true,
            'class' => 'form-control white_box_el_size_xxl',
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        // artcile type
        $this->addElement('select', 'article_type', array(
            'label' => $this->translate('Тип статьи'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
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
        
        // artcile type
        $this->addElement('select', 'content_type', array(
            'label' => $this->translate('Тип контента'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
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

        $this->addElement('text', 'image', array(
            'label' => $this->translate('Изображение'),
            'placeholder' => $this->translate('Изображение'),
	    'class' => 'form-control',
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'form-control white_box_el_size_l',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));
        
        $this->addElement('textarea', 'annotation', array(
            'label' => $this->translate('Аннотация статьи'),
            'placeholder' => $this->translate('Аннотация статьи'),
            'cols' => 60,
            'rows' => 10,
            'class' => 'form-control white_box_el_size_xxl',
            'maxlength' => 250,
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'aboutTextArea_Label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box textTextArea_box')),
            )
        ));

        $this->addElement('textarea', 'text', array(
            'label' => $this->translate('Текст статьи'),
            'placeholder' => $this->translate('Текст статьи'),
            'cols' => 60,
            'rows' => 10,
            'class' => 'form-control white_box_el_size_xxl',
            'maxlength' => 50000,
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'aboutTextArea_Label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box textTextArea_box')),
            )
        ));

        $this->addElement('checkbox', 'publish', array(
            'label' => $this->translate('Опубликовать?'),
            'value' => 1,
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box checkbox')),
                array('HtmlTag', array('tag' => 'span', 'class' => 'element_tag')),
            )
        ));
        
        $this->addElement('checkbox', 'publish_to_slider', array(
            'label' => $this->translate('Опубликовать в слайдер?'),
            'value' => 0,
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box checkbox')),
                array('HtmlTag', array('tag' => 'span', 'class' => 'element_tag')),
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