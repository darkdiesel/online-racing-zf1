<?php

class TrackController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Трасса'));
    }

    public function idAction() {
        $request = $this->getRequest();
        $track_id = (int) $request->getParam('track_id');

        $track = new Application_Model_DbTable_Track();
        $track_data = $track->getTrackData($track_id);

        if ($track_data) {
            $this->view->track = $track_data;
            $this->view->headTitle($track_data->name);
            $this->view->pageTitle("{$this->view->translate('Трасса')} :: {$track_data->name}");
        } else {
            $this->messageManager->addError("{$this->view->translate('Запрашиваемая трасса не существует!')}");
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Трасса не существует!')");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Трасса не существует!')");
        }
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить трассу'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Track_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $track_data = array();

                //receive and rename image_round file
                if ($form->getValue('track_scheme')) {
                    if ($form->track_scheme->receive()) {
                        $file = $form->track_scheme->getFileInfo();
                        $ext = pathinfo($file['track_scheme']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_track_scheme' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['track_scheme']['destination'] . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter($file['track_scheme']['destination'] . '/' . $file['track_scheme']['name']);

                        $track_data['url_track_scheme'] = '/img/data/track_schemes/' . $newName;
                    }
                }

                $date = date('Y-m-d H:i:s');
                $track_data['name'] = $form->getValue('name');
                $track_data['city'] = $form->getValue('city');
                $track_data['country_id'] = $form->getValue('country');
                $track_data['year_track'] = $form->getValue('year_track');
                $track_data['description'] = $form->getValue('description');
                $track_data['date_create'] = $date;
                $track_data['date_edit'] = $date;

                $country = new Application_Model_DbTable_Track();
                $newTrack = $country->createRow($track_data);
                $newTrack->save();

                $this->redirect($this->view->url(array('controller' => 'track', 'action' => 'id', 'id' => $newTrack->id), 'track', true));
            } else {
                $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        $country = new Application_Model_DbTable_Country();
        $countries = $country->getCountriesName('ASC');

        foreach ($countries as $country):
            $form->country->addMultiOption($country->id, $country->native_name . " ({$country->english_name})");
        endforeach;

        $this->view->form = $form;
    }

    public function allAction() {
        $this->messageManager->addError("{$this->view->translate('" Все трассы" Функционал не доделан!')}");
        
        $this->view->headTitle($this->view->translate('Все трассы'));
        $this->view->pageTitle($this->view->translate('Все трассы'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $track = new Application_Model_DbTable_Track();
        $paginator = $track->getTracksPager($page_count_items, $page, $page_range, $items_order);
        
         if (count($paginator)){
            $this->view->paginator = $paginator;
        } else {
            $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент на сайте не найден!')}");
        }
    }
    
    

}

