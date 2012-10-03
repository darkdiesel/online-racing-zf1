<?php

class ArticleController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->_helper->redirector('view', 'article');
    }

    public function addAction()
    {
        // page title
        $this->view->headTitle($this->view->translate('Добавление контента'));
        
        $request = $this->getRequest();
        // form
        $form = new Application_Form_ArticleAddForm();
        
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                
            }
        }
        
        $this->view->form = $form;
    }

    public function deleteAction()
    {
        // action body
    }

    public function editAction()
    {
        // action body
    }

    public function viewAction()
    {
        // action body
    }


}









