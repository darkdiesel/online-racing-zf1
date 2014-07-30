<?php

class Peshkov_Form_Country_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public $formDecorators
        = array(
            array('FormErrors'),
            array('FormElements'),
            array('Form')
        );

    public $elementDecorators
        = array(
            array('ViewHelper'),
            //array('HtmlTag', array('tag' => 'div', 'class' => '')),
            array('Label', array('class' => 'control-label')),
            array('Errors'),
            array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),

        );

    public $buttonDecorators
        = array(
            array('ViewHelper'),
            array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
            array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
        );

    public $fileDecorators
        = array(
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
                 'id'    => 'admin-country-add'
            )
        )
            ->setName('adminCountryAdd')
            ->setAction(
                $this->getView()->url(
                    array('module' => 'admin', 'controller' => 'country', 'action' => 'add'), 'default'
                )
            )
            ->setMethod('post')
            ->addDecorators($this->formDecorators);

        $nativeName = new Zend_Form_Element_Text('NativeName');
        $nativeName->setLabel($this->translate('Родное название'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Родное название'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(0, 255, 'UTF-8'))
            ->addValidator(
                'Db_NoRecordExists', false,
                array(
                     'table' => 'country',
                     'field' => 'NativeName',
                )
            )
            //->addValidator(new App_Validate_NoDbRecordExists('country', 'NativeName'))
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter(new App_Filter_Upper())
            ->setDecorators($this->elementDecorators);

        $englishName = new Zend_Form_Element_Text('EnglishName');
        $englishName->setLabel($this->translate('Английское название'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Английское название'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(0, 255, 'UTF-8'))
            ->addValidator(
                'Db_NoRecordExists', false,
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
        $abbreviation->setLabel($this->translate('Аббревиатура'))
            ->setOptions(array('maxLength' => 5, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Аббревиатура'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(0, 5, 'UTF-8'))
            ->addValidator(
                'Db_NoRecordExists', false,
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

        $urlImageRound = new Zend_Form_Element_File('UrlImageRound');
        $urlImageRound->setLabel($this->translate('Круговая картинка флага (32х24)'))
            ->setAttrib('class', 'form-control')
            ->setRequired(true)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/flags/')
            ->addValidator('Size', false, 102400)
            ->addValidator('Extension', false, 'jpg,png,gif')
            ->addValidator('IsImage')
            ->addValidator('Count', false, 1)
            ->setDecorators($this->fileDecorators);

        $urlImageGlossyWave = new Zend_Form_Element_File('UrlImageGlossyWave');
        $urlImageGlossyWave->setLabel($this->translate('Волнистая картинка флага (64х48)'))
            ->setAttrib('class', 'form-control')
            ->setRequired(true)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/flags/')
            ->addValidator('Size', false, 102400)
            ->addValidator('Extension', false, 'jpg,png,gif')
            ->addValidator('IsImage')
            ->addValidator('Count', false, 1)
            ->setDecorators($this->fileDecorators);

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Добавить'))
            ->setAttrib('class', 'btn btn-primary')
            ->setIgnore(true)
            ->setDecorators($this->buttonDecorators);

        $reset = new Zend_Form_Element_Reset('Reset');
        $reset->setLabel($this->translate('Сбросить'))
            ->setAttrib('class', 'btn btn-default')
            ->setIgnore(true)
            ->setDecorators($this->buttonDecorators);

        $countyAllUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'country', 'action' => 'all'), 'country_all'
        );

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='{$countyAllUrl}'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->buttonDecorators);

        $this->addElement($nativeName)
            ->addElement($englishName)
            ->addElement($abbreviation)
            ->addElement($urlImageRound)
            ->addElement($urlImageGlossyWave);

        $this->addElement($submit)
            ->addElement($reset)
            ->addElement($cancel);

        $this->addDisplayGroup(
            array(
                 $this->getElement('Submit'),
                 $this->getElement('Reset'),
                 $this->getElement('Cancel'),
            ), 'FormActions', array()
        );

        $this->getDisplayGroup('FormActions')->setDecorators(
            array(
                 'FormElements',
                 //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
                 //'Fieldset',
                 array(array('outerHtmlTag' => 'HtmlTag'),
                       array('tag' => 'div', 'class' => 'block-item-form-actions text-center clearfix')),
            )
        );
    }

}
