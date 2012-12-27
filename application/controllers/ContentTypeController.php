<?php

class ContentTypeController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/admin.css"));
    }

    // action for view content type
    public function idAction() {
        $request = $this->getRequest();
        $content_type_id = (int) $request->getParam('id');

        $content_type = new Application_Model_DbTable_ContentType();
        $content_type_data = $content_type->fetchRow(array('id = ?' => $content_type_id));

        if (count($content_type_data) != 0) {
            $this->view->content_type = $content_type_data;
            $this->view->headTitle($this->view->translate('Тип контента'));
            $this->view->headTitle($content_type_data->name);
            return;
        } else {
            $this->view->errMessage = $this->view->translate('Тип контента не найден!');
            $this->view->headTitle($this->view->translate('Тип контента не найден!'));
        }
    }

    // action for view all content types
    public function allAction() {
        $this->view->headTitle($this->view->translate('Типы контента'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'ASC';
        $page = $this->getRequest()->getParam('page');

        $content_type = new Application_Model_DbTable_ContentType();

        $this->view->paginator = $content_type->getContentTypePager($page_count_items, $page, $page_range, $items_order);
    }

    // action for add new content type
    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить тип контента'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_ContentType_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $content_type_data = array(
                    'name' => strtolower($form->getValue('name')),
                    'description' => $form->getValue('description'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $content_type = new Application_Model_DbTable_ContentType();
                $newContent_type = $content_type->createRow($content_type_data);
                $newContent_type->save();

                $this->redirect($this->view->baseUrl('content-type/id/' . $newContent_type->id));
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для добавления типа контента!');
            }
        }

        $this->view->form = $form;
    }

    // action for edit content type
    public function editAction() {
        $request = $this->getRequest();
        $content_type_id = $request->getParam('id');

        $content_type = new Application_Model_DbTable_ContentType();
        $content_type_data = $content_type->fetchRow(array('id = ?' => $content_type_id));

        if (count($content_type_data) != 0) {
            // form
            $form = new Application_Form_ContentType_Edit();
            $form->setAction('/content-type/edit/' . $content_type_id);

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $content_type_data = array(
                        'name' => strtolower($form->getValue('name')),
                        'description' => $form->getValue('description'),
                        'date_edit' => date('Y-m-d H:i:s')
                    );

                    $content_type_where = $content_type->getAdapter()->quoteInto('id = ?', $content_type_id);
                    $content_type->update($content_type_data, $content_type_where);

                    $this->redirect($this->view->baseUrl('content-type/id/' . $content_type_id));
                } else {
                    $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для изминения типа контента!');
                }
            }
            $this->view->headTitle($this->view->translate('Редактирование'));
            $this->view->headTitle($content_type_data->name);

            $form->name->setvalue($content_type_data->name);
            $form->description->setvalue($content_type_data->description);

            $this->view->form = $form;
        } else {
            $this->view->errMessage = $this->view->translate('Тип контента не найден!');
            $this->view->headTitle($this->view->translate('Тип контента не найден!'));
        }
    }

    // action for delete content type
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удаление типа контента'));

        $request = $this->getRequest();
        $content_type_id = (int) $request->getParam('id');

        $content_type = new Application_Model_DbTable_ContentType();
        $content_type_data = $content_type->fetchRow(array('id = ?' => $content_type_id));

        if (count($content_type_data) != 0) {
            $this->view->headTitle($content_type_data->name);

            $form = new Application_Form_ArticleType_Delete();
            $form->setAction('/content-type/delete/' . $content_type_id);
            $form->cancel->setAttrib('onClick', 'location.href="/content-type/id/' . $content_type_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $content_type_where = $content_type->getAdapter()->quoteInto('id = ?', $content_type_id);
                    $content_type->delete($content_type_where);

                    $this->_helper->redirector('all', 'content-type');
                } else {
                    $this->view->errMessage = $this->view->translate("Произошла неожиданноя ошибка! Пожалуйста обратитесь к нам и сообщите о ней.");
                }
            }

            $this->view->form = $form;
            $this->view->content_type = $content_type_data;
        } else {
            $this->view->errMessage = $this->view->translate('Тип контента не найден!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Тип контента не найден!'));
        }
    }

}