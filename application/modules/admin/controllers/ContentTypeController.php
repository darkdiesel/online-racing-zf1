<?php

class Admin_ContentTypeController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Тип контента'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view content type
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'contentTypeID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'contentTypeID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_ContentType ct')
                ->where('ct.ID = ?', $requestData->contentTypeID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->contentTypeData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->t('Тип контента') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемый тип контента не найден!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Тип контента не найден!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Тип контента не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all content types
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
            $this->view->pageTitle($this->view->t('Типы контента'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_ContentType ct')
                ->orderBy('ct.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $contentTypePaginator = new Zend_Paginator($adapter);
            // pager settings
            $contentTypePaginator->setItemCountPerPage("10");
            $contentTypePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $contentTypePaginator->setPageRange("5");

            if ($contentTypePaginator->count() == 0) {
                $this->view->contentTypeData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->contentTypeData = $contentTypePaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new content type
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить тип контента'));

        // form
        $contentTypeAddForm = new Peshkov_Form_ContentType_Add();
        $this->view->contentTypeAddForm = $contentTypeAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($contentTypeAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_ContentType();

                $formData = $contentTypeAddForm->getValues();

                if (!$formData['Description']){
                    unset($formData['Description']);
                }

                $item->fromArray($formData);
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $adminContentTypeIDUrl = $this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id','contentTypeID' => $item->ID), 'adminContentTypeID');

                $this->redirect($adminContentTypeIDUrl);

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit content type
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать тип контента'));

        // set filters and validators for GET input
        $filters = array(
            'contentTypeID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'contentTypeID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $contentTypeEditForm = new Peshkov_Form_ContentType_Edit();
            $this->view->contentTypeEditForm = $contentTypeEditForm;

            if ($this->getRequest()->isPost()) {
                if ($contentTypeEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $contentTypeEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_ContentType')->find($requestData->contentTypeID);

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminContentTypeIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id', 'contentTypeID' => $requestData->contentTypeID),
                        'adminContentTypeID'
                    );

                    $this->redirect($adminContentTypeIDUrl);
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
                    ->from('Default_Model_ContentType ct')
                    ->where('ct.ID = ?', $requestData->contentTypeID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->contentTypeData = $result[0];
                    $this->view->contentTypeEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемый тип контента не найден!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Тип контента не найден!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Тип контента не найден!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete content type
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить тип контента'));

        // set filters and validators for GET input
        $filters = array(
            'contentTypeID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'contentTypeID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_ContentType ct')
                ->where('ct.ID = ?', $requestData->contentTypeID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create content-type delete form
                $contentTypeDeleteForm = new Peshkov_Form_ContentType_Delete();

                $this->view->contentTypeData = $result[0];
                $this->view->contentTypeDeleteForm = $contentTypeDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить тип контента')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($contentTypeDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_ContentType ct')
                            ->whereIn('ct.ID', $requestData->contentTypeID);

                        $result = $query->execute();

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Тип контента <strong>" . $this->view->contentTypeData['Name'] . "</strong> успешно удален."
                            )
                        );

                        $adminContentTypeAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'content-type', 'action' => 'all', 'page' => 1),
                            'adminContentTypeAll'
                        );

                        $this->redirect($adminContentTypeAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемый тип контента не найден!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Тип контента не найден!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Тип контента не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
