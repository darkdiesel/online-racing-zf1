<?php

class Admin_CountryController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Страна'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'countryID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'countryID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Country c')
                ->where('c.ID = ?', $requestData->countryID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->countryData = $result[0];

                $this->view->headTitle($result[0]['NativeName']);
                $this->view->pageTitle(
                    $this->view->translate('Страна') . ' :: ' . $result[0]['EnglishName'] . ' ('
                    . $result[0]['NativeName'] . ')'
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемая страна не найдена!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Страна не найдена!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Страна не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

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
            $this->view->pageTitle($this->view->translate('Cтраны'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Country c')
                ->orderBy('c.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $countryPaginator = new Zend_Paginator($adapter);
            // pager settings
            $countryPaginator->setItemCountPerPage("10");
            $countryPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $countryPaginator->setPageRange("5");

            $this->view->countryData = $countryPaginator;

            if ($countryPaginator->count() == 0) {
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить страну'));

        // form
        $countryAddForm = new Peshkov_Form_Country_Add();
        $this->view->countryaddForm = $countryAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($countryAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Country();

                $item->fromArray($countryAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                //receive and rename image_round file
                if ($countryAddForm->getValue('UrlImageRound')) {
                    if ($countryAddForm->UrlImageRound->receive()) {
                        $file = $countryAddForm->UrlImageRound->getFileInfo();
                        $ext = pathinfo($file['UrlImageRound']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['UrlImageRound']['destination'] . '/'
                            . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['UrlImageRound']['destination'] . '/' . $file['UrlImageRound']['name']
                        );

                        $item->UrlImageRound = '/data-content/data-uploads/flags/' . $newName;
                    }
                }

                //receive and rename image_glossy_wave file
                if ($countryAddForm->getValue('UrlImageGlossyWave')) {
                    if ($countryAddForm->UrlImageGlossyWave->receive()) {
                        $file = $countryAddForm->UrlImageGlossyWave->getFileInfo();
                        $ext = pathinfo($file['UrlImageGlossyWave']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['UrlImageGlossyWave']['destination']
                            . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['UrlImageGlossyWave']['destination'] . '/' . $file['UrlImageGlossyWave']['name']
                        );

                        $item->UrlImageGlossyWave = '/data-content/data-uploads/flags/' . $newName;
                    }
                }

                $item->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'admin', 'controller' => 'country', 'action' => 'id',
                            'countryID' => $item->ID), 'adminCountryID'
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

    public function editAction()
    {
        $this->view->headTitle($this->view->translate('Редактировать'));
        $this->view->pageTitle($this->view->translate('Редактировать страну'));

        // set filters and validators for GET input
        $filters = array(
            'countryID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'countryID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $countryEditForm = new Peshkov_Form_Country_Edit();
            $this->view->countryEditForm = $countryEditForm;

            if ($this->getRequest()->isPost()) {
                if ($countryEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $countryEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_Country')->find($requestData->countryID);

                    //receive and rename image_round file
                    if ($formData['UrlImageRound']) {
                        if ($countryEditForm->UrlImageRound->receive()) {
                            $file = $countryEditForm->UrlImageRound->getFileInfo();
                            $ext = pathinfo($file['UrlImageRound']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['UrlImageRound']['destination'] . '/'
                                . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['UrlImageRound']['destination'] . '/' . $file['UrlImageRound']['name']
                            );

                            $formData['UrlImageRound'] = '/data-content/data-uploads/flags/' . $newName;

                            if ($formData['UrlImageRound'] != $item['UrlImageRound']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['UrlImageRound']);
                            }
                        }
                    } else {
                        unset($formData['UrlImageRound']);
                    }

                    //receive and rename image_glossy_wave file
                    if ($formData['UrlImageGlossyWave']) {
                        if ($countryEditForm->UrlImageGlossyWave->receive()) {
                            $file = $countryEditForm->UrlImageGlossyWave->getFileInfo();
                            $ext = pathinfo($file['UrlImageGlossyWave']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['UrlImageGlossyWave']['destination']
                                . '/' . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['UrlImageGlossyWave']['destination'] . '/' . $file['UrlImageGlossyWave']['name']
                            );

                            $formData['UrlImageGlossyWave'] = '/data-content/data-uploads/flags/' . $newName;

                            if ($formData['UrlImageGlossyWave'] != $item['UrlImageGlossyWave']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['UrlImageGlossyWave']);
                            }
                        }
                    } else {
                        unset($formData['UrlImageGlossyWave']);
                    }

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminCountryIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'country', 'action' => 'id', 'countryID' => $requestData->countryID),
                        'adminCountryID'
                    );

                    $this->redirect($adminCountryIDUrl);
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
                    ->from('Default_Model_Country c')
                    ->where('c.ID = ?', $requestData->countryID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->countryData = $result[0];
                    $this->view->countryEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->translate('Запрашиваемая страна не найдена!'));

                    $this->view->headTitle($this->view->translate('Ошибка!'));
                    $this->view->headTitle($this->view->translate('Страна не найдена!'));

                    $this->view->pageTitle($this->view->translate('Ошибка!'));
                    $this->view->pageTitle($this->view->translate('Страна не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));
        $this->view->pageTitle($this->view->translate('Удалить страну'));

        // set filters and validators for POST input
        $filters = array(
            'countryID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'countryID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Country c')
                ->where('c.ID = ?', $requestData->countryID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                //Create country delete form
                $countryDeleteForm = new Peshkov_Form_Country_Delete();

                $this->view->countryData = $result[0];
                $this->view->countryDeleteForm = $countryDeleteForm;

                $this->view->headTitle($result[0]['NativeName']);

                $this->messages->addWarning(
                    $this->view->translate('Вы действительно хотите удалить страну')
                    . " <strong>" . $result[0]['NativeName'] . " (" . $result[0]['EnglishName'] . ")</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($countryDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Country c')
                            ->whereIn('c.ID', $requestData->countryID);

                        $result = $query->execute();

                        // remove country images
                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->countryData['UrlImageRound']);
                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->countryData['UrlImageGlossyWave']);

                        $adminCountryAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'country', 'action' => 'all', 'page' => 1),
                            'adminCountryAll'
                        );

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->translate("Страна <strong>" . $this->view->countryData['NativeName'] . " (" . $this->view->countryData['EnglishName'] . ")</strong> успешно удалена."
                            )
                        );

                        $this->redirect($adminCountryAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемая страна не найдена!!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Страна не найдена!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Страна не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
