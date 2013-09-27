<?php

class RaceController extends App_Controller_LoaderController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Гонка'));
    }

    public function idAction() {
        // action body
    }

    public function addAction() {
        // css and js for date time picker script
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/jquery-ui-timepicker-addon.css"));
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery-ui-timepicker-addon.js"));

        $request = $this->getRequest();
        $championship_id = (int) $request->getParam('championship_id');

        $championship = new Application_Model_DbTable_Championship();
        $championship_data = $championship->getChampionshipData($championship_id);

        if ($championship_data) {
            $this->view->championship = $championship_data;
            $this->view->headTitle("{$this->view->translate('Чемпионат')} :: {$championship_data->name} :: {$this->view->translate('Добавить гонку')}");
            $this->view->pageTitle("{$championship_data->name} :: {$this->view->translate('Добавить гонку')}");

            $form = new Application_Form_Race_Add();
            $form->setAction($this->view->url(array('controller' => 'race', 'action' => 'add', 'championship_id' => $championship_id), 'championshipRace', true));

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $race_data = array();

                    $date = date('Y-m-d H:i:s');

                    $race_data['name'] = $form->getValue('name');
                    $race_data['championship_id'] = $form->getValue('championship');
                    $race_data['track_id'] = $form->getValue('track');
                    $race_data['number_race'] = $form->getValue('number_race');
                    $race_data['date_race'] = $form->getValue('date_race');
                    $race_data['description'] = $form->getValue('description');
                    $race_data['date_create'] = $date;
                    $race_data['date_edit'] = $date;

                    $race = new Application_Model_DbTable_ChampionshipRace();
                    $newRace = $race->createRow($race_data);
                    $newRace->save();
                    $this->redirect($this->view->url(array('controller' => 'race', 'action' => 'id', 'championship_id' => $championship_data->id, 'race_id' => $newRace->id), 'championshipRaceId', true));
                } else {
                    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $form->championship->addMultiOption($championship_data->id, $championship_data->name);
            $form->championship->setValue($championship_data->id);

            // add tracks
            $track = new Application_Model_DbTable_Track();
            $tracks_data = $track->getTracksName('ASC');

            if ($tracks_data) {
                foreach ($tracks_data as $track):
                    $form->track->addMultiOption($track->id, $track->name);
                endforeach;
            } else {
                $this->messageManager->addError("{$this->view->translate('Трассы не найдены!')}"
                        . "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(array('controller' => 'track', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
            }

            $this->view->form = $form;
        } else {
            $this->messageManager->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не найден!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не найден!')}");
        }
    }

}