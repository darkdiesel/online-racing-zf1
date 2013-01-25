<?php

class ArticleTypeController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Тип статьи'));
    }

    // action for view article type
    public function idAction() {
        $request = $this->getRequest();
        $article_type_id = (int) $request->getParam('id');

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_type_data = $article_type->fetchRow(array('id = ?' => $article_type_id));

        if (count($article_type_data) != 0) {
            $this->view->article_type = $article_type_data;
            $this->view->headTitle($article_type_data->name);
            return;
        } else {
            $this->view->errMessage = $this->view->translate('Тип статьи не найден!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Тип статьи не найден!'));
        }
    }

    // action for view all article types
    public function allAction() {
        $this->view->headTitle($this->view->translate('Просмотреть все'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'ASC';
        $page = $this->getRequest()->getParam('page');

        $article_type = new Application_Model_DbTable_ArticleType();

        $this->view->paginator = $article_type->getArticleTypePager($page_count_items, $page, $page_range, $items_order);
    }

    // action for add new article type
    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_ArticleType_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $article_type_data = array(
                    'name' => strtolower($form->getValue('name')),
                    'description' => $form->getValue('description'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $article_type = new Application_Model_DbTable_ArticleType();
                $newArticle_type = $article_type->createRow($article_type_data);
                $newArticle_type->save();

                $this->redirect($this->view->baseUrl('article-type/id/' . $newArticle_type->id));
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для добавления типа статьи!');
            }
        }

        $this->view->form = $form;
    }

    // action for edit article type
    public function editAction() {
        $this->view->headTitle($this->view->translate('Редактировать'));

        $request = $this->getRequest();
        $article_type_id = $request->getParam('id');

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_type_data = $article_type->fetchRow(array('id = ?' => $article_type_id));

        if (count($article_type_data) != 0) {
            // form
            $form = new Application_Form_ArticleType_Edit();
            $form->setAction('/article-type/edit/' . $article_type_id);

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $article_type_data = array(
                        'name' => strtolower($form->getValue('name')),
                        'description' => $form->getValue('description'),
                        'date_edit' => date('Y-m-d H:i:s')
                    );

                    $article_type_where = $article_type->getAdapter()->quoteInto('id = ?', $article_type_id);
                    $article_type->update($article_type_data, $article_type_where);

                    $this->redirect($this->view->baseUrl('article-type/id/' . $article_type_id));
                } else {
                    $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для изминения типа статьи!');
                }
            }

            $this->view->headTitle($article_type_data->name);

            $form->name->setvalue($article_type_data->name);
            $form->description->setvalue($article_type_data->description);

            $this->view->form = $form;
        } else {
            $this->view->errMessage = $this->view->translate('Тип статьи не найден!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Тип статьи не найден!'));
        }
    }

    // action for delete article type
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $article_type_id = (int) $request->getParam('id');

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_type_data = $article_type->fetchRow(array('id = ?' => $article_type_id));

        if (count($article_type_data) != 0) {
            $this->view->headTitle($article_type_data->name);

            $form = new Application_Form_ArticleType_Delete();
            $form->setAction('/article-type/delete/' . $article_type_id);
            $form->cancel->setAttrib('onClick', 'location.href="/article-type/id/' . $article_type_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $article_type_where = $article_type->getAdapter()->quoteInto('id = ?', $article_type_id);
                    $article_type->delete($article_type_where);

                    $this->_helper->redirector('all', 'article-type');
                } else {
                    $this->view->errMessage = $this->view->translate("Произошла неожиданноя ошибка! Пожалуйста обратитесь к нам и сообщите о ней.");
                }
            }

            $this->view->form = $form;
            $this->view->article_type = $article_type_data;
        } else {
            $this->view->errMessage = $this->view->translate('Тип статьи не найден!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Тип статьи не найден!'));
        }
    }

}