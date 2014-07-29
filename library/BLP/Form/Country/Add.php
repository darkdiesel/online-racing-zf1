<?php

class BLP_Form_Country_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public $formDecorators = array(
        array('FormErrors'),
        array('FormElements'),
        array('Form')
    );

    public $elementDecorators = array(
        array('ViewHelper'),
        //array('HtmlTag', array('tag' => 'div', 'class' => '')),
        array('Label', array('class' => 'control-label')),
        array('Errors'),
        array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),

    );

    public $fileDecorators = array(
        array('File'),
        //array('HtmlTag', array('tag' => 'div', 'class' => '')),
        array('Label', array('class' => 'control-label')),
        array('Errors'),
        array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),

    );

    public function init()
    {
        $this->setAttribs(
            array(
                'class' => 'block-item block-item-form',
                'id' => 'admin-country-add'
            )
        )
            ->setName('adminCountryAdd')
            ->setAction(
                $this->getView()->url(array('module' => 'admin', 'controller' => 'country', 'action' => 'add'), 'default')
            )
            ->setMethod('post')
            ->addDecorators($this->formDecorators);

        $nativeName = new Zend_Form_Element_Text('NativeName');
        $nativeName->setLabel($this->translate('Родное название'));
        $nativeName->setOptions(array('maxLength' => 255,'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Родное название'))
            ->setRequired(true)
            ->addValidator('NotEmpty', true)
            ->addValidator('stringLength', true,
                array(0, 255)
            )
            ->addValidator(
                'Db_NoRecordExists', true,
                array(
                    'table' => 'country',
                    'field' => 'NativeName',
                )
            )
            //->addValidator(new App_Validate_NoDbRecordExists('country', 'NativeName'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addFilter(new App_Filter_Upper())
            ->setDecorators($this->elementDecorators);

        $englishName = new Zend_Form_Element_Text('EnglishName');
        $englishName->setLabel($this->translate('Английское название'));
        $englishName->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Английское название'))
            ->setRequired(true)
            ->addValidator('NotEmpty', true)
            ->addValidator('stringLength', true,
                array(0, 255)
            )
            ->addValidator(
                'Db_NoRecordExists', true,
                array(
                    'table' => 'country',
                    'field' => 'EnglishName',
                )
            )
            //->addValidator(new App_Validate_NoDbRecordExists('country', 'EnglishName'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addFilter(new App_Filter_Upper())
            ->setDecorators($this->elementDecorators);

        $abbreviation = new Zend_Form_Element_Text('Abbreviation');
        $abbreviation->setLabel($this->translate('Аббревиатура'));
        $abbreviation->setOptions(array('maxLength' => 5, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Аббревиатура'))
            ->setRequired(true)
            ->addValidator('NotEmpty', true)
            ->addValidator('stringLength', true,
                array(0, 5)
            )
            ->addValidator(
                'Db_NoRecordExists', true,
                array(
                    'table' => 'country',
                    'field' => 'Abbreviation',
                )
            )
            //->addValidator(new App_Validate_NoDbRecordExists('country', 'Abbreviation'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addFilter(new App_Filter_Upper())
            ->setDecorators($this->elementDecorators);

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

        $this->addElement($nativeName)
            ->addElement($englishName)
            ->addElement($abbreviation);

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
