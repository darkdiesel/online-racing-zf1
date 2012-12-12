<?php

class ArticleTypeController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/admin.css"));
    }

    // action for view article type
    public function idAction() {
        $request = $this->getRequest();
        $article_type_id = (int) $request->getParam('id');

        $mapper = new Application_Model_ArticleTypeMapper();
        $article_type_data = $mapper->getArticleTypeDataById($article_type_id);

        if ($article_type_data == 'null') {
            $this->view->errMessage = $this->view->translate('Тип статьи не существует');
            $this->view->headTitle($this->view->translate('Тип статьи не существует'));
            return;
        } else {
            $this->view->article_type = $article_type_data;
            $this->view->headTitle($article_type_data->name);
        }
    }

    // action for view all article types
    public function allAction() {
        $this->view->headTitle($this->view->translate('Типы статей'));

        $request = $this->getRequest();
        $mapper = new Application_Model_ArticleTypeMapper();
        $this->view->paginator = $mapper->getArticleTypesPager(10, $request->getParam('page'), 5, 'all', 'ASC');
    }

    // action for add new article type
    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить тип статьи'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_ArticleTypeAddForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $article_type = new Application_Model_ArticleType($form->getValues());
                $mapper = new Application_Model_ArticleTypeMapper();
                $mapper->save($article_type, 'add');

                $this->_helper->redirector('all', 'article-type');
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для добавления типа статьи!');
            }
        }

        $this->view->form = $form;
    }

    // action for edit article type
    public function editAction() {
        $request = $this->getRequest();
        $article_type_id = $request->getParam('id');

        // form
        $form = new Application_Form_ArticleTypeEditForm();
        $form->setAction('/article-type/edit/' . $article_type_id);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $article = new Application_Model_ArticleType();
                $article->setId($article_type_id);
                $article->setName($form->getValue('name'));
                $article->setDescription($form->getValue('description'));

                $mapper = new Application_Model_ArticleTypeMapper();
                $mapper->save($article, 'edit');

                $this->redirect($this->view->baseUrl('article-type/id/' . $article_type_id));
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для изминения типа статьи!');
            }
        }

        $mapper = new Application_Model_ArticleTypeMapper();
        $article_type_data = $mapper->getArticleTypeDataById($article_type_id);

        if ($article_type_data == 'null') {
            $this->view->errMessage = $this->view->translate('Тип статьи не существует');
            $this->view->headTitle($this->view->translate('Тип статьи не существует'));
            return;
        } else {
            $this->view->headTitle($this->view->translate('Редактировать') . ' → ' . $article_type_data->name);

            $form->name->setvalue($article_type_data->name);
            $form->description->setvalue($article_type_data->description);

            $this->view->form = $form;
        }
    }

    // action for delete article type
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить тип статьи'));

        $this->view->errMessage = $this->view->translate("Приносим свои извинения. Данный функционал еще не реализован!");
    }

}