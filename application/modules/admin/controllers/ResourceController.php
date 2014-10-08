<?php

class Admin_ResourceController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Ресурс'));
    }

    public function idAction()
    {
        $request = $this->getRequest();
        $resource_id = (int)$request->getParam('resource_id');

        $resource_data = $this->db->get('resource')->getItem($resource_id);

        if ($resource_data) {
            $this->view->resource = $resource_data;

            $privileges_data = $this->db->get('privilege')->getAll(array('resource_id' => $resource_id));

            if ($privileges_data) {
                $this->view->privileges_data = $privileges_data;
            } else {
                $this->messages->addWarning($this->view->translate('Для данного ресурса не создано привлегий!'));
            }

            $this->view->headTitle($resource_data->name);
            $this->view->pageTitle($this->view->translate("Ресурс :: ") . $resource_data->name);
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Ресурс не найден!'));
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

    // action for view all resources
    public function allAction()
    {
        $this->view->headTitle($this->view->translate('Все'));
        $this->view->pageTitle($this->view->translate('Ресурсы сайта'));

        // pager settings
        $pager_args = array(
            "page_count_items" => 10,
            "page_range" => 5,
            "page" => $this->getRequest()->getParam('page')
        );

        $paginator = $this->db->get("resource")->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

        if ($paginator) {
            $this->view->paginator = $paginator;
        } else {
            $this->messages->addInfo("{$this->view->translate('Запрашиваемые ресурсы на сайте не найдены!')}");
        }
    }

    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить ресурс'));

        // add scripts
        $this->view->headScript()->appendFile("/js/admin/resource.js");

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Resource_Add();
        $form->setAction(
            $this->view->url(
                array('module' => 'admin', 'controller' => 'resource', 'action' => 'add'), 'default', true
            )
        );

        $resource_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'all'), 'adminResourceAll', true);

        $form->cancel->setAttrib('onClick', "location.href=\"{$resource_all_url}\"");

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $new_resource_data = array(
                    'name' => strtolower($form->getValue('name')),
                    'module' => strtolower($form->getValue('module')),
                    'controller' => strtolower($form->getValue('controller')),
                    'parent_resource_id' => strtolower($form->getValue('parent_resource')),
                    'description' => $form->getValue('description'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $new_resource = $this->db->get('resource')->createRow($new_resource_data);
                $new_resource->save();

                $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'id', 'resource_id' => $new_resource->id), 'adminResourceId', true));
            } else {
                $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        $this->view->form = $form;
    }

    // action for editing resource
    public function editAction()
    {
        $request = $this->getRequest();
        $resource_id = (int)$request->getParam('resource_id');

        // add scripts
        $this->view->headScript()->appendFile("/js/admin/resource.js");

        $this->view->headTitle($this->view->translate('Редактировать'));

        $resource_data = $this->db->get('resource')->getItem($resource_id);

        if ($resource_data) {
            // form
            $form = new Application_Form_Resource_Edit();
            $form->setAction($this->view->url(
                array('module' => 'admin', 'controller' => 'resource', 'action' => 'edit',
                    'resource_id' => $resource_id), 'adminResourceAction', true
            ));

            $resource_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'id', 'resource_id' => $resource_id), 'adminResourceId', true);

            $form->cancel->setAttrib('onClick', "location.href=\"{$resource_id_url}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $check_resource_data = $this->db->get('resource')->getItem(array('name' => strtolower($form->getValue('name'))));

                    $update_resource = TRUE;

                    if ($check_resource_data) {
                        if ($check_resource_data->id != $resource_id) {
                            $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                            $this->messages->addError($this->view->translate('Ресурс с именем "' . strtolower($form->getValue('name')) . '" уже существует!'));
                            $update_resource = FALSE;
                        }
                    }

                    if ($update_resource) {
                        $new_resource_data = array(
                            'name' => strtolower($form->getValue('name')),
                            'module' => strtolower($form->getValue('module')),
                            'controller' => strtolower($form->getValue('controller')),
                            'parent_resource_id' => strtolower($form->getValue('parent_resource')),
                            'description' => $form->getValue('description'),
                            'date_edit' => date('Y-m-d H:i:s')
                        );

                        $resource_where = $this->db->get('resource')->getAdapter()->quoteInto('id = ?', $resource_id);
                        $this->db->get('resource')->update($new_resource_data, $resource_where);

                        $this->redirect($resource_id_url);
                    }
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }
            $this->view->headTitle($resource_data->name);
            $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$resource_data->name}");

            $form->name->setvalue($resource_data->name);
            $form->module->setvalue($resource_data->module);
            $form->controller->setvalue($resource_data->controller);
            $form->parent_resource->setvalue($resource_data->parent_resource_id);
            $form->description->setvalue($resource_data->description);

            $this->view->form = $form;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Ресурс не найден!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Ресурс не найден!')}");
        }
    }

    // action for delete resource
    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $resource_id = (int)$request->getParam('resource_id');

        $resource_data = $this->db->get('resource')->getItem($resource_id);

        if ($resource_data) {
            $this->view->headTitle($resource_data->name);
            $this->view->pageTitle("{$this->view->translate('Удалить ресурс')} :: {$resource_data->name}");

            $this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить ресурс')} <strong>\"{$resource_data->name}\"</strong> ?");

            $form = new Application_Form_Resource_Delete();
            $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'delete', 'resource_id' => $resource_id), 'adminResourceAction', true));
            $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'id', 'resource_id' => $resource_id), 'adminResourceId', true) . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $resource_where = $this->db->get('resource')->getAdapter()->quoteInto('id = ?', $resource_id);
                    $this->db->get('resource')->delete($resource_where);

                    $this->messages->clearMessages();
                    $this->messages->addSuccess("{$this->view->translate("Ресурс <strong>\"{$resource_data->name}\"</strong> успешно удален")}");

                    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'all', 'page' => 1), 'adminResourceAll', true));
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $this->view->form = $form;
            $this->view->resource = $resource_data;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Ресурс не найден!'));
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

    public function addPrivilegeAction()
    {
        $this->view->headTitle($this->view->translate('Добавить привилегию ресурсу'));

        $request = $this->getRequest();
        $resource_id = (int)$request->getParam('resource_id');

        $resource_data = $this->db->get('resource')->getItem($resource_id);

        if ($resource_data) {
            $this->view->headTitle($resource_data->name);
            $this->view->pageTitle($this->view->translate('Добавить привилегию ресурсу') . " : " . $resource_data->name);

            $form = new Application_Form_Resource_AddPrivilege();
            $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'add-privilege', 'resource_id' => $resource_id), 'adminResourceAction', true));
            $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'id', 'resource_id' => $resource_id), 'adminResourceId', true) . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $check_privilege_data = $this->db->get('privilege')->getItem(
                        array(
                            'name' => strtolower($form->getValue('name')),
                            'resource_id' => array(
                                'value' => $form->getValue('resource'),
                                'condition' => 'AND'
                            )
                        )
                    );

                    if ($check_privilege_data) {
                        $this->messages->addError($this->view->translate('Для данного ресурса эта привиления уже присутствует в базе данных!'));
                        $add_privilege = FALSE;
                    } else {
                        $add_privilege = TRUE;
                    }

                    if ($add_privilege) {
                        $date = date('Y-m-d H:i:s');
                        $new_privilege_data = array(
                            'name' => strtolower($form->getValue('name')),
                            'resource_id' => $form->getValue('resource'),
                            'description' => $form->getValue('description'),
                            'date_create' => $date,
                            'date_edit' => $date,
                        );

                        $new_privilege = $this->db->get('privilege')->createRow($new_privilege_data);
                        $new_privilege->save();

                        $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'id', 'privilege_id' => $new_privilege->id), 'adminPrivilegeId', true));
                    }
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            // add resource to form
            $form->resource->addMultiOptions(array($resource_data->id => $resource_data->name));
            $form->resource->setvalue($resource_data->id);

            $this->view->form = $form;
            $this->view->resource_data = $resource_data;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Ресурс не найден!'));
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

    public function addResourceAccessAction()
    {
        $this->view->headTitle($this->view->translate('Настроить доступ к ресурсу'));

        $request = $this->getRequest();
        $resource_id = (int)$request->getParam('resource_id');

        $resource_data = $this->db->get('resource')->getItem($resource_id);

        if ($resource_data) {
            $this->view->headTitle($resource_data->name);
            $this->view->pageTitle($this->view->translate('Настроить доступ к ресурсу') . " : " . $resource_data->name);

            $form = new Application_Form_Resource_AddResourceAccess();
            $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'add-resource-access', 'resource_id' => $resource_id), 'adminResourceAction', true));
            $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'id', 'resource_id' => $resource_id), 'adminResourceId', true) . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $date = date('Y-m-d H:i:s');
                    $new_resource_access_data = array(
                        'role_id' => $form->getValue('role'),
                        'resource_id' => $form->getValue('resource'),
                        'privilege_id' => $form->getValue('privilege'),
                        'allow' => $form->getValue('allow'),
                        'date_create' => $date,
                        'date_edit' => $date,
                    );

                    $check_resource_access_data = $this->db->get('resource_access')->getItem(
                        array(
                            'role_id' => $new_resource_access_data['role_id'],
                            'resource_id' => array(
                                'value' => $new_resource_access_data['resource_id'],
                                'condition' => 'AND'
                            ),
                            'privilege_id' => array(
                                'value' => $new_resource_access_data['privilege_id'],
                                'condition' => 'AND'
                            ),
                        ));

                    if ($check_resource_access_data) {
                        $this->messages->addError(
                            $this->view->translate('Доступ для выбранного ресурса, привилегии и роли уже существуют. Отредактируйте уже созданный доступ либо поменяйте параметры для добавления нового.')
                        );
                    } else {
                        $new_resource_access = $this->db->get('resource_access')->createRow($new_resource_access_data);
                        $new_resource_access->save();

                        $this->redirect(
                            $this->view->url(
                                array('module' => 'admin', 'controller' => 'resource-access', 'action' => 'id', 'resource_access_id' => $new_resource_access->id), 'adminResourceAccessId', true
                            )
                        );
                    }
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }
            // add resource to form
            $form->resource->addMultiOptions(array($resource_data->id => $resource_data->name));
            $form->resource->setvalue($resource_data->id);

            $privilege_data = $this->db->get('privilege')->getAll(array('resource_id' => $resource_data->id));

            if ($privilege_data) {
                foreach ($privilege_data as $privilege) {
                    $form->privilege->addMultiOptions(array($privilege->id => $privilege->name));
                }
            } else {
                $this->messages->addError($this->view->translate('У данного ресурса нет привилегий!'));
            }

            $this->view->form = $form;
            $this->view->resource_data = $resource_data;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Ресурс не найден!'));
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

}
