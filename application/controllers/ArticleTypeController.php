<?php

class ArticleTypeController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }

    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить тип статьи'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_ArticleTypeAddForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $article_type = new Application_Model_ArticleType($form->getValues());
                $mapper = new Application_Model_ArticleTypeMapper();
                $mapper->save($article_type, 'add');

                $this->_helper->redirector('all', 'articletype');
            }
        }

        $this->view->form = $form;
    }

    public function deleteAction()
    {
        // action body
    }

    public function allAction()
    {
        // action body
    }

    // action for editing article type
    public function editAction()
    {
        $request = $this->getRequest();
        $article_type_id = $request->getParam('id');

        // form
        $form = new Application_Form_ArticleTypeEditForm();
        $form->setAction('/articletype/edit/' . $article_type_id);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $article = new Application_Model_ArticleType();
                $article->setId($article_type_id);
                $article->setName($form->getValue('name'));
                $article->setDescription($form->getValue('description'));

                $mapper = new Application_Model_ArticleTypeMapper();
                $mapper->save($article, 'edit');

                $this->redirect($this->view->baseUrl('articletype/id/' . $article_type_id));
            }
        }
        
        if ($article_type_id == 0) {
            $this->view->errMessage = $this->view->translate('Тип статьи не существует');
            $this->view->headTitle($this->view->translate('Тип статьи не существует'));
            return;
        }
        
        $mapper = new Application_Model_ArticleTypeMapper();
        $article_type_data = $mapper->getArticleTypeDataById($article_type_id, 'edit');
        
        if ($article_type_data == 'null') {
            $this->view->errMessage = $this->view->translate('Тип статьи не существует');
            $this->view->headTitle($this->view->translate('Тип статьи не существует'));
            return;
        } else {
            $this->view->headTitle($this->view->translate('Редактировать') . ' ' . $article_type_data->name);

            $form->name->setvalue($article_type_data->name);
            $form->description->setvalue($article_type_data->description);
            
            $this->view->form = $form;
        }
    }

    // action for view article type
    public function idAction()
    {
        $request = $this->getRequest();
        $article_id = $request->getParam('id');
    }


}



