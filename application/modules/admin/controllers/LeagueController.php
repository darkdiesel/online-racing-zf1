<?php

class Admin_LeagueController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Лига'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view all leagues
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
            $this->view->headTitle($this->view->translate('Все'));
            $this->view->pageTitle($this->view->translate('Лиги'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_League l')
                ->leftJoin('l.User u')
                ->orderBy('l.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $leaguePaginator = new Zend_Paginator($adapter);
            // pager settings
            $leaguePaginator->setItemCountPerPage("10");
            $leaguePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $leaguePaginator->setPageRange("5");

            if ($leaguePaginator->count() == 0) {
                $this->view->leagueData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->leagueData = $leaguePaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new league
    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить лигу'));

        // form
        $leagueAddForm = new Peshkov_Form_League_Add();
        $this->view->leagueAddForm = $leagueAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($leagueAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_League();

                $item->fromArray($leagueAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                //receive and rename image_round file
                if ($leagueAddForm->getValue('UrlImageLogo')) {
                    if ($leagueAddForm->UrlImageLogo->receive()) {
                        $file = $leagueAddForm->UrlImageLogo->getFileInfo();
                        $ext = pathinfo($file['UrlImageLogo']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_league_logo' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['UrlImageLogo']['destination'] . '/'
                            . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['UrlImageLogo']['destination'] . '/' . $file['UrlImageLogo']['name']
                        );

                        $item->UrlImageLogo = '/data-content/data-uploads/leagues/' . $newName;
                    }
                }

                $item->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'default', 'controller' => 'league', 'action' => 'id',
                            'leagueID' => $item->ID), 'defaultLeagueID'
                    )
                );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit league
    public function editAction()
    {
        $this->view->headTitle($this->view->translate('Редактировать'));
        $this->view->pageTitle($this->view->translate('Редактировать лигу'));

        // set filters and validators for GET input
        $filters = array(
            'leagueID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'leagueID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $leagueEditForm = new Peshkov_Form_League_Edit();
            $this->view->leagueEditForm = $leagueEditForm;

            if ($this->getRequest()->isPost()) {
                if ($leagueEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $leagueEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_League')->find($requestData->leagueID);

                    //receive and rename image logo file
                    if ($formData['UrlImageLogo']) {
                        if ($leagueEditForm->UrlImageLogo->receive()) {
                            $file = $leagueEditForm->UrlImageLogo->getFileInfo();
                            $ext = pathinfo($file['UrlImageLogo']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['UrlImageLogo']['destination'] . '/'
                                . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['UrlImageLogo']['destination'] . '/' . $file['UrlImageLogo']['name']
                            );

                            $formData['UrlImageLogo'] = '/data-content/data-uploads/leagues/' . $newName;

                            if ($formData['UrlImageLogo'] != $item['UrlImageLogo']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['UrlImageLogo']);
                            }
                        }
                    } else {
                        unset($formData['UrlImageLogo']);
                    }

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminLeagueIDUrl = $this->view->url(
                        array('module' => 'default', 'controller' => 'league', 'action' => 'id', 'leagueID' => $requestData->leagueID),
                        'defaultLeagueID'
                    );

                    $this->redirect($adminLeagueIDUrl);
                } else {
                    $this->messages->addError(
                        $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                    );
                }
            } else {
                // if GET request
                // retrieve requested record
                // pre-populate form
                $query = Doctrine_Query::create()
                    ->from('Default_Model_League l')
                    ->leftJoin('l.User u')
                    ->where('l.ID = ?', $requestData->leagueID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->leagueData = $result[0];
                    $this->view->leagueEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));

                    $this->view->headTitle($this->view->translate('Ошибка!'));
                    $this->view->headTitle($this->view->translate('Лига не найдена!'));

                    $this->view->pageTitle($this->view->translate('Ошибка!'));
                    $this->view->pageTitle($this->view->translate('Лига не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

// action for delete racing series
    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));
        $this->view->pageTitle($this->view->translate('Удалить лигу'));

        // set filters and validators for GET input
        $filters = array(
            'leagueID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'leagueID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_League l')
                ->leftJoin('l.User u')
                ->where('l.ID = ?', $requestData->leagueID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create racing-series delete form
                $leagueDeleteForm = new Peshkov_Form_League_Delete();

                $this->view->leagueData = $result[0];
                $this->view->leagueDeleteForm = $leagueDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->translate('Вы действительно хотите удалить лигу')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($leagueDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_League l')
                            ->whereIn('l.ID', $requestData->leagueID);

                        $result = $query->execute();

                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->leagueData['UrlImageLogo']);

                        //TODO: Delete all championships, teams, and races after delleting league

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->translate("Лига <strong>" . $this->view->leagueData['Name'] . "</strong> успешно удалена."
                            )
                        );

                        $adminLeagueAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'all', 'page' => 1),
                            'adminLeagueAll'
                        );

                        $this->redirect($adminLeagueAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Лига не найдена!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Лига не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
