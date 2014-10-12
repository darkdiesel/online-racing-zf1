<?php

class RaceEventController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Гоночное событие'));
    }

    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'raceEventID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'raceEventID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_RaceEvent re')
                ->leftJoin('re.Championship champ')
                ->where('re.ID = ?', $requestData->raceEventID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->raceEventData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle($result[0]['Name']);
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемое гоночное событие не найдено!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Гоночное событие не найдено!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Гоночное событие не найдено!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }
}