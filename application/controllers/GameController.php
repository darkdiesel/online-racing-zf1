<?php

class GameController extends App_Controller_FirstBootController {

    public function idAction() {
        $request = $this->getRequest();
        $game_id = (int) $request->getParam('id');

        $mapper = new Application_Model_GameMapper();
        $game_data = $mapper->getGameDataById($game_id, 'view');

        if ($game_data == 'null') {
            $this->view->errMessage = $this->view->translate('Игра не существует');
            $this->view->headTitle($this->view->translate('Игра не существует'));
            return;
        } else {
            $this->view->article_type = $game_data;
            $this->view->headTitle($game_data->name);
        }
    }

    public function addAction() {
        // page title
        $this->view->headTitle($this->view->translate('Добавить игру'));
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_ArticleAddForm();
        $form->setAction('/game/add');

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $mapper = new Application_Model_ArticleTypeMapper();
                $article_type = $mapper->getArticleTypeDataById($form->getValue('article_type'), 'view');
                if (strtolower($article_type->name) == 'game') {
                    /* $article = new Application_Model_Article();
                      $article->setUser_id(Zend_Auth::getInstance()->getStorage('online-racing')->read()->id);
                      $article->setArticle_Type_id($form->getValue('article_type'));
                      $article->setContent_Type_id(0);
                      $article->setTitle($form->getValue('title'));
                      $article->setText($form->getValue('text'));
                      $article->setImage($form->getValue('image'));
                      $article->setPublish($form->getValue('publish'));

                      $article_mapper = new Application_Model_ArticleMapper();
                      $article_id = $article_mapper->save($article, 'add');

                      $game = new Application_Model_Game();
                      $game->setName($form->getValue('title'));
                      $game->setArticle_Id($article_id);

                      $game_mapper = new Application_Model_GameMapper();
                      $game_id = $game_mapper->save($game, 'add'); */

                    $date = date('Y-m-d H:i:s');

                    $article_data = array(
                        'user_id' => Zend_Auth::getInstance()->getStorage('online-racing')->read()->id,
                        'article_type_id' => $form->getValue('article_type'),
                        'content_type_id' => 0,
                        'title' => $form->getValue('title'),
                        'text' => $form->getValue('text'),
                        'image' => $form->getValue('image'),
                        'publish' => $form->getValue('publish'),
                        'date_create' => $date,
                        'date_edit' => $date,
                    );

                    $article = new Application_Model_DbTable_Article();
                    $newArticle = $article->createRow($article_data);
                    $newArticle->save();

                    $game = new Application_Model_DbTable_Game();
                    $game_data = array(
                        'name' => $form->getValue('title'),
                        'article_id' => $newArticle->id
                    );
                    $newGame = $game->createRow($game_data);
                    $newGame->save();

                    $this->redirect($this->view->baseUrl('game/id/' . $newGame->id));
                } else {
                    $this->view->errMessage = $this->view->translate('Тип статьи должен быть game!');
                }
            }
        }

        $mapper = new Application_Model_ArticleTypeMapper();
        $game_types = $mapper->fetchAll();

        foreach ($game_types as $type):
            $form->article_type->addMultiOption($type->id, $type->name);
            if (strtolower($type->name) == 'game') {
                $form->article_type->setValue($type->id, $type->name);
            }
        endforeach;

        $this->view->form = $form;
    }

    public function editAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        $game_id = (int) $request->getParam('id');

        // form
        $form = new Application_Form_ArticleEditForm();
        $form->setAction('/game/edit/' . $game_id);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $game = new Application_Model_Article();
                $game->setArticle_Type_id($form->getValue('article_type'));
                $game->setContent_Type_id(0);
                $game->setId($game_id);
                $game->setTitle($form->getValue('title'));
                $game->setText($form->getValue('text'));
                $game->setImage($form->getValue('image'));
                $game->setPublish($form->getValue('publish'));

                $mapper = new Application_Model_ArticleMapper();
                $mapper->save($game, 'edit');

                $this->redirect($this->view->baseUrl('game/id/' . $game_id));
            }
        }

        $mapper = new Application_Model_ArticleMapper();
        $game_data = $mapper->getArticleDataById($game_id, 'edit');

        if ($game_data == 'null') {
            $this->view->errMessage = $this->view->translate('Игра не существует');
            $this->view->headTitle($this->view->translate('Игра не существует'));
            return;
        } else {

            $mapper = new Application_Model_ArticleTypeMapper();
            $game_types = $mapper->fetchAll();

            foreach ($game_types as $type):
                $form->game_type->addMultiOption($type->id, $type->name);
            endforeach;

            $this->view->headTitle($this->view->translate('Редактировать') . ' → ' . $game_data->title);

            $form->title->setvalue($game_data->title);
            $form->text->setvalue($game_data->text);
            $form->image->setvalue($game_data->image);
            $form->publish->setvalue($game_data->publish);

            $this->view->form = $form;
        }
    }

}