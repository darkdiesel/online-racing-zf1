<?php

class EventController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/admin.css"));
        $this->view->headTitle($this->view->translate('Событие'));
    }

    // action for view content type
    public function idAction() {
        $request = $this->getRequest();
        $event_id = (int) $request->getParam('id');

        $event = new Application_Model_DbTable_Event();
        $event_data = $event->getData($event_id);

        if ($event_data) {
            $this->view->event = $event_data;
            $this->view->headTitle($event_data->name);
            return;
        } else {
            $this->view->errMessage = $this->view->translate('Событие не найдено!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Событие не найдено!'));
        }
    }

    // action for view all content types
    public function allAction() {
        $this->view->headTitle($this->view->translate('Просмотреть все'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $content_type = new Application_Model_DbTable_Event();

        $this->view->paginator = $content_type->getEventsPager($page_count_items, $page, $page_range, $items_order);
    }

    // action for add new content type
    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));

        // css and js for date time picker script
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/jquery-ui-timepicker-addon.css"));
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery-ui-timepicker-addon.js"));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Event_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $event_data = array(
                    'name' => $form->getValue('name'),
                    'description' => $form->getValue('description'),
                    'date_event' => $form->getValue('date_event'),
                    'date_create' => $date,
                    'date_edit' => $date,
                    'url_event' => $form->getValue('url_event'),
                );

                $event = new Application_Model_DbTable_Event();
                $newEvent = $event->createRow($event_data);
                $newEvent->save();

                $this->redirect($this->view->baseUrl('event/id/' . $newEvent->id));
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для добавления события!');
            }
        }

        $this->view->form = $form;
    }

    // action for edit content type
    public function editAction() {
        $this->view->headTitle($this->view->translate('Редактировать'));

        $request = $this->getRequest();
        $event_id = $request->getParam('id');

        // css and js for date time picker script
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/jquery-ui-timepicker-addon.css"));
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery-ui-timepicker-addon.js"));

        $event = new Application_Model_DbTable_Event();
        $event_data = $event->fetchRow(array('id = ?' => $event_id));

        if (count($event_data) != 0) {
            // form
            $form = new Application_Form_Event_Edit();
            $form->setAction('/event/edit/' . $event_id);

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $event_data = array(
                        'name' => $form->getValue('name'),
                        'description' => $form->getValue('description'),
                        'date_event' => $form->getValue('date_event'),
                        'date_edit' => date('Y-m-d H:i:s'),
                        'url_event' => $form->getValue('url_event')
                    );

                    $event_where = $event->getAdapter()->quoteInto('id = ?', $event_id);
                    $event->update($event_data, $event_where);

                    $this->redirect($this->view->baseUrl('event/id/' . $event_id));
                } else {
                    $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для изминения события!');
                }
            }

            $this->view->headTitle($event_data->name);

            $form->name->setvalue($event_data->name);
            $form->date_event->setvalue($event_data->date_event);
            $form->url_event->setvalue($event_data->url_event);
            $form->description->setvalue($event_data->description);

            $this->view->form = $form;
        } else {
            $this->view->errMessage = $this->view->translate('Событие не найдено!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Событие не найдено!'));
        }
    }

    // action for delete content type
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $event_id = (int) $request->getParam('id');

        $event = new Application_Model_DbTable_Event();
        $event_data = $event->fetchRow(array('id = ?' => $event_id));

        if (count($event_data) != 0) {
            $this->view->headTitle($event_data->name);

            $form = new Application_Form_Event_Delete();
            $form->setAction('/event/delete/' . $event_id);
            $form->cancel->setAttrib('onClick', 'location.href="/event/id/' . $event_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $event_where = $event->getAdapter()->quoteInto('id = ?', $event_id);
                    $event->delete($event_where);

                    $this->_helper->redirector('all', 'event');
                } else {
                    $this->view->errMessage = $this->view->translate("Произошла неожиданноя ошибка! Пожалуйста обратитесь к нам и сообщите о ней.");
                }
            }

            $this->view->form = $form;
            $this->view->event = $event_data;
        } else {
            $this->view->errMessage = $this->view->translate('Событие не найдено!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Событие не найдено!'));
        }
    }

}