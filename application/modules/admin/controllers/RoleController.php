<?php

class Admin_RoleController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Роли Пользователей'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view role
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'roleID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'roleID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_role r')
                ->leftJoin('r.Role pr')
                ->where('r.ID = ?', $requestData->roleID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->roleData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->t('Роль') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая роль не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Роль не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Роль не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all roles
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
            $this->view->pageTitle($this->view->t('Роли пользователей'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Role r')
                ->leftJoin('r.Role pr')
                ->orderBy('r.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $rolePaginator = new Zend_Paginator($adapter);
            // pager settings
            $rolePaginator->setItemCountPerPage("10");
            $rolePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $rolePaginator->setPageRange("5");

            if ($rolePaginator->count() == 0) {
                $this->view->roleData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->roleData = $rolePaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new role
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить роль'));

        // form
        $roleAddForm = new Peshkov_Form_Role_Add();
        $this->view->roleAddForm = $roleAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($roleAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Role();

                $formData = $roleAddForm->getValues();

                if (!$formData['ParentRoleID']){
                    $formData['ParentRoleID'] = null;
                }

                if (!$formData['Description']){
                    $formData['Description'] = null;
                }

                $item->fromArray($formData);

                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $adminRoleIDUrl = $this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'roleID' => $item->ID), 'adminRoleID');

                $this->redirect($adminRoleIDUrl);

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit role
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать роль'));

        // set filters and validators for GET input
        $filters = array(
            'roleID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'roleID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $roleEditForm = new Peshkov_Form_Role_Edit();
            $this->view->roleEditForm = $roleEditForm;

            if ($this->getRequest()->isPost()) {
                if ($roleEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $roleEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_Role')->find($requestData->roleID);

                    if (!$formData['ParentRoleID']){
                        $formData['ParentRoleID'] = null;
                    }

                    if (!$formData['Description']){
                        $formData['Description'] = null;
                    }

                    $item->fromArray($formData);

                    $item->DateEdit = date('Y-m-d H:i:s');

                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminRoleIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'roleID' => $requestData->roleID),
                        'adminRoleID'
                    );

                    $this->redirect($adminRoleIDUrl);
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
                    ->from('Default_Model_Role r')
                    ->where('r.ID = ?', $requestData->roleID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->roleData = $result[0];
                    $this->view->roleEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемая роль не найдена!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Роль не найдена!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Роль не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete role
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить роль'));

        // set filters and validators for GET input
        $filters = array(
            'roleID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'roleID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Role r')
                ->where('r.ID = ?', $requestData->roleID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create role delete form
                $roleDeleteForm = new Peshkov_Form_Role_Delete();

                $this->view->roleData = $result[0];
                $this->view->roleDeleteForm = $roleDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить рольы')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($roleDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Role r')
                            ->whereIn('r.ID', $requestData->roleID);

                        $result = $query->execute();

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Роль <strong>" . $this->view->roleData['Name'] . "</strong> успешно удалена."
                            )
                        );

                        $adminRoleAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'role', 'action' => 'all', 'page' => 1),
                            'adminRoleAll'
                        );

                        $this->redirect($adminRoleAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая роль не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Роль не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Роль не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
