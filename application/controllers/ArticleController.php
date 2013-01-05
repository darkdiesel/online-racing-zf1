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

        $article = new Application_Model_DbTable_Article();
        $article_data = $article->getArticleData($article_id);

        if ($article_data) {
            $this->view->article = $article_data;
            $this->view->headTitle($article_data->title);
        } else {
            $this->view->errMessage .= $this->view->translate('Статья не найдена!');
            $this->view->headTitle($this->view->translate('Статья не найдена!'));
        }
    }

    // action for view all articles
    public function allAction() {
        $this->view->headTitle($this->view->translate('Контент сайта'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $article = new Application_Model_DbTable_Article();
        $this->view->paginator = $article->getPublishedArticlesPager($page_count_items, $page, $page_range, $items_order);
    }

    // action for add new article
    public function addAction() {
        // page title
        $this->view->headTitle($this->view->translate('Добавление контента'));
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));
        
        //add css
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/forms.css"));
        
        $request = $this->getRequest();
        // form
        $form = new Application_Form_Article_Add();

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
                    'publish_to_slider' => $form->getValue('publish_to_slider'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $article = new Application_Model_DbTable_Article();
                $newArticle = $article->createRow($article_data);
                $newArticle->save();

                $article_type = new Application_Model_DbTable_ArticleType();
                $article_type_name = $article_type->getName($form->getValue('article_type'));

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

        // add article types to the form
        $article_types = new Application_Model_DbTable_ArticleType();
        $article_types = $article_types->getArticleTypesName('ASC');

        if ($article_types) {
            foreach ($article_types as $type):
                $form->article_type->addMultiOption($type->id, $type->name);
            endforeach;
        } else {
            $this->view->errMessage .= $this->view->translate('Типы статей на сайте не найдены!') . '<br/>'
                    . '<a href=' . $this->baseURL('article-type/add') . '>' . $this->view->translate('Создайте тип статьи, чтобы добавлять контент на сайте.') . '</a><br/>';
        }

        // add content types to the form
        $content_type = new Application_Model_DbTable_ContentType();
        $content_types = $content_type->getContentTypesName('ASC');

        if ($content_types) {
            foreach ($content_types as $type):
                $form->content_type->addMultiOption($type->id, $type->name);
            endforeach;
        } else {
            $this->view->errMessage .= $this->view->translate('Типы контента на сайте не найдены!') . '<br/>'
                    . '<a href=' . $this->baseURL('content-type/add') . '>' . $this->view->translate('Создайте тип контента, чтобы добавлять контент на сайте.') . '</a><br/>';
        }

        $this->view->form = $form;
    }

    // action for edit article
    public function editAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl("includes/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        $article_id = (int) $request->getParam('id');

        $article = new Application_Model_DbTable_Article();
        $article_data = $article->getArticleData($article_id);

        if ($article_data) {
            $form = new Application_Form_Article_Edit();
            $form->setAction('/article/edit/' . $article_id);
            $form->cancel->setAttrib('onClick', 'location.href="/article/id/' . $article_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    if ($article_data->article_type_id == $form->getValue('article_type')) {
                        // if article type not changed do this code
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
                        $article_type_name = $article_type->getName($form->getValue('article_type'));

                        // save additional information corespondig article_type to db
                        switch ($article_type_name) {
                            case 'game':
                                $game = new Application_Model_DbTable_Game();
                                $game_data = array(
                                    'name' => $form->getValue('title'),
                                );
                                $game_where = $game->getAdapter()->quoteInto('id = ?', $game_id);
                                $game->update($game_data, $game_where);
                                break;
                            default :

                                break;
                        }

                        $this->redirect($this->view->baseUrl('article/id/' . $article_id));
                    } else {
                        // if article type changed
                        $this->view->errMessage .= $this->view->translate('Функционал для смены типов статьи не готов.') . '<br/>';
                    }
                }
            }

            // add article types to the form
            $article_types = new Application_Model_DbTable_ArticleType();
            $article_types = $article_types->getArticleTypesName('ASC');

            if ($article_types) {
                foreach ($article_types as $type):
                    $form->article_type->addMultiOption($type->id, $type->name);
                endforeach;
            } else {
                $this->view->errMessage .= $this->view->translate('Типы статей на сайте не найдены!') . '<br/>'
                        . '<a href=' . $this->baseURL('article-type/add') . '>' . $this->view->translate('Создайте тип статьи, чтобы добавлять контент на сайте.') . '</a><br/>';
            }

            // add content types to the form
            $content_type = new Application_Model_DbTable_ContentType();
            $content_types = $content_type->getContentTypesName('ASC');

            if ($content_types) {
                foreach ($content_types as $type):
                    $form->content_type->addMultiOption($type->id, $type->name);
                endforeach;
            } else {
                $this->view->errMessage .= $this->view->translate('Типы контента на сайте не найдены!') . '<br/>'
                        . '<a href=' . $this->baseURL('content-type/add') . '>' . $this->view->translate('Создайте тип контента, чтобы добавлять контент на сайте.') . '</a><br/>';
            }

            //head titles
            $this->view->headTitle($this->view->translate('Редактировать'));
            $this->view->headTitle($article_data->title);

            $form->title->setvalue($article_data->title);
            $form->article_type->setvalue($article_data->article_type_id);
            $form->content_type->setvalue($article_data->content_type_id);
            $form->annotation->setvalue($article_data->annotation);
            $form->text->setvalue($article_data->text);
            $form->image->setvalue($article_data->image);
            $form->publish->setvalue($article_data->publish);
            $form->publish_to_slider->setvalue($article_data->publish_to_slider);

            $this->view->form = $form;
        } else {
            $this->view->errMessage .= $this->view->translate('Статья не найдена!') . '<br/>';
            $this->view->headTitle($this->view->translate('Статья не найдена!'));
        }
    }

    // action for delete article
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удаление статьи'));

        $request = $this->getRequest();
        $article_id = (int) $request->getParam('id');

        $article = new Application_Model_DbTable_Article();
        $article_data = $article->getArticleData($article_id);

        if ($article_data) {
            //page title
            $this->view->headTitle($article_data->title);

            //create delete form
            $form = new Application_Form_Article_Delete();
            $form->setAction('/article/delete/' . $article_id);
            $form->cancel->setAttrib('onClick', 'location.href="/article/id/' . $article_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    $article_where = $article->getAdapter()->quoteInto('id = ?', $article_id);
                    $article->delete($article_where);

                    $article_type = new Application_Model_DbTable_ArticleType();
                    $article_type->getName($article_data->article_type_id);

                    switch ($article_type->name) {
                        case 'game':
                            $game = new Application_Model_DbTable_Game();
                            $game_where = $game->getAdapter()->quoteInto('id = ?', $game_id);
                            $game->delete($game_where);
                            break;
                        case 'news':
                            break;
                        default :
                            break;
                    }

                    $this->_helper->redirector('all', 'article');
                }
            }

            $this->view->article = $article_data;
            $this->view->form = $form;
        } else {
            $this->view->errMessage .= $this->view->translate('Статья не найдена!') . '<br/>';
            $this->view->headTitle($this->view->translate('Статья не найдена!'));
        }
    }

}