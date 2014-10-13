<?php

class Admin_RaceEventController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Гоночное событие'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view all race events
    public function allAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'page' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'page' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $this->view->headTitle($this->view->t('Все'));
            $this->view->pageTitle($this->view->t('Гоночные события'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_RaceEvent re')
                ->leftJoin('re.Championship champ')
                ->orderBy('re.ID DESC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $raceEventPaginator = new Zend_Paginator($adapter);
            // pager settings
            $raceEventPaginator->setItemCountPerPage("10");
            $raceEventPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $raceEventPaginator->setPageRange("5");

            if ($raceEventPaginator->count() == 0) {
                $this->view->raceEventData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->raceEventData = $raceEventPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new race-event
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить гоночное событие'));

        // form
        $raceEventAddForm = new Peshkov_Form_RaceEvent_Add();
        $this->view->raceEventAddRorm = $raceEventAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($raceEventAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_RaceEvent();

                $formData = $raceEventAddForm->getValues();

                if (!$formData['Description']) {
                    $formData['Description'] = null;
                }

                if (!$formData['DateStart']) {
                    $formData['DateStart'] = null;
                }

                if (!$formData['DateEnd']) {
                    $formData['DateEnd'] = null;
                }

                if (!$formData['OrderInChamp']) {
                    $formData['OrderInChamp'] = null;
                }

                $item->fromArray($formData);
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $this->messages->addSuccess(
                    $this->view->t("Гоночное событие <strong>" . $item->Name . "</strong> успешно создано.")
                );

                $defaultRaceEventIDUrl = $this->view->url(
                    array('module' => 'default', 'controller' => 'race-event', 'action' => 'id',
                        'raceEventID' => $item->ID), 'defaultRaceEventID'
                );

                $this->redirect($defaultRaceEventIDUrl);

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit race-event
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать гоночное событие'));

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

            $raceEventEditForm = new Peshkov_Form_RaceEvent_Edit();
            $this->view->raceEventEditForm = $raceEventEditForm;

            if ($this->getRequest()->isPost()) {
                if ($raceEventEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $raceEventEditForm->getValues();

                    if (!$formData['Description']) {
                        $formData['Description'] = null;
                    }

                    if (!$formData['DateStart']) {
                        $formData['DateStart'] = null;
                    }

                    if (!$formData['DateEnd']) {
                        $formData['DateEnd'] = null;
                    }

                    if (!$formData['OrderInChamp']) {
                        $formData['OrderInChamp'] = null;
                    }

                    $item = Doctrine_Core::getTable('Default_Model_RaceEvent')->find($requestData->raceEventID);

                    $item->fromArray($formData);

                    $item->DateEdit = date('Y-m-d H:i:s');

                    $item->save();

                    $this->messages->addSuccess(
                        $this->view->t("Гоночное событие <strong>" . $item->Name . "</strong> успешно отредактировано.")
                    );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $defaultRaceEventIDUrl = $this->view->url(
                        array('module' => 'default', 'controller' => 'race-event', 'action' => 'id', 'raceEventID' => $requestData->raceEventID),
                        'defaultRaceEventID'
                    );

                    $this->redirect($defaultRaceEventIDUrl);
                } else {
                    $this->messages->addError(
                        $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                    );
                }
            } else {
                // if GET request
                // retrieve requested record
                // pre-populate form
                $query = Doctrine_Query::create()
                    ->from('Default_Model_RaceEvent re')
                    ->leftJoin('re.Championship champ')
                    ->where('re.ID = ?', $requestData->raceEventID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->raceEventData = $result[0];
                    $this->view->raceEventEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемое гоночное событие не найдено!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Гоночное событие не найдено!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Гоночное событие не найдено!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete race-event
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить гоночное событие'));

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
                // Create race-event delete form
                $raceEventDeleteForm = new Peshkov_Form_RaceEvent_Delete();

                $this->view->raceEventData = $result[0];
                $this->view->raceEventDeleteForm = $raceEventDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить гоночное событие')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($raceEventDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_RaceEvent re')
                            ->whereIn('re.ID', $requestData->raceEventID);

                        $result = $query->execute();

                        //TODO: Delete all races after delleting race-event

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Гоночное событие <strong>" . $this->view->raceEventData['Name'] . "</strong> успешно удалено."
                            )
                        );

                        $adminRaceEventAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'race-event', 'action' => 'all', 'page' => 1),
                            'adminRaceEventAll'
                        );

                        $this->redirect($adminRaceEventAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }
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

//    public function migrateAction()
//    {
//        $query = Doctrine_Query::create()
//            ->from('Default_Model_Race r')
//            ->orderBy('r.ID ASC');
//
//        $result = $query->fetchArray();
//
//        foreach ($result as $race) {
//            $new_item = new Default_Model_RaceEvent();
//
//            $values = array(
//                'Name' => $race['Name'],
//                'DateStart' => $race['DateStart'],
//                'DateEnd' => $race['DateStart'],
//                'OrderInChamp' => $race['OrderInEvent'],
//                'ChampionshipID' => $race['ChampionshipID'],
//                'DateCreate' => $race['DateCreate'],
//                'DateEdit' => $race['DateEdit'],
//            );
//
//            if ($race['Description']){
//                $values['Description'] = $race['Description'];
//            }
//
//            $new_item->fromArray($values);
//
//            $new_item->save();
//
//            //update championship
//            $item = Doctrine_Core::getTable('Default_Model_Race')->find($race['ID']);
//            $item->fromArray($race);
//                $item->RaceEventID = $new_item->ID;
//
//            $item->save();
//
//            echo $race['Name'].' Done!<br/>';
//        }
//    }

}