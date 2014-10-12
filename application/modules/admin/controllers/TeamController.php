<?php

class Admin_TeamController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Команда'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view team
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'teamID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'teamID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Team t')
                ->leftJoin('t.RacingSeries rs')
                ->where('t.ID = ?', $requestData->teamID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->teamData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->t('Команда') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая команда не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Команда не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Команда не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all teams
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
            $this->view->pageTitle($this->view->t('Команды'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Team t')
                ->leftJoin('t.RacingSeries rs')
                ->orderBy('t.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $teamPaginator = new Zend_Paginator($adapter);
            // pager settings
            $teamPaginator->setItemCountPerPage("10");
            $teamPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $teamPaginator->setPageRange("5");

            if ($teamPaginator->count() == 0) {
                $this->view->teamData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->teamData = $teamPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new racing series
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить команду'));

        // form
        $teamAddForm = new Peshkov_Form_Team_Add();
        $this->view->teamAddForm = $teamAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($teamAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Team();

                $item->fromArray($teamAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'admin', 'controller' => 'team', 'action' => 'id',
                            'teamID' => $item->ID), 'adminTeamID'
                    )
                );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit article type
    // action for edit racing series
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать команду'));

        // set filters and validators for GET input
        $filters = array(
            'teamID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'teamID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $teamEditForm = new Peshkov_Form_Team_Edit();
            $this->view->teamEditForm = $teamEditForm;

            if ($this->getRequest()->isPost()) {
                if ($teamEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $teamEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_Team')->find($requestData->teamID);

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminTeamIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'id', 'teamID' => $requestData->teamID),
                        'adminTeamID'
                    );

                    $this->redirect($adminTeamIDUrl);
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
                    ->from('Default_Model_Team t')
                    ->where('t.ID = ?', $requestData->teamID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->teamData = $result[0];
                    $this->view->teamEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемая команда не найдена!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Команда не найдена!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Команда не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete team
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить команду'));

        // set filters and validators for GET input
        $filters = array(
            'teamID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'teamID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Team t')
                ->leftJoin('t.RacingSeries rs')
                ->where('t.ID = ?', $requestData->teamID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create racing-series delete form
                $teamDeleteForm = new Peshkov_Form_Team_Delete();

                $this->view->teamData = $result[0];
                $this->view->teamDeleteForm = $teamDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить команду')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($teamDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Team rs')
                            ->whereIn('rs.ID', $requestData->teamID);

                        $result = $query->execute();

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Команда <strong>" . $this->view->teamData['Name'] . "</strong> успешно удалена."
                            )
                        );

                        $adminTeamAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'all', 'page' => 1),
                            'adminTeamAll'
                        );

                        $this->redirect($adminTeamAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая команда не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Команда не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Команда не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
