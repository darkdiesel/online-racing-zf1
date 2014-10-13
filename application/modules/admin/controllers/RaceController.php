<?php

class Admin_RaceController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Гонки'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view all races
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
            $this->view->pageTitle($this->view->t('Гонки'));
            $this->view->pageIcon('<i class="fa fa-road"></i>');

            $query = Doctrine_Query::create()
                ->from('Default_Model_Race r')
                ->leftJoin('r.Track t')
                ->leftJoin('r.RaceEvent re')
                ->leftJoin('re.Championship champ')
                ->orderBy('r.ID DESC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $racePaginator = new Zend_Paginator($adapter);
            // pager settings
            $racePaginator->setItemCountPerPage("10");
            $racePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $racePaginator->setPageRange("5");

            if ($racePaginator->count() == 0) {
                $this->view->raceData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->raceData = $racePaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new race
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить гонку'));
        $this->view->pageIcon('<i class="fa fa-plus"></i>');

        // form
        $raceAddForm = new Peshkov_Form_Race_Add();
        $this->view->raceAddRorm = $raceAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($raceAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Race();

                $formData = $raceAddForm->getValues();

                if (!$formData['Description']) {
                    $formData['Description'] = null;
                }

                if (!$formData['DateStart']) {
                    $formData['DateStart'] = null;
                }

                if (!$formData['LapsCount']) {
                    $formData['LapsCount'] = null;
                }

                if (!$formData['TimeDuration']) {
                    $formData['TimeDuration'] = null;
                }

                if (!$formData['OrderInEvent']) {
                    $formData['OrderInEvent'] = null;
                }

                $item->fromArray($formData);
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $this->messages->addSuccess(
                    $this->view->t("Гоночное событие <strong>" . $item->Name . "</strong> успешно создано.")
                );

                $defaultRaceIDUrl = $this->view->url(
                    array('module' => 'default', 'controller' => 'race', 'action' => 'id',
                        'raceID' => $item->ID), 'defaultRaceID'
                );

                $this->redirect($defaultRaceIDUrl);

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit race
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать гонку'));
        $this->view->pageIcon('<i class="fa fa-pencil"></i>');

        // set filters and validators for GET input
        $filters = array(
            'raceID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'raceID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $raceEditForm = new Peshkov_Form_Race_Edit();
            $this->view->raceEditForm = $raceEditForm;

            if ($this->getRequest()->isPost()) {
                if ($raceEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $raceEditForm->getValues();

                    if (!$formData['Description']) {
                        $formData['Description'] = null;
                    }

                    if (!$formData['DateStart']) {
                        $formData['DateStart'] = null;
                    }

                    if (!$formData['LapsCount']) {
                        $formData['LapsCount'] = null;
                    }

                    if (!$formData['TimeDuration']) {
                        $formData['TimeDuration'] = null;
                    }

                    if (!$formData['OrderInEvent']) {
                        $formData['OrderInEvent'] = null;
                    }

                    $item = Doctrine_Core::getTable('Default_Model_Race')->find($requestData->raceID);

                    $item->fromArray($formData);

                    $item->DateEdit = date('Y-m-d H:i:s');

                    $item->save();

                    $this->messages->addSuccess(
                        $this->view->t("Гонка <strong>" . $item->Name . "</strong> успешно отредактирована.")
                    );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $defaultRaceIDUrl = $this->view->url(
                        array('module' => 'default', 'controller' => 'race', 'action' => 'id', 'raceID' => $requestData->raceID),
                        'defaultRaceID'
                    );

                    $this->redirect($defaultRaceIDUrl);
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
                    ->from('Default_Model_Race r')
                    ->leftJoin('r.Track t')
                    ->leftJoin('r.RaceEvent re')
                    ->leftJoin('re.Championship champ')
                    ->where('r.ID = ?', $requestData->raceID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->raceData = $result[0];
                    $this->view->raceEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемая гонка не найдена!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Гонка не найдена!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Гонка не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete race
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить гонку'));
        $this->view->pageIcon('<i class="fa fa fa-trash-o"></i>');

        // set filters and validators for GET input
        $filters = array(
            'raceID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'raceID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Race r')
                ->leftJoin('r.Track t')
                ->leftJoin('r.RaceEvent re')
                ->leftJoin('re.Championship champ')
                ->where('r.ID = ?', $requestData->raceID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create race delete form
                $raceDeleteForm = new Peshkov_Form_Race_Delete();

                $this->view->raceData = $result[0];
                $this->view->raceDeleteForm = $raceDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить гонку')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($raceDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Race r')
                            ->whereIn('r.ID', $requestData->raceID);

                        $result = $query->execute();

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Гонка <strong>" . $this->view->raceData['Name'] . "</strong> успешно удалена."
                            )
                        );

                        $adminRaceAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'race', 'action' => 'all', 'page' => 1),
                            'adminRaceAll'
                        );

                        $this->redirect($adminRaceAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая гонка не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Гонка не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Гонка не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}