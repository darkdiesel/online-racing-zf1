<?php

class ArticleController extends Zend_Controller_Action {

    public function init() {
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/articles.css"));
    }

    public function addAction() {
        // page title
        $this->view->headTitle($this->view->translate('Добавление контента'));
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_ArticleAddForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $article = new Application_Model_Article();
                $article->setUser_id(Zend_Auth::getInstance()->getStorage('online-racing')->read()->id);
                $article->setArticle_Type_id($form->getValue('article_type'));
                $article->setContent_Type_id(0);
                $article->setTitle($form->getValue('title'));
                $article->setText($form->getValue('text'));
                $article->setImage($form->getValue('image'));
                $article->setPublish($form->getValue('publish'));
                
                $mapper = new Application_Model_ArticleMapper();
                $mapper->save($article, 'add');
                
                $this->_helper->redirector('all', 'article');
            }
        }

        $this->view->form = $form;
    }

    public function deleteAction() {
        // action body
    }

    public function editAction() {
        // action body
    }

    public function viewAction() {
        $request = $this->getRequest();
        $article_id = $request->getParam('id');
        
        if ($request->getParam('id') == 0) {
            $this->view->errMessage = $this->view->translate('Статья не существует');
            return;
        }
        
        $mapper = new Application_Model_ArticleMapper();
        $article_data = $mapper->getArticleDataById($article_id);
        
        if ($article_data == 'null') {
            $this->view->errMessage = $this->view->translate('Статья не существует');
            return;
        } else {
            $this->view->article = $article_data;
            $this->view->headTitle($article_data->title);
        }
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Контент сайта'));

        $request = $this->getRequest();
        $mapper = new Application_Model_ArticleMapper();
        $this->view->paginator = $mapper->getArticlesPager(10, $request->getParam('page'), 5,1,'all');
    }

}