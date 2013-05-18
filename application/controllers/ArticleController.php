<?php

class ArticleController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Контент'));
    }

    // action for view article
    public function idAction() {
        $request = $this->getRequest();
        $article_id = (int) $request->getParam('article_id');

        $article = new Application_Model_DbTable_Article();
        $article_data = $article->getArticleData($article_id);

        if ($article_data) {
            $this->view->article = $article_data;
            $this->view->headTitle($article_data->title);
            $this->view->pageTitle($article_data->title);
        } else {
            $this->messageManager->addError($this->view->translate('Запрашиваемый контент не существует!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Контент не существует!')}");
        }
    }

    // action for view all articles
    public function allAction() {
        $this->view->headTitle($this->view->translate('Весь контент сайта'));
        $this->view->pageTitle($this->view->translate('Контент сайта'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $article = new Application_Model_DbTable_Article();
        $paginator = $article->getPublishedArticlesPager($page_count_items, $page, $page_range, $items_order);

        if (count($paginator)) {
            $this->view->paginator = $paginator;
        } else {
            $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент на сайте не найден!')}");
        }
    }

    // action for add new article
    public function addAction() {
        // page title
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить контент'));
        $this->view->headScript()->appendFile($this->view->baseUrl("js/ckeditor/ckeditor.js"));

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
                        break;
                    default :
                        break;
                }

                $this->redirect($this->view->url(array('controller' => 'article', 'action' => 'id', 'article_id' => $newArticle->id), 'article', true));
            } else {
                $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        // add article types to the form
        $article_types = new Application_Model_DbTable_ArticleType();
        $article_types = $article_types->getArticleTypeNames('ASC');

        if ($article_types) {
            foreach ($article_types as $type):
                $form->article_type->addMultiOption($type->id, $type->name);

                if (strtolower($type->name) == 'game') {
                    $form->article_type->setvalue($type->id);
                }
            endforeach;
        } else {
            $this->messageManager->addError("{$this->view->translate('Типы статей на сайте не найдены!')}"
                    . "<br/><a class=\"btn btn-danger btn-small\" href=\"{$this->view->url(array('controller' => 'article-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
        }

        // add content types to the form
        $content_type = new Application_Model_DbTable_ContentType();
        $content_types = $content_type->getContentTypeNames('ASC');

        if ($content_types) {
            foreach ($content_types as $type):
                $form->content_type->addMultiOption($type->id, $type->name);
            endforeach;
        } else {
            $this->messageManager->addError("{$this->view->translate('Типы контента на сайте не найдены!')}"
                    . "<br/><a class=\"btn btn-danger btn-small\" href=\"{$this->view->url(array('controller' => 'content-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
        }

        $this->view->form = $form;
    }

    // action for edit article
    public function editAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl("js/ckeditor/ckeditor.js"));

        $request = $this->getRequest();
        $article_id = (int) $request->getParam('article_id');

        $article = new Application_Model_DbTable_Article();
        $article_data = $article->getArticleData($article_id);

        if ($article_data) {
            $form = new Application_Form_Article_Edit();
            $form->setAction($this->view->url(array('controller' => 'article', 'action' => 'edit', 'article_id' => $article_id), 'article', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'article', 'action' => 'id', 'article_id' => $article_id), 'article', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    if ($article_data->article_type_id == $form->getValue('article_type')) {
                        // if article type not changed do this code
                        $new_article_data = array(
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
                        $article->update($new_article_data, $article_where);

                        $article_type = new Application_Model_DbTable_ArticleType();
                        $article_type_name = $article_type->getName($form->getValue('article_type'));

                        // save additional information corespondig article_type to db
                        switch ($article_type_name) {
                            case 'game':
                                $game = new Application_Model_DbTable_Game();
                                $game_data = array(
                                    'name' => $form->getValue('title'),
                                );
                                $game_where = $game->getAdapter()->quoteInto('article_id = ?', $article_id);
                                $game->update($game_data, $game_where);
                                break;
                            default :

                                break;
                        }

                        $this->redirect($this->view->url(array('controller' => 'article', 'action' => 'id', 'article_id' => $article_id), 'article', true));
                    } else {
                        // if article type changed
                        $this->messageManager->addError("{$this->view->translate('Функционал для смены типов статьи не готов.')}");
                    }
                } else {
                    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            // add article types to the form
            $article_types = new Application_Model_DbTable_ArticleType();
            $article_types = $article_types->getArticleTypeNames('ASC');

            if ($article_types) {
                foreach ($article_types as $type):
                    $form->article_type->addMultiOption($type->id, $type->name);
                endforeach;
            } else {
                $this->messageManager->addError("{$this->view->translate('Типы статей на сайте не найдены!')}" .
                        "<br/><a href=\"{$this->view->url(array('controller' => 'article-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
            }

            // add content types to the form
            $content_type = new Application_Model_DbTable_ContentType();
            $content_types = $content_type->getContentTypeNames('ASC');

            if ($content_types) {
                foreach ($content_types as $type):
                    $form->content_type->addMultiOption($type->id, $type->name);
                endforeach;
            } else {
                $this->messageManager->addError("{$this->view->translate('Типы контента на сайте не найдены!')}" .
                        "<br/><a href=\"{$this->view->url(array('controller' => 'content-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
            }

            //head titles
            $this->view->headTitle("{$this->view->translate('Редактировать контент')} :: {$article_data->title}");
            $this->view->pageTitle("{$this->view->translate('Редактировать контент')} :: {$article_data->title}");

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
            $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент не существует!')}");
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
        }
    }

    // action for delete article
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удаление статьи'));

        $request = $this->getRequest();
        $article_id = (int) $request->getParam('article_id');

        $article = new Application_Model_DbTable_Article();
        $article_data = $article->getArticleData($article_id);

        if ($article_data) {
            //page title
            $this->view->headTitle($article_data->title);
            $this->view->pageTitle("{$this->view->translate('Удаление статьи')} :: {$article_data->title}");

            $this->messageManager->addWarning("{$this->view->translate('Вы действительно хотите удалить статью')}. {$article_data->title}?");

            //create delete form
            $form = new Application_Form_Article_Delete();
            $form->setAction($this->view->url(array('controller' => 'article', 'action' => 'edit', 'article_id' => $article_id), 'article', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'article', 'action' => 'id', 'article_id' => $article_id), 'article', true)}\"");

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
                } else {
                    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $this->view->article = $article_data;
            $this->view->form = $form;
        } else {
            $this->messageManager->addError("{$this->view->translate('Зарпашиваемый контент не найден!')}");
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Контент не существует!')");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Контент не существует!')}");
        }
    }

    public function allByTypeAction() {
        $request = $this->getRequest();
        $article_type_id = (int) $request->getParam('article_type_id');

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_type_data = $article_type->getName($article_type_id);

        if ($article_type_data) {
            $this->view->headTitle("{$this->view->translate('Контент по типу')} :: {$article_type_data}");
            $this->view->pageTitle("{$this->view->translate('Контент по типу')} :: {$article_type_data}");

            // setup pager settings
            $page_count_items = 10;
            $page_range = 5;
            $items_order = 'DESC';
            $page = $this->getRequest()->getParam('page');

            $article = new Application_Model_DbTable_Article();
            $paginator = $article->getAllArticlesPagerByType($page_count_items, $page, $page_range, $article_type_id, $items_order);

            if (count($paginator)) {
                $this->view->paginator = $paginator;
            } else {
                $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент на сайте не найден!')}");
            }

            $this->view->article_type_name = $article_type_data;
        } else {
            $this->messageManager->addError("{$this->view->translate('Зарпашиваемый тип контента не существует!')}");

            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не существует!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не существует!')}");
        }
    }

}