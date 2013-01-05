<?php

class ChampionshipController extends App_Controller_FirstBootController {

    public function idAction() {
        $this->view->headTitle($this->view->translate('Чемпионат'));
        $request = $this->getRequest();
        $championship_id = (int) $request->getParam('id');

        $championship = new Application_Model_DbTable_Championship();
        $championship_data = $championship->getChampionshipData($championship_id);

        if ($championship_data) {
            $this->view->championship = $championship_data;
            $this->view->headTitle($championship_data->name);
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!');
            $this->view->headTitle($this->view->translate('Чемпионат не найдена!'));
        }
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Создание чемпионата'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Championship_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                //receive and rename logo
                $file = $form->logo->getFileInfo();
                $ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
                $newName = strtolower($form->name->getValue()) . '_logo' . '.' . $ext;
                $form->logo->addFilter(new Zend_Filter_File_Rename(array('target'
                            => $file['logo']['destination'] . '/' . $newName, 'overwrite' => true)));
                $form->logo->receive();

                // save new article to db
                $date = date('Y-m-d H:i:s');
                $championship_data = array(
                    'name' => $form->getValue('name'),
                    'url_logo' => '/img/data/logos/championships/' . $newName,
                    'league_id' => $form->getValue('league'),
                    'article_id' => $form->getValue('rule'),
                    'game_id' => $form->getValue('game'),
                    'user_id' => $form->getValue('admin'),
                    'date_start' => $form->getValue('date_start'),
                    'date_end' => $form->getValue('date_end'),
                    'description' => $form->getValue('description'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $championship = new Application_Model_DbTable_Championship();
                $newChampionship = $championship->createRow($championship_data);
                $newChampionship->save();

                $this->redirect('/championship/id/' . $newChampionship->id);
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

}