<?php

class Peshkov_Form_Race_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $adminRaceAddUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'race', 'action' => 'add'), 'default');
        $adminRaceAllUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'race', 'action' => 'all'), 'adminRaceAll');

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'race-add'
            )
        )
            ->setName('raceEventAdd')
            ->setAction($adminRaceAddUrl)
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
            ->setOptions(array('maxLength' => 500, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Описание'))
            ->setRequired(false)
            ->addValidator('stringLength', false, array(0, 500, 'UTF-8'))
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $raceEvents = new Zend_Form_Element_Select('RaceEventID');
        $raceEvents->setLabel($this->translate('Гоночное событие'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Гоночное событие'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getRaceEvents() as $raceEvent) {
            $raceEvents->addMultiOption($raceEvent['ID'], $raceEvent['Championship']['Name']. ' :: ' . $raceEvent['Name']);
        };

        $tracks = new Zend_Form_Element_Select('TrackID');
        $tracks->setLabel($this->translate('Трасса'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Трасса'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getTracks() as $track) {
            $tracks->addMultiOption($track['ID'], $track['Country']['EnglishName']. ' :: ' . $track['Name'] . ' (' . $track['Year'] .')');
        };

        $lapsCount = new Zend_Form_Element_Text('LapsCount');
        $lapsCount->setLabel($this->translate('Количество кругов'))
            ->setOptions(array('maxLength' => 128, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Количество кругов'))
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('Int')
            ->addValidator('stringLength', false, array(1, 128, 'UTF-8'))
            ->addFilter('Int')
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        $lapsCount->setDescription($this->translate('При не надобности - оставить по умолчанию'));

        $timeDuration = new Zend_Form_Element_Text('TimeDuration');
        $timeDuration->setLabel($this->translate('Время продолжительности гонки'))
            ->setOptions(array('maxLength' => 128, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Время продолжительности гонки'))
            ->setAttrib('readonly', 'readonly')
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 128, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        $timeDuration->setDescription($this->translate('При не надобности - оставить по умолчанию'));

        $orderInEvent = new Zend_Form_Element_Text('OrderInEvent');
        $orderInEvent->setLabel($this->translate('Порядковый номер гонки в событии'))
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
        $orderInEvent->setDescription($this->translate('При не надобности - оставить по умолчанию'));

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
            ->setAttrib('onClick', "location.href='".$adminRaceAllUrl."'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($raceEvents)
            ->addElement($tracks)
            ->addElement($description);

        $this->addElement($lapsCount)
            ->addElement($timeDuration)
            ->addElement($orderInEvent)
            ->addElement($dateStart);

        $this->addElement($submit)
            ->addElement($reset)
            ->addElement($cancel);

        $this->addDisplayGroup(
            array(
                $this->getElement('Name'),
                $this->getElement('RaceEventID'),
                $this->getElement('TrackID'),
                $this->getElement('Description'),
            ), 'RaceInfo'
        );

        $this->getDisplayGroup('RaceInfo')
            ->setOrder(10)
            ->setLegend('Информация о гонке')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('LapsCount'),
                $this->getElement('TimeDuration'),
                $this->getElement('OrderInEvent'),
                $this->getElement('DateStart'),
            ), 'AdditionalRaceInfo'
        );

        $this->getDisplayGroup('AdditionalRaceInfo')
            ->setOrder(20)
            ->setLegend('Дополнительная информация о гонке')
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

    public function getRaceEvents(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_RaceEvent re')
            ->leftJoin('re.Championship champ')
            ->orderBy('champ.DateCreate DESC')
            ->addOrderBy('champ.Name ASC')
            ->addOrderBy('re.DateStart ASC');
        return $query->fetchArray();
    }

    public function getTracks(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_Track tr')
            ->leftJoin('tr.Country c')
            ->orderBy('c.EnglishName ASC')
            ->addOrderBy('tr.Name ASC');
        return $query->fetchArray();
    }

}
