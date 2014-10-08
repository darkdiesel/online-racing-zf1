<?php

class Admin_ChampionshipController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Чемпионат'));
    }

    // action for view all posts
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
            $this->view->pageTitle($this->view->translate('Чемпионаты'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Championship champ')
                ->leftJoin('champ.User u')
                ->leftJoin('champ.PostRule pr')
                ->leftJoin('champ.PostGame pg')
                ->leftJoin('champ.RacingSeries rc')
                ->orderBy('champ.ID DESC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $championshipPaginator = new Zend_Paginator($adapter);
            // pager settings
            $championshipPaginator->setItemCountPerPage("10");
            $championshipPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $championshipPaginator->setPageRange("5");

            if ($championshipPaginator->count() == 0) {
                $this->view->championshipData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->championshipData = $championshipPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new championship
    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить чемпионат'));

        // form
        $championshipAddForm = new Peshkov_Form_Championship_Add();
        $this->view->championshipAddForm = $championshipAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($championshipAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Championship();

                $formData = $championshipAddForm->getValues();

                if (!$formData['DateStart']) {
                    unset($formData['DateStart']);
                }

                if (!$formData['DateEnd']) {
                    unset($formData['DateEnd']);
                }

                if (!$formData['HotLapsIP']) {
                    unset($formData['HotLapsIP']);
                }

                $item->fromArray($formData);

                $item->DateCreate = $date;
                $item->DateEdit = $date;

                //receive and rename image logo file
                if ($championshipAddForm->getValue('ImageLogoUrl')) {
                    if ($championshipAddForm->ImageLogoUrl->receive()) {
                        $file = $championshipAddForm->ImageLogoUrl->getFileInfo();
                        $ext = pathinfo($file['ImageLogoUrl']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_championship_logo' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['ImageLogoUrl']['destination'] . '/'
                            . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['ImageLogoUrl']['destination'] . '/' . $file['ImageLogoUrl']['name']
                        );

                        $item->ImageLogoUrl = '/data-content/data-uploads/championships/' . $newName;
                    }
                }

                $item->save();

                $this->messages->addSuccess(
                    $this->view->translate("Чемпионат <strong>" . $item->Name . "</strong> успешно создан.")
                );

                $this->redirect(
                    $this->view->url(
                        array('module' => 'default', 'controller' => 'championship', 'action' => 'id',
                            'championshipID' => $item->ID), 'defaultChampionshipID'
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

    // action for edit championship
    public function editAction()
    {
        $this->view->headTitle($this->view->translate('Редактировать'));
        $this->view->pageTitle($this->view->translate('Редактировать чемпионат'));

        // set filters and validators for GET input
        $filters = array(
            'championshipID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'championshipID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $championshipEditForm = new Peshkov_Form_Championship_Edit();
            $this->view->championshipEditForm = $championshipEditForm;

            if ($this->getRequest()->isPost()) {
                if ($championshipEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $championshipEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_Championship')->find($requestData->championshipID);

                    //receive and rename image logo file
                    if ($formData['ImageLogoUrl']) {
                        if ($championshipEditForm->ImageLogoUrl->receive()) {
                            $file = $championshipEditForm->ImageLogoUrl->getFileInfo();
                            $ext = pathinfo($file['ImageLogoUrl']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_championship_logo' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['ImageLogoUrl']['destination'] . '/'
                                . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['ImageLogoUrl']['destination'] . '/' . $file['ImageLogoUrl']['name']
                            );

                            $formData['ImageLogoUrl'] = '/data-content/data-uploads/championships/' . $newName;

                            if ($formData['ImageLogoUrl'] != $item['ImageLogoUrl']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['ImageLogoUrl']);
                            }
                        }
                    } else {
                        unset($formData['ImageLogoUrl']);
                    }

                    if (!$formData['DateStart']) {
                        unset($formData['DateStart']);
                    }

                    if (!$formData['DateEnd']) {
                        unset($formData['DateEnd']);
                    }

                    if (!$formData['HotLapsIP']) {
                        unset($formData['HotLapsIP']);
                    }

                    $item->fromArray($formData);

                    $item->DateEdit = date('Y-m-d H:i:s');

                    $item->save();

                    $this->messages->addSuccess(
                        $this->view->translate("Чемпионат <strong>" . $item->Name . "</strong> успешно изменен.")
                    );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminChampionshipIDUrl = $this->view->url(
                        array('module' => 'default', 'controller' => 'championship', 'action' => 'id', 'championshipID' => $requestData->championshipID),
                        'defaultChampionshipID'
                    );

                    $this->redirect($adminChampionshipIDUrl);
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
                    ->from('Default_Model_Championship champ')
                    ->leftJoin('champ.User u')
                    ->leftJoin('champ.PostRule pr')
                    ->leftJoin('champ.PostGame pg')
                    ->leftJoin('champ.RacingSeries rc')
                    ->where('champ.ID = ?', $requestData->championshipID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->championshipData = $result[0];
                    $this->view->championshipEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));

                    $this->view->headTitle($this->view->translate('Ошибка!'));
                    $this->view->headTitle($this->view->translate('Чемпионат не найден!'));

                    $this->view->pageTitle($this->view->translate('Ошибка!'));
                    $this->view->pageTitle($this->view->translate('Чемпионат не найден!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete league
    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));
        $this->view->pageTitle($this->view->translate('Удалить чемпионат'));

        // set filters and validators for GET input
        $filters = array(
            'championshipID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'championshipID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Сhampionship champ')
                ->leftJoin('champ.User u')
                ->leftJoin('champ.PostRule pr')
                ->leftJoin('champ.PostGame pg')
                ->leftJoin('champ.RacingSeries rc')
                ->where('champ.ID = ?', $requestData->championshipID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create championship delete form
                $championshipDeleteForm = new Peshkov_Form_Сhampionship_Delete();

                $this->view->championshipData = $result[0];
                $this->view->championshipDeleteForm = $championshipDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->translate('Вы действительно хотите удалить чемпионат')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($championshipDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Сhampionship champ')
                            ->whereIn('champ.ID', $requestData->championshipID);

                        $result = $query->execute();

                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->championshipData['ImageLogoUrl']);

                        //TODO: Delete all teams, teams, and races after delleting championship

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->translate("Чемпионат <strong>" . $this->view->championshipData['Name'] . "</strong> успешно удален.")
                        );

                        $adminСhampionshipAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'championship', 'action' => 'all', 'page' => 1),
                            'adminСhampionshipAll'
                        );

                        $this->redirect($adminСhampionshipAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Чемпионат не найден!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Чемпионат не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
