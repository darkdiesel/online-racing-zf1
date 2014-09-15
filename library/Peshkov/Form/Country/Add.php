<?php

class Peshkov_Form_Country_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'country-add'
            )
        )
            ->setName('countryAdd')
            ->setAction(
                $this->getView()->url(
                    array('module' => 'admin', 'controller' => 'country', 'action' => 'add'), 'default'
                )
            )
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

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
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

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
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

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
            ->addFilter('StringToUpper')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $urlImageRound = new Zend_Form_Element_File('ImageRoundUrl');
        $urlImageRound->setLabel($this->translate('Круговая картинка флага (32х24)'))
            ->setAttrib('class', 'form-control')
            ->setRequired(true)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/flags/')
            ->addValidator('Size', false, 102400) // 100 kb
            ->addValidator('Extension', false, 'jpg,png,gif')
            //->addValidator('IsImage')
            ->addValidator('Count', false, 1)
            ->setDecorators($this->getView()->getDecorator()->fileDecorators());

        $urlImageGlossyWave = new Zend_Form_Element_File('ImageGlossyWaveUrl');
        $urlImageGlossyWave->setLabel($this->translate('Волнистая картинка флага (64х48)'))
            ->setAttrib('class', 'form-control')
            ->setRequired(true)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/flags/')
            ->addValidator('Size', false, 102400) // 100 kb
            ->addValidator('Extension', false, 'jpg,png,gif')
            //->addValidator('IsImage')
            ->addValidator('Count', false, 1)
            ->setDecorators($this->getView()->getDecorator()->fileDecorators());

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Добавить'))
            ->setAttrib('class', 'btn btn-primary')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $reset = new Zend_Form_Element_Reset('Reset');
        $reset->setLabel($this->translate('Сбросить'))
            ->setAttrib('class', 'btn btn-default')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $adminCountyAllUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'country', 'action' => 'all'), 'adminCountryAll'
        );

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='{$adminCountyAllUrl}'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

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
                $this->getElement('NativeName'),
                $this->getElement('EnglishName'),
                $this->getElement('Abbreviation')
            ), 'CountryInfo'
        );

        $this->getDisplayGroup('CountryInfo')
            ->setOrder(10)
            ->setLegend('Информация о стране')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('ImageRoundUrl'),
                $this->getElement('ImageGlossyWaveUrl')
            ), 'CountryImg'
        );

        $this->getDisplayGroup('CountryImg')
            ->setOrder(20)
            ->setLegend('Изоброжения флагов страны')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('Submit'),
                $this->getElement('Reset'),
                $this->getElement('Cancel'),
            ), 'FormActions'
        );

        $this->getDisplayGroup('FormActions')
            ->setOrder(100)
            ->setDecorators($this->getView()->getDecorator()->formActionsGroupDecorators());
    }

}
