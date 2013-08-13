<?php

class Admin_ResourceController extends App_Controller_FirstBootController {

    public function init() {
	parent::init();
	$this->view->headTitle($this->view->translate('Ресурс'));
    }

    public function idAction(){
	$request = $this->getRequest();
        $resource_id = (int) $request->getParam('resource_id');
	
        $resource = new Application_Model_DbTable_Resource();
        $resource_data = $resource->getResource($resource_id);

        if ($resource_data) {
            //$this->view->breadcrumb()->PostAll('1')->Post($post_id, $post_data->title);
            $this->view->resource = $resource_data;
            $this->view->headTitle($resource_data->name);
            $this->view->pageTitle($resource_data->name);
        } else {
            $this->messageManager->addError($this->view->translate('Запрашиваемый ресурс не существует!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Ресурс не существует!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Ресурс не существует!')}");
        }
    }

    public function addAction() {
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
			    'resource_id' => $new_resource->id), 'resourceId', true
			)
		);
	    } else {
		$this->messageManager->addError($this->view->translate('Исправте следующие ошибки для добавления типа контента!'));
	    }
	}

	$this->view->form = $form;
    }

}