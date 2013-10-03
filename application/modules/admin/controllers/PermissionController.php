<?php

class Admin_PermissionController extends App_Controller_LoaderController
{

    public function init()
    {
	parent::init();
	$this->view->headTitle($this->view->translate('Права доступа'));
    }
    
    public function addAction()
    {
	$this->view->headTitle($this->view->translate('Добавить'));
	$this->view->pageTitle($this->view->translate('Добавить права доступа'));

	$request = $this->getRequest();
	// form
	$form = new Application_Form_Permission_Add();
	$form->setAction(
		$this->view->url(
			array('module' => 'admin', 'controller' => 'permission', 'action' => 'add'), 'default', true
		)
	);

	if ($this->getRequest()->isPost()) {
	    if ($form->isValid($request->getPost())) {
		$date = date('Y-m-d H:i:s');
		$new_role_data = array(
		    'role_id' => strtolower($form->getValue('role')),
		    'resource_id' => strtolower($form->getValue('resource')),
		);

		$new_role = $this->db->get('role')->createRow($new_role_data);
		$new_role->save();

		$this->redirect(
			$this->view->url(
				array('module' => 'admin', 'controller' => 'role', 'action' => 'id',
			    'role_id' => $new_role->id), 'role_id', true
			)
		);
	    } else {
		$this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
	    }
	}

	$this->view->form = $form;
    }

}
