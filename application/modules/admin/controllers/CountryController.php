<?php

class Admin_CountryController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Страна'));

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
                    $this->view->t('Страна') . ' :: ' . $result[0]['EnglishName'] . ' ('
                    . $result[0]['NativeName'] . ')'
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая страна не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Страна не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Страна не найдена!'));
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
            $this->view->headTitle($this->view->t('Все'));
            $this->view->pageTitle($this->view->t('Cтраны'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Country c')
                ->orderBy('c.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $countryPaginator = new Zend_Paginator($adapter);
            // pager settings
            $countryPaginator->setItemCountPerPage("10");
            $countryPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $countryPaginator->setPageRange("5");

            if ($countryPaginator->count() == 0) {
                $this->view->countryData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->countryData = $countryPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить страну'));

        // form
        $countryAddForm = new Peshkov_Form_Country_Add();
        $this->view->countryAddForm = $countryAddForm;

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
                if ($countryAddForm->getValue('ImageRoundUrl')) {
                    if ($countryAddForm->ImageRoundUrl->receive()) {
                        $file = $countryAddForm->ImageRoundUrl->getFileInfo();
                        $ext = pathinfo($file['ImageRoundUrl']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['ImageRoundUrl']['destination'] . '/'
                            . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['ImageRoundUrl']['destination'] . '/' . $file['ImageRoundUrl']['name']
                        );

                        $item->ImageRoundUrl = '/data-content/data-uploads/countries/' . $newName;
                    }
                }

                //receive and rename image_glossy_wave file
                if ($countryAddForm->getValue('ImageGlossyWaveUrl')) {
                    if ($countryAddForm->ImageGlossyWaveUrl->receive()) {
                        $file = $countryAddForm->ImageGlossyWaveUrl->getFileInfo();
                        $ext = pathinfo($file['ImageGlossyWaveUrl']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['ImageGlossyWaveUrl']['destination']
                            . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['ImageGlossyWaveUrl']['destination'] . '/' . $file['ImageGlossyWaveUrl']['name']
                        );

                        $item->ImageGlossyWaveUrl = '/data-content/data-uploads/countries/' . $newName;
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
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }

    }

    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать страну'));

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
                    if ($formData['ImageRoundUrl']) {
                        if ($countryEditForm->ImageRoundUrl->receive()) {
                            $file = $countryEditForm->ImageRoundUrl->getFileInfo();
                            $ext = pathinfo($file['ImageRoundUrl']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['ImageRoundUrl']['destination'] . '/'
                                . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['ImageRoundUrl']['destination'] . '/' . $file['ImageRoundUrl']['name']
                            );

                            $formData['ImageRoundUrl'] = '/data-content/data-uploads/flags/' . $newName;

                            if ($formData['ImageRoundUrl'] != $item['ImageRoundUrl']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['ImageRoundUrl']);
                            }
                        }
                    } else {
                        unset($formData['ImageRoundUrl']);
                    }

                    //receive and rename image_glossy_wave file
                    if ($formData['ImageGlossyWaveUrl']) {
                        if ($countryEditForm->ImageGlossyWaveUrl->receive()) {
                            $file = $countryEditForm->ImageGlossyWaveUrl->getFileInfo();
                            $ext = pathinfo($file['ImageGlossyWaveUrl']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['ImageGlossyWaveUrl']['destination']
                                . '/' . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['ImageGlossyWaveUrl']['destination'] . '/' . $file['ImageGlossyWaveUrl']['name']
                            );

                            $formData['ImageGlossyWaveUrl'] = '/data-content/data-uploads/flags/' . $newName;

                            if ($formData['ImageGlossyWaveUrl'] != $item['ImageGlossyWaveUrl']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['ImageGlossyWaveUrl']);
                            }
                        }
                    } else {
                        unset($formData['ImageGlossyWaveUrl']);
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
                        $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
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

                    $this->messages->addError($this->view->t('Запрашиваемая страна не найдена!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Страна не найдена!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Страна не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить страну'));

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
                    $this->view->t('Вы действительно хотите удалить страну')
                    . " <strong>" . $result[0]['NativeName'] . " (" . $result[0]['EnglishName'] . ")</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($countryDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Country c')
                            ->whereIn('c.ID', $requestData->countryID);

                        $result = $query->execute();

                        // remove country images
                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->countryData['ImageRoundUrl']);
                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->countryData['ImageGlossyWaveUrl']);

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Страна <strong>" . $this->view->countryData['NativeName'] . " (" . $this->view->countryData['EnglishName'] . ")</strong> успешно удалена."
                            )
                        );

                        $adminCountryAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'country', 'action' => 'all', 'page' => 1),
                            'adminCountryAll'
                        );

                        $this->redirect($adminCountryAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая страна не найдена!!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Страна не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Страна не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
