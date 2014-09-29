<?php

class Admin_RoleController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Роли Пользователей'));

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
                ->where('r.ID = ?', $requestData->roleID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->roleData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->translate('Роль') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемая роль не найдена!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Роль не найдена!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Роль не найдена!'));
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
            $this->view->headTitle($this->view->translate('Все'));
            $this->view->pageTitle($this->view->translate('Роли пользователей'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Role r')
                ->orderBy('r.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $rolePaginator = new Zend_Paginator($adapter);
            // pager settings
            $rolePaginator->setItemCountPerPage("10");
            $rolePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $rolePaginator->setPageRange("5");

            if ($rolePaginator->count() == 0) {
                $this->view->roleData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->RoleData = $rolePaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new role
    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить роль'));

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

                $item->fromArray($roleAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $adminRoleIDUrl = $this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'roleID' => $item->ID), 'adminRoleID');

                $this->redirect($adminRoleIDUrl);

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for editing role
    public function editAction()
    {
        $request = $this->getRequest();
        $role_id = (int)$request->getParam('role_id');

        $this->view->headTitle($this->view->translate('Редактировать'));

        $role_data = $this->db->get('role')->getItem($role_id);

        if ($role_data) {
            // form
            $form = new Application_Form_Role_Edit();
            $form->setAction($this->view->url(
                array('module' => 'admin', 'controller' => 'role', 'action' => 'edit',
                    'role_id' => $role_id), 'adminRoleAction', true
            ));
            $admin_role_id = $this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'role_id' => $role_id), 'adminRoleId', true);
            $form->cancel->setAttrib('onClick', "location.href=\"{$admin_role_id}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $new_role_data = array(
                        'name' => strtolower($form->getValue('name')),
                        'parent_role_id' => strtolower($form->getValue('parent_role')),
                        'description' => $form->getValue('description'),
                        'date_edit' => date('Y-m-d H:i:s')
                    );

                    $role_where = $this->db->get('role')->getAdapter()->quoteInto('id = ?', $role_id);
                    $this->db->get('role')->update($new_role_data, $role_where);

                    $this->redirect($admin_role_id);
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }
            $this->view->headTitle($role_data->name);
            $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$role_data->name}");

            $form->name->setvalue($role_data->name);
            $form->parent_role->setvalue($role_data->parent_role_id);
            $form->description->setvalue($role_data->description);

            $this->view->form = $form;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемая роль пользователя не найдена!'));
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Роль пользователя не найдена!'));
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

    // action for delete role
    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $role_id = (int)$request->getParam('role_id');

        $role_data = $this->db->get('role')->getItem($role_id);

        if ($role_data) {
            $this->view->headTitle($role_data->name);
            $this->view->pageTitle("{$this->view->translate('Удалить ресурс')} :: {$role_data->name}");

            $this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить ресурс')} <strong>\"{$role_data->name}\"</strong> ?");

            $form = new Application_Form_Role_Delete();
            $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'delete', 'role_id' => $role_id), 'role_action', true));
            $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'role_id' => $role_id), 'role_id', true) . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $role_where = $this->db->get('role')->getAdapter()->quoteInto('id = ?', $role_id);
                    $this->db->get('role')->delete($role_where);

                    $this->messages->clearMessages();
                    $this->messages->addSuccess("{$this->view->translate("Роль пользователя <strong>\"{$role_data->name}\"</strong> успешно удалена")}");

                    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'all', 'page' => 1), 'adminRoleAll', true));
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $this->view->form = $form;
            $this->view->role = $role_data;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемая роль пользователя не найдена!'));
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Роль пользователя не найдена!'));
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

}
