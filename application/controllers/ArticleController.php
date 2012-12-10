<?php

class ArticleController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/articles.css"));
        $this->article_model = new Application_Model_DbTable_Article();
    }

    // action for view article
    public function idAction() {
        $request = $this->getRequest();
        $article_id = (int) $request->getParam('id');

        $mapper = new Application_Model_ArticleMapper();
        $article_data = $mapper->getArticleDataById($article_id, 'view');

        if ($article_data == 'null') {
            $this->view->errMessage = $this->view->translate('Статья не существует');
            $this->view->headTitle($this->view->translate('Статья не существует'));
            return;
        } else {
            $this->view->article = $article_data;
            $this->view->headTitle($article_data->title);
        }
    }

    // action for view all articles
    public function allAction() {
        $this->view->headTitle($this->view->translate('Контент сайта'));

        $request = $this->getRequest();
        $mapper = new Application_Model_ArticleMapper();
        $this->view->paginator = $mapper->getArticlesPager(10, $request->getParam('page'), 5, 1, 'all', 'DESC');
    }

    // action for add new article
    public function addAction() {
        // page title
        $this->view->headTitle($this->view->translate('Добавить контент'));
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

        $mapper = new Application_Model_ArticleTypeMapper();
        $article_types = $mapper->fetchAll();

        foreach ($article_types as $type):
            $form->article_type->addMultiOption($type->id, $type->name);
        endforeach;

        $this->view->form = $form;
    }

    // action for edit article
    public function editAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        $article_id = (int) $request->getParam('id');

        // form
        $form = new Application_Form_ArticleEditForm();
        $form->setAction('/article/edit/' . $article_id);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $article = new Application_Model_Article();
                $article->setArticle_Type_id($form->getValue('article_type'));
                $article->setContent_Type_id(0);
                $article->setId($article_id);
                $article->setTitle($form->getValue('title'));
                $article->setText($form->getValue('text'));
                $article->setImage($form->getValue('image'));
                $article->setPublish($form->getValue('publish'));

                $mapper = new Application_Model_ArticleMapper();
                $mapper->save($article, 'edit');

                $this->redirect($this->view->baseUrl('article/id/' . $article_id));
            }
        }

        $mapper = new Application_Model_ArticleMapper();
        $article_data = $mapper->getArticleDataById($article_id, 'edit');

        if ($article_data == 'null') {
            $this->view->errMessage = $this->view->translate('Статья не существует');
            $this->view->headTitle($this->view->translate('Статья не существует'));
            return;
        } else {

            $mapper = new Application_Model_ArticleTypeMapper();
            $article_types = $mapper->fetchAll();

            foreach ($article_types as $type):
                $form->article_type->addMultiOption($type->id, $type->name);
            endforeach;

            $form->cancel->setAttrib('onClick', 'location.href="/article/id/' . $article_id . '"');

            $this->view->headTitle($this->view->translate('Редактировать') . ' → ' . $article_data->title);

            $form->title->setvalue($article_data->title);
            $form->text->setvalue($article_data->text);
            $form->image->setvalue($article_data->image);
            $form->publish->setvalue($article_data->publish);

            $this->view->form = $form;
        }
    }

    // action for delete article
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить статью'));

        $request = $this->getRequest();
        $article_id = (int) $request->getParam('id');

        $form = new Application_Form_ArticleDeleteForm();
        $form->setAction('/article/delete/' . $article_id);

        $form->cancel->setAttrib('onClick', 'location.href="/article/id/' . $article_id . '"');


        $article_mapper = new Application_Model_ArticleMapper();
        $article_data = $article_mapper->getArticleDataById($article_id, 'edit');

        if ($article_data == 'null') {
            $this->view->errMessage = $this->view->translate('Статья не существует');
            $this->view->headTitle($this->view->translate('Статья не существует'));
            return;
        } else {
            $this->view->article = $article_data;
        }

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $article_type_mapper = new Application_Model_ArticleTypeMapper();

                $article_type = $article_type_mapper->getArticleTypeDataById($article_data->article_type_id, 'view');

                switch (strtolower($article_type->name)) {
                    case 'game':
                        $game_model = new Application_Model_DbTable_Game();
                        $game_model->fetchRow('article_id = ' . $article_id)->delete();
                        $this->article_model->fetchRow($this->article_model->select()->where('id = ?', $article_id))->delete();
                        break;
                    case 'news':
                        $this->article_model->fetchRow($this->article_model->select()->where('id = ?', $article_id))->delete();
                        break;
                    default :
                        $this->article_model->fetchRow($this->article_model->select()->where('id = ?', $article_id))->delete();
                        break;
                }
                $this->redirect($this->view->baseUrl('article/all/'));
            }
        }

        $this->view->form = $form;
    }

}