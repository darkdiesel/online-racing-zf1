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
	$resource_id = (int) $request->getParam('resource_id');

	$resource_data = $this->db->get('resource')->getItem($resource_id);

	if ($resource_data) {
	    $this->view->resource = $resource_data;
	    $this->view->headTitle($resource_data->name);
	    $this->view->pageTitle($this->view->translate("Ресурс :: ").$resource_data->name);
	} else {
	    $this->messages->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Ресурс не найден!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Ресурс не найден!')}");
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

	if (count($paginator)) {
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

	if ($this->getRequest()->isPost()) {
	    if ($form->isValid($request->getPost())) {
		$date = date('Y-m-d H:i:s');
		$new_resource_data = array(
		    'name' => strtolower($form->getValue('name')),
		    'module' => strtolower($form->getValue('module')),
		    'controller' => strtolower($form->getValue('controller')),
		    'action' => strtolower($form->getValue('action')),
		    'parent_resource_id' => strtolower($form->getValue('parent_resource')),
		    'description' => $form->getValue('description'),
		    'date_create' => $date,
		    'date_edit' => $date,
		);

		$new_resource = $this->db->get('resource')->createRow($new_resource_data);
		$new_resource->save();

		$this->redirect(
			$this->view->url(
				array('module' => 'admin', 'controller' => 'resource', 'action' => 'id',
			    'resource_id' => $new_resource->id), 'resource_id', true
			)
		);
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
	$resource_id = (int) $request->getParam('resource_id');
	
	// add scripts
	$this->view->headScript()->appendFile("/js/admin/resource.js");

	$this->view->headTitle($this->view->translate('Редактировать'));

	$resource_data = $this->db->get('resource')->getItem($resource_id);

	if ($resource_data) {
	    // form
	    $form = new Application_Form_Resource_Edit();
	    $form->setAction($this->view->url(
			    array('module' => 'admin', 'controller' => 'resource', 'action' => 'edit',
			'resource_id' => $resource_id), 'resource_action', true
	    ));
	    $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'all'), 'default', true)}\"");

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $new_resource_data = array(
			'name' => strtolower($form->getValue('name')),
			'module' => strtolower($form->getValue('module')),
			'controller' => strtolower($form->getValue('controller')),
			'action' => strtolower($form->getValue('action')),
			'parent_resource_id' => strtolower($form->getValue('parent_resource')),
			'description' => $form->getValue('description'),
			'date_edit' => date('Y-m-d H:i:s')
		    );

		    $resource_where = $this->db->get('resource')->getAdapter()->quoteInto('id = ?', $resource_id);
		    $this->db->get('resource')->update($new_resource_data, $resource_where);

		    $this->redirect($this->view->url(
				    array('module' => 'admin', 'controller' => 'resource', 'action' => 'id',
				'resource_id' => $resource_id), 'resource_id', true
		    ));
		} else {
		    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }
	    $this->view->headTitle($resource_data->name);
	    $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$resource_data->name}");

	    $form->name->setvalue($resource_data->name);
	    $form->module->setvalue($resource_data->module);
	    $form->controller->setvalue($resource_data->controller);
	    $form->action->setvalue($resource_data->action);
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
	$resource_id = (int) $request->getParam('resource_id');

	$resource_data = $this->db->get('resource')->getItem($resource_id);

	if ($resource_data) {
	    $this->view->headTitle($resource_data->name);
	    $this->view->pageTitle("{$this->view->translate('Удалить ресурс')} :: {$resource_data->name}");

	    $this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить ресурс')} <strong>\"{$resource_data->name}\"</strong> ?");

	    $form = new Application_Form_Resource_Delete();
	    $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'delete', 'resource_id' => $resource_id), 'resource_action', true));
	    $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'id', 'resource_id' => $resource_id), 'resource_id', true) . '"');

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $resource_where = $this->db->get('resource')->getAdapter()->quoteInto('id = ?', $resource_id);
		    $this->db->get('resource')->delete($resource_where);

		    $this->messages->clearMessages();
		    $this->messages->addSuccess("{$this->view->translate("Ресурс <strong>\"{$resource_data->name}\"</strong> успешно удален")}");

		    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'all', 'page' => 1), 'resource_all', true));
		} else {
		    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }

	    $this->view->form = $form;
	    $this->view->resource = $resource_data;
	} else {
	    $this->messages->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Ресурс не существует!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Ресурс не существует!')}");
	}
    }

}