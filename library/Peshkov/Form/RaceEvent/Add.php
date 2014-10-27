<?php

class Peshkov_Form_RaceEvent_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $adminRaceEventAddUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'race-event', 'action' => 'add'), 'default');
        $adminRaceEventAllUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'race-event', 'action' => 'all'), 'adminRaceEventAll');

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'race-event-add'
            )
        )
            ->setName('raceEventAdd')
            ->setAction($adminRaceEventAddUrl)
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

        $description = new Zend_Form_Element_Textarea('Description');
        $description->setLabel($this->translate('Описание'))
            ->setOptions(array('maxLength' => 5000, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Описание'))
            ->setRequired(false)
            ->addValidator('stringLength', false, array(0, 5000, 'UTF-8'))
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $championships = new Zend_Form_Element_Select('ChampionshipID');
        $championships->setLabel($this->translate('Чемпионат'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Чемпионат'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getChampionships() as $championship) {
            $championships->addMultiOption($championship['ID'], $championship['Name']);
        };

        $orderInChamp = new Zend_Form_Element_Text('OrderInChamp');
        $orderInChamp->setLabel($this->translate('Порядковый номер события в чемпионате'))
            ->setOptions(array('maxLength' => 3, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Название'))
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('Int')
            ->addValidator('stringLength', false, array(1, 3, 'UTF-8'))
            ->addFilter('Int')
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        $orderInChamp->setDescription($this->translate('При не надобности - оставить по умолчанию'));

        $dateStart = new Zend_Form_Element_Text('DateStart');
        $dateStart->setLabel($this->translate('Дата старта'))
            ->setOptions(array('maxLength' => 128, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Дата старта'))
            ->setAttrib('readonly', 'readonly')
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 128, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $dateEnd = new Zend_Form_Element_Text('DateEnd');
        $dateEnd->setLabel($this->translate('Дата окончания'))
            ->setOptions(array('maxLength' => 128, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Дата окончания'))
            ->setAttrib('readonly', 'readonly')
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 128, 'UTF-8'))
            ->addFilter('StripTags')
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
            ->setAttrib('onClick', "location.href='".$adminRaceEventAllUrl."'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($championships)
            ->addElement($description);

        $this->addElement($orderInChamp)
            ->addElement($dateStart)
            ->addElement($dateEnd);

        $this->addElement($submit)
            ->addElement($reset)
            ->addElement($cancel);

        $this->addDisplayGroup(
            array(
                $this->getElement('Name'),
                $this->getElement('ChampionshipID'),
                $this->getElement('Description'),
            ), 'RaceEventInfo'
        );

        $this->getDisplayGroup('RaceEventInfo')
            ->setOrder(10)
            ->setLegend('Информация о гоночном событии')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('OrderInChamp'),
                $this->getElement('DateStart'),
                $this->getElement('DateEnd'),
            ), 'AdditionalRaceEventInfo'
        );

        $this->getDisplayGroup('AdditionalRaceEventInfo')
            ->setOrder(20)
            ->setLegend('Дополнительная информация о гоночном событии')
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

    public function getChampionships(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_Championship champ')
            ->orderBy('champ.Name ASC');
        return $query->fetchArray();
    }

}
