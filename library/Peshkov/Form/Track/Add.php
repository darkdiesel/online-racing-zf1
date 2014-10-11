<?php

class Peshkov_Form_Track_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $adminTrackAddUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'add'), 'default');
        $adminTrackAllUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'all'), 'adminTrackAll');

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'track-add'
            )
        )
            ->setName('trackAdd')
            ->setAction($adminTrackAddUrl)
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $name = new Zend_Form_Element_Text('Name');
        $name->setLabel($this->translate('Название'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Название'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $countries = new Zend_Form_Element_Select('CountryID');
        $countries->setLabel($this->translate('Страна'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Страна'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        foreach ($this->getCountries() as $country) {
            $countries->addMultiOption($country['ID'], $country['NativeName'] . ' (' . $country['EnglishName'] . ')');
        };

        $cities = new Zend_Form_Element_Select('CityID');
        $cities->setLabel($this->translate('Город'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Город'))
            ->setRequired(false)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        //TODO: Add function and load cities for country or ajax query after selecting country
//        foreach ($this->getCountries() as $country) {
//            $countries->addMultiOption($country['ID'], $country['NativeName'] . ' (' . $country['EnglishName'] . ')');
//        };

        $year = new Zend_Form_Element_Text('Year');
        $year->setLabel($this->translate('Год конфигурации (ГГГГ)'))
            ->setOptions(array('maxLength' => 4, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Год конфигурации трассы (ГГГГ)'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 4, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $length = new Zend_Form_Element_Text('Length');
        $length->setLabel($this->translate('Длинна (Км)'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Длинна трассы (Км)'))
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $logoUrl = new Zend_Form_Element_File('LogoUrl');
        $logoUrl->setLabel($this->translate('Логотип'))
            ->setAttrib('class', 'form-control')
            ->setRequired(false)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/tracks/')
            ->addValidator('Size', false, 512000)// 500 kb
            ->addValidator('Extension', false, 'jpg,jpeg,png,gif')
            //->addValidator('IsImage')
            ->addValidator('Count', false, 1)
            ->setDecorators($this->getView()->getDecorator()->fileDecorators());

        $schemeUrl = new Zend_Form_Element_File('SchemeUrl');
        $schemeUrl->setLabel($this->translate('Схема'))
            ->setAttrib('class', 'form-control')
            ->setRequired(true)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/tracks/')
            ->addValidator('Size', false, 512000)// 500 kb
            ->addValidator('Extension', false, 'jpg,jpeg,png,gif')
            //->addValidator('IsImage')
            ->addValidator('Count', false, 1)
            ->setDecorators($this->getView()->getDecorator()->fileDecorators());

        $description = new Zend_Form_Element_Textarea('Description');
        $description->setLabel($this->translate('Описание'))
            ->setOptions(array('maxLength' => 5000, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Описание'))
            ->setRequired(false)
            ->addValidator('stringLength', false, array(0, 5000, 'UTF-8'))
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

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

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='" . $adminTrackAllUrl . "'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($countries)
            ->addElement($cities)
            ->addElement($logoUrl)
            ->addElement($schemeUrl)
            ->addElement($description);

        $this->addElement($year)
            ->addElement($length)
            ->addElement($length);

        $this->addElement($submit)
            ->addElement($reset)
            ->addElement($cancel);

        $this->addDisplayGroup(
            array(
                $this->getElement('Name'),
                $this->getElement('CityID'),
                $this->getElement('CountryID'),
                $this->getElement('LogoUrl'),
                $this->getElement('SchemeUrl'),
                $this->getElement('Description')
            ), 'TrackInfo'
        );

        $this->getDisplayGroup('TrackInfo')
            ->setOrder(10)
            ->setLegend('Информация о трассе')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('Year'),
                $this->getElement('Length'),
            ), 'AdditionalTrackInfo'
        );

        $this->getDisplayGroup('AdditionalTrackInfo')
            ->setOrder(20)
            ->setLegend('Дополнительная информация о трассе')
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

    public function getCountries()
    {
        $query = Doctrine_Query::create()
            ->from('Default_Model_Country c')
            ->orderBy('c.NativeName ASC');
        return $query->fetchArray();
    }

}
