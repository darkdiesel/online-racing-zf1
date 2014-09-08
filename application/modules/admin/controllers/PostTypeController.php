<?php

class Admin_PostTypeController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Тип статьи'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view post type
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'postTypeID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postTypeID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_PostType pt')
                ->where('pt.ID = ?', $requestData->postTypeID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->postTypeData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->translate('Тип статьи') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Тип статьи не найден!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Тип статьи не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all post types
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
            $this->view->pageTitle($this->view->translate('Типы статей'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_PostType pt')
                ->orderBy('pt.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $postTypePaginator = new Zend_Paginator($adapter);
            // pager settings
            $postTypePaginator->setItemCountPerPage("10");
            $postTypePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $postTypePaginator->setPageRange("5");

            if ($postTypePaginator->count() == 0) {
                $this->view->postTypeData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->postTypeData = $postTypePaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new post type
    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить тип статьи'));

        // form
        $postTypeAddForm = new Peshkov_Form_PostType_Add();
        $this->view->postTypeAddForm = $postTypeAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($postTypeAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_PostType();

                $item->fromArray($postTypeAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id',
                            'postTypeID' => $item->ID), 'adminPostTypeID'
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

    // action for edit post type
    public function editAction()
    {
        $this->view->headTitle($this->view->translate('Редактировать'));
        $this->view->pageTitle($this->view->translate('Редактировать тип статьи'));

        // set filters and validators for GET input
        $filters = array(
            'postTypeID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postTypeID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $postTypeEditForm = new Peshkov_Form_PostType_Edit();
            $this->view->postTypeEditForm = $postTypeEditForm;

            if ($this->getRequest()->isPost()) {
                if ($postTypeEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $postTypeEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_PostType')->find($requestData->postTypeID);

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminPostTypeIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id', 'postTypeID' => $requestData->postTypeID),
                        'adminPostTypeID'
                    );

                    $this->redirect($adminPostTypeIDUrl);
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
                    ->from('Default_Model_PostType pt')
                    ->where('pt.ID = ?', $requestData->postTypeID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->postTypeData = $result[0];
                    $this->view->postTypeEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));

                    $this->view->headTitle($this->view->translate('Ошибка!'));
                    $this->view->headTitle($this->view->translate('Тип статьи не найден!'));

                    $this->view->pageTitle($this->view->translate('Ошибка!'));
                    $this->view->pageTitle($this->view->translate('Тип статьи не найден!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete post type
    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));
        $this->view->pageTitle($this->view->translate('Удалить тип статьи'));

        // set filters and validators for GET input
        $filters = array(
            'postTypeID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postTypeID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_PostType pt')
                ->where('pt.ID = ?', $requestData->postTypeID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create content-type delete form
                $postTypeDeleteForm = new Peshkov_Form_PostType_Delete();

                $this->view->postTypeData = $result[0];
                $this->view->postTypeDeleteForm = $postTypeDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->translate('Вы действительно хотите удалить тип статьи')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($postTypeDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_PostType pt')
                            ->whereIn('pt.ID', $requestData->postTypeID);

                        $result = $query->execute();

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->translate("Тип статьи <strong>" . $this->view->postTypeData['Name'] . "</strong> успешно удален."
                            )
                        );

                        $adminPostTypeAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'post-type', 'action' => 'all', 'page' => 1),
                            'adminPostTypeAll'
                        );

                        $this->redirect($adminPostTypeAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Тип статьи не найден!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Тип статьи не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
