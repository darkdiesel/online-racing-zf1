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
                // save new article to db
                $date = date('Y-m-d H:i:s');
                $article_data = array(
                    'user_id' => Zend_Auth::getInstance()->getStorage('online-racing')->read()->id,
                    'article_type_id' => $form->getValue('article_type'),
                    'content_type_id' => $form->getValue('content_type'),
                    'title' => $form->getValue('title'),
                    'annotation' => $form->getValue('annotation'),
                    'text' => $form->getValue('text'),
                    'image' => $form->getValue('image'),
                    'publish' => $form->getValue('publish'),
                    'publish_to_slider' => $form->getValue('publish'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $article = new Application_Model_DbTable_Article();
                $newArticle = $article->createRow($article_data);
                $newArticle->save();

                $article_type = new Application_Model_DbTable_ArticleType();
                $article_type_name = $article_type->get_name($form->getValue('article_type'));

                // save additional information corespondig article_type to db
                switch ($article_type_name) {
                    case 'game':
                        $game = new Application_Model_DbTable_Game();
                        $game_data = array(
                            'name' => $form->getValue('title'),
                            'article_id' => $newArticle->id
                        );
                        $newGame = $game->createRow($game_data);
                        $newGame->save();

                        $this->redirect($this->view->baseUrl('game/id/' . $newGame->id));
                        break;
                    default :
                        $this->redirect($this->view->baseUrl('article/id/' . $newArticle->id));
                        break;
                }
            }
        }

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_types = $article_type->fetchAll();

        foreach ($article_types as $type):
            $form->article_type->addMultiOption($type->id, $type->name);
        endforeach;

        $content_type = new Application_Model_DbTable_ContentType();
        $content_types = $content_type->fetchAll();

        foreach ($content_types as $type):
            $form->content_type->addMultiOption($type->id, $type->name);
        endforeach;

        $this->view->form = $form;
    }

    // action for edit article
    public function editAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        $article_id = (int) $request->getParam('id');

        $article = new Application_Model_DbTable_Article();
        $article_data = $article->fetchRow(array('id = ?' => $article_id));

        if (count($article_data) != 0) {
            $form = new Application_Form_ArticleEditForm();
            $form->setAction('/article/edit/' . $article_id);
            $form->cancel->setAttrib('onClick', 'location.href="/article/id/' . $article_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    if ($article_data->article_type_id == $form->getValue('article_type')) {
                        // if article type not changed
                        $article_data = array(
                            'article_type_id' => $form->getValue('article_type'),
                            'content_type_id' => $form->getValue('content_type'),
                            'annotation' => $form->getValue('annotation'),
                            'title' => $form->getValue('title'),
                            'text' => $form->getValue('text'),
                            'image' => $form->getValue('image'),
                            'publish' => $form->getValue('publish'),
                            'publish_to_slider' => $form->getValue('publish_to_slider'),
                            'date_edit' => date('Y-m-d H:i:s'),
                        );
                        $article_where = $article->getAdapter()->quoteInto('id = ?', $article_id);
                        $article->update($article_data, $article_where);

                        $article_type = new Application_Model_DbTable_ArticleType();
                        $article_type_name = $article_type->get_name($form->getValue('article_type'));

                        // save additional information corespondig article_type to db
                        switch ($article_type_name) {
                            case 'game':
                                $game_data = array(
                                    'name' => $form->getValue('title'),
                                );
                                $game_where = $game->getAdapter()->quoteInto('id = ?', $game_id);
                                $game->update($game_data, $game_where);
                                break;
                        }

                        $this->redirect($this->view->baseUrl('article/id/' . $article_id));
                    } else {
                        // if article type changed
                        $this->view->errMessage = $this->view->translate('Функционал для смены типов статьи не готов.');
                    }
                }
            }

            $article_types = new Application_Model_DbTable_ArticleType();
            $article_types = $article_types->fetchAll();

            foreach ($article_types as $type):
                $form->article_type->addMultiOption($type->id, $type->name);
            endforeach;

            $content_type = new Application_Model_DbTable_ContentType();
            $content_types = $content_type->fetchAll();

            foreach ($content_types as $type):
                $form->content_type->addMultiOption($type->id, $type->name);
            endforeach;

            $this->view->headTitle($this->view->translate('Редактировать') . ' → ' . $article_data->title);

            $form->title->setvalue($article_data->title);
            $form->article_type->setvalue($article_data->article_type_id);
            $form->content_type->setvalue($article_data->content_type_id);
            $form->annotation->setvalue($article_data->annotation);
            $form->text->setvalue($article_data->text);
            $form->image->setvalue($article_data->image);
            $form->publish->setvalue($article_data->publish);
            $form->publish_to_slider->setvalue($article_data->publish_to_slider);

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

                $article_type = $article_type_mapper->getArticleTypeNameById($article_data->article_type_id);

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