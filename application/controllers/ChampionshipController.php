<?php

class ChampionshipController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Чемпионат'));
    }

    public function idAction() {
        $request = $this->getRequest();
        $championship_id = (int) $request->getParam('id');

        $championship = new Application_Model_DbTable_Championship();
        $championship_data = $championship->getChampionshipData($championship_id);

        if ($championship_data) {
            $this->view->championship = $championship_data;
            $this->view->headTitle($championship_data->name);
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Чемпионат не найден!'));
        }
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Championship_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                
                $championship_data = array();
                
                //receive and rename logo file
                if ($form->getValue('logo')) {
                    if ($form->logo->receive()) {
                        $file = $form->logo->getFileInfo();
                        $ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_logo' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                                    => $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

                        $championship_data['url_logo'] = '/img/data/logos/championships/' . $newName;
                    }
                }

                // save new article to db
                $date = date('Y-m-d H:i:s');
                
                $championship_data['name'] = $form->getValue('name');
                $championship_data['league_id'] = $form->getValue('league');
                $championship_data['article_id'] = $form->getValue('rule');
                $championship_data['game_id'] = $form->getValue('game');
                $championship_data['user_id'] = $form->getValue('admin');
                $championship_data['date_start'] = $form->getValue('date_start');
                $championship_data['date_end'] = $form->getValue('date_end');
                $championship_data['description'] = $form->getValue('description');
                $championship_data['date_create'] = $date;
                $championship_data['date_edit'] = $date;

                $championship = new Application_Model_DbTable_Championship();
                $newChampionship = $championship->createRow($championship_data);
                $newChampionship->save();
                $this->redirect($this->view->url(array('controller' => 'championship', 'action' => 'id', 'id' => $newChampionship->id), 'championship', true));
            }
        }
        // add leagues
        $league = new Application_Model_DbTable_League();
        $leagues = $league->getLeaguesName('ASC');

        if ($leagues) {
            foreach ($leagues as $league):
                $form->league->addMultiOption($league->id, $league->name);
            endforeach;
        } else {
            $this->view->errMessage .= $this->view->translate('Лиги не найдены') . '<br />';
        }

        // add reglaments
        $article = new Application_Model_DbTable_Article();
        $articles = $article->getPublishArticleTitlesByTypeName('rule', 'ASC');

        if ($articles) {
            foreach ($articles as $article):
                $form->rule->addMultiOption($article->id, $article->title);
            endforeach;
        } else {
            $this->view->errMessage .= $this->view->translate('Регламенты на сайте не найдены. Добавьте регламент, чтобы создать чемпионат!') . '<br />';
        }

        // add games
        $game = new Application_Model_DbTable_Game();
        $games = $game->getGameNames('ASC');

        if ($games) {
            foreach ($games as $game):
                $form->game->addMultiOption($game->id, $game->name);
            endforeach;
        } else {
            $this->view->errMessage .= $this->view->translate('Игры не найдены') . '<br />';
        }

        // add admins
        $user = new Application_Model_DbTable_User();
        $users = $user->getUsersByRoleName('admin', 'ASC');

        if ($users) {
            foreach ($users as $user) {
                $form->admin->addMultiOption($user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')');
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы добавить чемпионат.');
        }

        $this->view->form = $form;
    }

    public function editAction() {
        $this->view->headTitle($this->view->translate('Редактировать'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('id');
        
        $championship = new Application_Model_DbTable_Championship();
        $championship_data = $championship->getChampionshipData($championship_id);

        if ($championship_data) {
            $form = new Application_Form_Championship_Edit();
            
            $this->view->form = $form;
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!') . '<br/>';
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Чемпионат не найден!'));
        }
    }

    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('id');
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Все'));
    }

}