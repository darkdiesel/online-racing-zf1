<?php

class GameController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/articles.css"));
    }

    public function idAction() {
        $request = $this->getRequest();
        $game_id = (int) $request->getParam('id');

        $game = new Application_Model_DbTable_Game();
        $game_data = $game->fetchRow(array('id = ?' => $game_id));

        if (count($game_data) != 0) {

            $article = new Application_Model_DbTable_Article();
            $article_data = $article->getArticleData($game_data->article_id);

            if ($article_data) {
                $this->view->article = $article_data;
                $this->view->headTitle($article_data->title);
                $this->view->article = $article_data;
                $this->view->game = $game_data;
            } else {
                $this->view->errMessage = $this->view->translate('Контент для игры не найден');
                $this->view->headTitle($this->view->translate('Контент для игры не найден'));
                return;
            }
        } else {
            $this->view->errMessage = $this->view->translate('Игра не существует');
            $this->view->headTitle($this->view->translate('Игра не существует'));
            return;
        }
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Игры'));

        $article_type = new Application_Model_DbTable_ArticleType();
        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $article_type_id = $article_type->getId('game');
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $article = new Application_Model_DbTable_Article();
        $this->view->paginator = $article->getAllArticlesPagerByType($page_count_items, $page, $page_range, $article_type_id, $items_order);
    }

    public function addAction() {
        // page title
        $this->view->headTitle($this->view->translate('Добавить игру'));
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Article_Add();
        $form->setAction('/game/add');

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $article_type = new Application_Model_DbTable_ArticleType();
                $article_type_name = $article_type->getName($form->getValue('article_type'));

                if ($article_type_name == 'game') {
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

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_types = $article_type->fetchAll();

        foreach ($article_types as $type):
            $form->article_type->addMultiOption($type->id, $type->name);
            if ($type->name == 'game') {
                $form->article_type->setValue($type->id, $type->name);
            }
        endforeach;

        $content_type = new Application_Model_DbTable_ContentType();
        $content_types = $content_type->fetchAll();

        foreach ($content_types as $type):
            $form->content_type->addMultiOption($type->id, $type->name);
        endforeach;

        $this->view->form = $form;
    }

    public function editAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        $game_id = (int) $request->getParam('id');

        $game = new Application_Model_DbTable_Game();
        $game_data = $game->fetchRow(array('id = ?' => $game_id));

        if (count($game_data) != 0) {
            $article = new Application_Model_DbTable_Article();
            $article_data = $article->fetchRow(array('id = ?' => $game_data->article_id));

            if (count($article_data) != 0) {
                // form
                $form = new Application_Form_Article_Edit();
                $form->setAction('/game/edit/' . $game_id);
                $form->cancel->setAttrib('onClick', 'location.href="/game/id/' . $game_id . '"');

                if ($this->getRequest()->isPost()) {
                    if ($form->isValid($request->getPost())) {
                        if ($article_data->article_type_id == $form->getValue('article_type')) {
                            // article_type not changed
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
                            $article_where = $game->getAdapter()->quoteInto('id = ?', $game_data->article_id);
                            $article->update($article_data, $article_where);

                            $game_data = array(
                                'name' => $form->getValue('title'),
                            );
                            $game_where = $game->getAdapter()->quoteInto('id = ?', $game_id);
                            $game->update($game_data, $game_where);

                            $this->redirect($this->view->baseUrl('game/id/' . $game_id));
                        } else {
                            //article type changed
                            $this->view->errMessage = $this->view->translate('Нельзя менять тип статьи для игры! Тип статьи должен быть game!');
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
                $form->article_type->setvalue($article_data->article_type);
                $form->content_type->setvalue($article_data->content_type);
                $form->annotation->setvalue($article_data->annotation);
                $form->text->setvalue($article_data->text);
                $form->image->setvalue($article_data->image);
                $form->publish->setvalue($article_data->publish);
                $form->publish_to_slider->setvalue($article_data->publish_to_slider);

                $this->view->form = $form;
                $this->view->article = $article_data;
            } else {
                $this->view->errMessage = $this->view->translate('Контент для игры не найден');
                $this->view->headTitle($this->view->translate('Контент для игры не найден'));
                return;
            }
        } else {
            $this->view->errMessage = $this->view->translate('Игра не существует');
            $this->view->headTitle($this->view->translate('Игра не существует'));
            return;
        }
    }

    public function deleteAction() {
        $request = $this->getRequest();
        $game_id = (int) $request->getParam('id');

        $game = new Application_Model_DbTable_Game();
        $game_data = $game->fetchRow(array('id = ?' => $game_id));

        if (count($game_data) != 0) {
            $article = new Application_Model_DbTable_Article();
            $article_data = $article->getArticleData($game_data->article_id);

            if ($article_data) {
                $form = new Application_Form_Article_Delete();
                $form->setAction('/game/delete/' . $game_id);
                $form->cancel->setAttrib('onClick', 'location.href="/game/id/' . $game_id . '"');

                if ($this->getRequest()->isPost()) {
                    if ($form->isValid($request->getPost())) {
                        // delete article for this game
                        $article_where = $game->getAdapter()->quoteInto('id = ?', $game_data->article_id);
                        $article->delete($article_where);

                        // delete game
                        $game_where = $game->getAdapter()->quoteInto('id = ?', $game_id);
                        $game->delete($game_where);

                        $this->_helper->redirector('all', 'game');
                    } else {
                        $this->view->errMessage = $this->view->translate("Произошла неожиданноя ошибка! Пожалуйста обратитесь к нам и сообщите о ней");
                    }
                }

                $this->view->form = $form;
                $this->view->article = $article_data;
            } else {
                $this->view->errMessage = $this->view->translate('Контент для игры не найден!');
                $this->view->headTitle($this->view->translate('Контент для игры не найден!'));
                return;
            }
        } else {
            $this->view->errMessage = $this->view->translate('Игра не найдена!');
            $this->view->headTitle($this->view->translate('Игра не найдена!'));
            return;
        }
    }

}