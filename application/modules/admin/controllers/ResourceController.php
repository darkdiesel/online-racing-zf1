<?php

class Admin_ResourceController extends App_Controller_FirstBootController
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

	$resource = new Application_Model_DbTable_Resource();
	$resource_data = $resource->getItem($resource_id);

	if ($resource_data) {
	    //$this->view->breadcrumb()->PostAll('1')->Post($post_id, $post_data->title);
	    $this->view->resource = $resource_data;
	    $this->view->headTitle($resource_data->name);
	    $this->view->pageTitle($resource_data->name);
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Ресурс не найден!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Ресурс не найден!')}");
	}
    }

    // action for view all resources
    public function allAction()
    {
	$this->view->headTitle($this->view->translate('Ресурсы сайта'));
	$this->view->pageTitle($this->view->translate('Ресурсы сайта'));

	$resource = new Application_Model_DbTable_Resource();

	// pager settings
	$pager_args = array(
	    "page_count_items" => 10,
	    "page_range" => 5,
	    "page" => $this->getRequest()->getParam('page')
	);

	$paginator = $resource->getAll("all", "ASC", TRUE, $pager_args);

	if (count($paginator)) {
	    $this->view->paginator = $paginator;
	} else {
	    $this->messageManager->addInfo("{$this->view->translate('Запрашиваемые ресурсы на сайте не найдены!')}");
	}
    }

    public function addAction()
    {
	$this->view->headTitle($this->view->translate('Добавить'));
	$this->view->pageTitle($this->view->translate('Добавить ресурс'));

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
		    'controller' => strtolower($form->getValue('controller')),
		    'action' => strtolower($form->getValue('action')),
		    'description' => $form->getValue('description'),
		    'date_create' => $date,
		    'date_edit' => $date,
		);

		$resource = new Application_Model_DbTable_Resource();
		$new_resource = $resource->createRow($new_resource_data);
		$new_resource->save();

		$this->redirect(
			$this->view->url(
				array('module' => 'admin', 'controller' => 'resource', 'action' => 'id',
			    'resource_id' => $new_resource->id), 'resource_id', true
			)
		);
	    } else {
		$this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
	    }
	}

	$this->view->form = $form;
    }

    // action for editing resource
    public function editAction()
    {
	$request = $this->getRequest();
	$resource_id = (int) $request->getParam('resource_id');

	$this->view->headTitle($this->view->translate('Редактировать'));

	$resource = new Application_Model_DbTable_Resource();
	$resource_data = $resource->getItem($resource_id);

	if ($resource_data) {
	    // form
	    $form = new Application_Form_Resource_Edit();
	    $form->setAction($this->view->url(
			    array('module' => 'admin', 'controller' => 'resource', 'action' => 'edit',
			'resource_id' => $resource_id), 'resource_actions', true
	    ));
	    $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'all'), 'default', true)}\"");

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $new_resource_data = array(
			'name' => strtolower($form->getValue('name')),
			'controller' => strtolower($form->getValue('controller')),
			'action' => strtolower($form->getValue('action')),
			'description' => $form->getValue('description'),
			'date_edit' => date('Y-m-d H:i:s')
		    );

		    $resource_where = $resource->getAdapter()->quoteInto('id = ?', $resource_id);
		    $resource->update($new_resource_data, $resource_where);

		    $this->redirect($this->view->url(
				    array('module' => 'admin', 'controller' => 'resource', 'action' => 'id',
				'resource_id' => $resource_id), 'resource_id', true
		    ));
		} else {
		    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }
	    $this->view->headTitle($resource_data->name);
	    $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$resource_data->name}");

	    $form->name->setvalue($resource_data->name);
	    $form->controller->setvalue($resource_data->controller);
	    $form->action->setvalue($resource_data->action);
	    $form->description->setvalue($resource_data->description);

	    $this->view->form = $form;
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
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

	$resource = new Application_Model_DbTable_Resource();
	$resource_data = $resource->getItem($resource_id);

	if ($resource_data) {
	    $this->view->headTitle($resource_data->name);
	    $this->view->pageTitle("{$this->view->translate('Удалить ресурс')} :: {$resource_data->name}");

	    $this->messageManager->addWarning("{$this->view->translate('Вы действительно хотите удалить русурс')} <strong>\"{$resource_data->name}\"</strong> ?");

	    $form = new Application_Form_ArticleType_Delete();
	    $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'delete', 'resource_id' => $resource_id), 'resource_actions', true));
	    $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'id', 'resource_id' => $resource_id), 'resource_id', true) . '"');

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $resource_where = $resource->getAdapter()->quoteInto('id = ?', $resource_id);
		    $resource->delete($resource_where);

		    $this->view->showMessages()->clearMessages();
		    $this->messageManager->addSuccess("{$this->view->translate("Ресурс <strong>\"{$resource_data->name}\"</strong> успешно удален")}");

		    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'resource', 'action' => 'all', 'page' => 1), 'resource_all', true));
		} else {
		    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }

	    $this->view->form = $form;
	    $this->view->resource = $resource_data;
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемый ресурс не найден!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Ресурс не существует!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Ресурс не существует!')}");
	}
    }

}