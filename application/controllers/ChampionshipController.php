<?php

class ChampionshipController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Чемпионат'));
    }

    public function idAction() {
        $request = $this->getRequest();
        $championship_id = (int) $request->getParam('championship_id');

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
        $championship_id = $request->getParam('championship_id');

        $championship = new Application_Model_DbTable_Championship();
        $championship_data = $championship->getChampionshipData($championship_id);

        if ($championship_data) {
            $form = new Application_Form_Championship_Edit();

            $form->setAction($this->view->url(array('controller' => 'championship', 'action' => 'edit', 'id' => $championship_id), 'championship', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'championship', 'action' => 'id', 'id' => $championship_id), 'championship', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    $exist_championship_name = $championship->checkExistChampionshipName($form->getValue('name'));

                    if ($exist_championship_name) {
                        if ($exist_championship_name == $championship_id) {
                            $update = TRUE;
                        } else {
                            $update = FALSE;
                        }
                    } else {
                        $update = TRUE;
                    }


                    if ($update) {
                        //saving new data to DB
                        $new_championship_data = array();

                        //receive and rename logo file
                        if ($form->getValue('logo')) {
                            if ($form->logo->receive()) {
                                $file = $form->logo->getFileInfo();
                                $ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
                                $newName = Date('Y-m-d_H-i-s') . strtolower('_logo' . '.' . $ext);

                                $filterRename = new Zend_Filter_File_Rename(array('target'
                                            => $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

                                $filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

                                $new_championship_data['url_logo'] = '/img/data/logos/championships/' . $newName;

                                if ($new_championship_data['url_logo'] != $championship_data['url_logo']) {
                                    unlink(APPLICATION_PATH . '/../public_html' . $championship_data['url_logo']);
                                }
                            }
                        }

                        // save new article to db
                        $date = date('Y-m-d H:i:s');

                        $new_championship_data['name'] = $form->getValue('name');
                        $new_championship_data['league_id'] = $form->getValue('league');
                        $new_championship_data['article_id'] = $form->getValue('rule');
                        $new_championship_data['game_id'] = $form->getValue('game');
                        $new_championship_data['user_id'] = $form->getValue('admin');
                        $new_championship_data['date_start'] = $form->getValue('date_start');
                        $new_championship_data['date_end'] = $form->getValue('date_end');
                        $new_championship_data['description'] = $form->getValue('description');
                        $new_championship_data['date_edit'] = $date;

                        $championship_where = $championship->getAdapter()->quoteInto('id = ?', $championship_id);
                        $championship->update($new_championship_data, $championship_where);

                        $this->redirect($this->view->url(array('controller' => 'championship', 'action' => 'id', 'id' => $championship_id), 'championship', true));
                    } else {
                        $this->view->errMessage .= $this->view->translate('Неверное название') . ": {$form->getValue('name')} <br/>";
                        $this->view->errMessage .= $this->view->translate('Название чемпионата уже существуют в базе данных!') . '<br/>';
                    }
                }
            }

            //set championship name value
            $form->name->setValue($championship_data->name);

            //set league value
            $league = new Application_Model_DbTable_League();
            $leagues = $league->getLeaguesName('ASC');

            if ($leagues) {
                foreach ($leagues as $league):
                    $form->league->addMultiOption($league->id, $league->name);
                endforeach;
            } else {
                $this->view->errMessage .= $this->view->translate('Лиги не найдены') . '<br />';
            }

            $form->league->setValue($championship_data->league_id);

            // set reglament value
            $article = new Application_Model_DbTable_Article();
            $articles = $article->getPublishArticleTitlesByTypeName('rule', 'ASC');

            if ($articles) {
                foreach ($articles as $article):
                    $form->rule->addMultiOption($article->id, $article->title);
                endforeach;
            } else {
                $this->view->errMessage .= $this->view->translate('Регламенты на сайте не найдены. Добавьте регламент, чтобы создать чемпионат!') . '<br />';
            }

            $form->rule->setValue($championship_data->article_id);

            // set game value
            $game = new Application_Model_DbTable_Game();
            $games = $game->getGameNames('ASC');

            if ($games) {
                foreach ($games as $game):
                    $form->game->addMultiOption($game->id, $game->name);
                endforeach;
            } else {
                $this->view->errMessage .= $this->view->translate('Игры не найдены') . '<br />';
            }

            $form->game->setValue($championship_data->game_id);

            // set championship admin value
            $user = new Application_Model_DbTable_User();
            $users = $user->getUsersByRoleName('admin', 'ASC');

            if ($users) {
                foreach ($users as $user) {
                    $form->admin->addMultiOption($user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')');
                }
            } else {
                $this->view->errMessage .= $this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы добавить чемпионат.');
            }

            $form->admin->setValue($championship_data->user_id);

            $form->date_start->setValue($championship_data->date_start);
            $form->date_end->setValue($championship_data->date_end);
            $form->description->setValue($championship_data->description);

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
        $championship_id = $request->getParam('championship_id');
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Все'));
    }

    public function addteamAction() {
        $this->view->headTitle($this->view->translate('Добавить команду в чемпионат'));
        $request = $this->getRequest();

        $championship_id = $request->getParam('championship_id');

        $championship = new Application_Model_DbTable_Championship();

        if ($championship->checkExistChampionshipById($championship_id)) {
            $form = new Application_Form_Championship_Addteam();
            $form->setAction($this->view->url(array('controller' => 'championship', 'action' => 'addteam', 'championship_id' => $championship_id), 'championshipTeamDefault', true));
            $this->view->form = $form;

            // add teams
            $team = new Application_Model_DbTable_Team();
            $teams = $team->getTeamNames('ASC');

            $championship_team = new Application_Model_DbTable_ChampionshipTeam();

            if ($teams) {
                foreach ($teams as $team):
                    if (!$championship_team->checkTeamExist($championship_id, $team->id)) {
                        $form->team->addMultiOption($team->id, $team->name);
                    }
                endforeach;
            } else {
                $this->view->errMessage .= $this->view->translate('Команды не найдены!') . '<br />';
            }

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    //saving new data to DB
                    $new_championship_team_data = array();

                    //receive and rename logo file
                    if ($form->getValue('logo')) {
                        if ($form->logo->receive()) {
                            $file = $form->logo->getFileInfo();
                            $ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_logo_team' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                                        => $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

                            $filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

                            $new_championship_team_data['url_logo'] = '/img/data/logos/teams/logo/' . $newName;
                        }
                    }

                    //receive and rename logo file
                    if ($form->getValue('logo_team')) {
                        if ($form->logo->receive()) {
                            $file = $form->logo_team->getFileInfo();
                            $ext = pathinfo($file['logo_team']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_logo_car' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                                        => $file['logo_team']['destination'] . '/' . $newName, 'overwrite' => true));

                            $filterRename->filter($file['logo_team']['destination'] . '/' . $file['logo_team']['name']);

                            $new_championship_team_data['url_logo_car'] = '/img/data/logos/teams/car/' . $newName;
                        }
                    }

                    // save new article to db
                    $date = date('Y-m-d H:i:s');

                    $new_championship_team_data['name'] = $form->getValue('name');
                    $new_championship_team_data['championship_id'] = $championship_id;
                    $new_championship_team_data['team_id'] = $form->getValue('team');
                    $new_championship_team_data['team_number'] = $form->getValue('team_number');
                    $new_championship_team_data['date_create'] = $date;
                    $new_championship_team_data['date_edit'] = $date;


                    $newChampionshipTeam = $championship_team->createRow($new_championship_team_data);
                    $newChampionshipTeam->save();

                    $this->redirect($this->view->url(array('controller' => 'championship', 'action' => 'id', 'championship_id' => $championship_id), 'championship', true));
                }
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!') . '<br/>';
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Чемпионат не найден!'));
        }
    }

    public function editteamAction() {
        $this->view->headTitle($this->view->translate('Редактировать команду'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');

        $championship = new Application_Model_DbTable_Championship();

        if ($championship->checkExistChampionshipById($championship_id)) {
            $team_id = $request->getParam('team_id');

            $championship_team = new Application_Model_DbTable_ChampionshipTeam();
            $championship_team_data = $championship_team->getData($team_id, $championship_id);

            if ($championship_team_data) {
                $form = new Application_Form_Championship_Editteam();
                $form->setAction($this->view->url(array('controller' => 'championship', 'action' => 'editteam', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true));
                $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'championship', 'action' => 'team', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true)}\"");

                $form->name->setValue($championship_team_data->name);

                // add teams
                $team = new Application_Model_DbTable_Team();
                $teams = $team->getTeamNames('ASC');

                if ($teams) {
                    foreach ($teams as $team):
                        if (!$championship_team->checkTeamExist($championship_id, $team->id)) {
                            $form->team->addMultiOption($team->id, $team->name);
                        } elseif ($team->id == $championship_team_data->team_id) {
                            $form->team->addMultiOption($team->id, $team->name);
                        }
                    endforeach;
                } else {
                    $this->view->errMessage .= $this->view->translate('Команды не найдены!') . '<br />';
                }

                $form->team->setValue($championship_team_data->team_id);

                if ($this->getRequest()->isPost()) {
                    if ($form->isValid($request->getPost())) {
                        //saving new data to DB
                        $new_championship_team_data = array();

                        //receive and rename logo file
                        if ($form->getValue('logo')) {
                            if ($form->logo->receive()) {
                                $file = $form->logo->getFileInfo();
                                $ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
                                $newName = Date('Y-m-d_H-i-s') . strtolower('_logo_team' . '.' . $ext);

                                $filterRename = new Zend_Filter_File_Rename(array('target'
                                            => $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

                                $filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

                                $new_championship_team_data['url_logo'] = '/img/data/logos/teams/logo/' . $newName;

                                if ($new_championship_team_data['url_logo'] != $championship_team_data['url_logo']) {
                                    unlink(APPLICATION_PATH . '/../public_html' . $championship_team_data['url_logo']);
                                }
                            }
                        }

                        //receive and rename logo file
                        if ($form->getValue('logo_team')) {
                            if ($form->logo->receive()) {
                                $file = $form->logo_team->getFileInfo();
                                $ext = pathinfo($file['logo_team']['name'], PATHINFO_EXTENSION);
                                $newName = Date('Y-m-d_H-i-s') . strtolower('_logo_car' . '.' . $ext);

                                $filterRename = new Zend_Filter_File_Rename(array('target'
                                            => $file['logo_team']['destination'] . '/' . $newName, 'overwrite' => true));

                                $filterRename->filter($file['logo_team']['destination'] . '/' . $file['logo_team']['name']);

                                $new_championship_team_data['url_logo_car'] = '/img/data/logos/teams/car/' . $newName;

                                if ($new_championship_team_data['url_logo_car'] != $championship_team_data['url_logo_car']) {
                                    unlink(APPLICATION_PATH . '/../public_html' . $championship_team_data['url_logo_car']);
                                }
                            }
                        }

                        // save new article to db
                        $date = date('Y-m-d H:i:s');

                        $new_championship_team_data['name'] = $form->getValue('name');
                        $new_championship_team_data['team_id'] = $form->getValue('team');
                        $new_championship_team_data['team_number'] = $form->getValue('team_number');
                        $new_championship_team_data['date_edit'] = $date;

                        $championship_team_where = $championship_team->getAdapter()->quoteInto("championship_id = {$championship_id} and team_id = {$team_id}");
                        $championship_team->update($new_championship_team_data, $championship_team_where);

                        $this->redirect($this->view->url(array('controller' => 'championship', 'action' => 'team', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true));
                    }
                }

                $this->view->form = $form;
            } else {
                $this->view->errMessage .= $this->view->translate('Команда не найдена!') . '<br/>';
                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Команда не найдена не найдена!'));
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!') . '<br/>';
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Чемпионат не найден!'));
        }
    }

    public function teamsAction() {
        $this->view->headTitle($this->view->translate('Команды и Гонщики'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');

        $championship = new Application_Model_DbTable_Championship();

        if ($championship->checkExistChampionshipById($championship_id)) {
            $championshipTeam = new Application_Model_DbTable_ChampionshipTeam();

            $championship_team_data = $championshipTeam->getAllTeamData($championship_id);


            if ($championship_team_data) {
                $this->view->championshipTeams = $championship_team_data;
            } else {
                $this->view->errMessage .= $this->view->translate('В чемпионате нет команд!') . '<br/>';
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!') . '<br/>';
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Чемпионат не найден!'));
        }
    }

    public function teamAction() {
        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');
        $team_id = $request->getParam('team_id');

        $championship = new Application_Model_DbTable_Championship();

        if ($championship->checkExistChampionshipById($championship_id)) {
            $championship_team = new Application_Model_DbTable_ChampionshipTeam();
            if ($championship_team->checkTeamExist($championship_id, $team_id)) {
                $championship_team = new Application_Model_DbTable_ChampionshipTeam();
                $championship_team_data = $championship_team->getFullTeamData($championship_id, $team_id);

                $this->view->headTitle($championship_team_data->championship_name);
                $this->view->headTitle($this->view->translate('Команда'));
                $this->view->headTitle($championship_team_data->name);
                
                $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();
                $championship_team_driver_data = $championship_team_driver->getChampionshipTeamDrivers($championship_id, $team_id);
                
                $this->view->team_data = $championship_team_data;
                $this->view->team_driver_data = $championship_team_driver_data;
            } else {
                $this->view->errMessage .= $this->view->translate('Команда не найдена!') . '<br/>';
                $this->view->headTitle($this->view->translate('Команда'));
                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Команда не найдена не найдена!'));
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!') . '<br/>';
            $this->view->headTitle($this->view->translate('Команда'));
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Чемпионат не найден!'));
        }
    }

    public function adddriverAction() {
        $this->view->headTitle($this->view->translate('Добавить гонщика в команду'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');
        $team_id = $request->getParam('team_id');

        $championship = new Application_Model_DbTable_Championship();

        if ($championship->checkExistChampionshipById($championship_id)) {
            $championship_team = new Application_Model_DbTable_ChampionshipTeam();
            if ($championship_team->checkTeamExist($championship_id, $team_id)) {
                $form = new Application_Form_Championship_Adddriver();
                $form->setAction($this->view->url(array('controller' => 'championship', 'action' => 'adddriver', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeamDriver', true));
                $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'championship', 'action' => 'team', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true)}\"");

                $user = new Application_Model_DbTable_User();

                $users_data = $user->getAllUsers('ASC');

                $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();

                if ($users_data) {
                    foreach ($users_data as $user) {
                        if (!$championship_team_driver->checkChampionshipDriverExist($championship_id, $user->id)) {
                            $form->driver->addMultiOption($user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')');
                        }
                    }
                    $this->view->form = $form;
                } else {
                    $this->view->errMessage .= $this->view->translate('Гонщики на сайте не найдены!.') . '<br />';
                }

                if ($this->getRequest()->isPost()) {
                    if ($form->isValid($request->getPost())) {
                        //saving new data to DB
                        $new_championship_team_driver_data = array();

                        // save new article to db
                        $date = date('Y-m-d H:i:s');

                        $new_championship_team_driver_data['championship_id'] = $championship_id;
                        $new_championship_team_driver_data['team_id'] = $team_id;
                        $new_championship_team_driver_data['user_id'] = $form->getValue('driver');
                        $new_championship_team_driver_data['driver_number'] = $form->getValue('driver_number');
                        $new_championship_team_driver_data['date_create'] = $date;
                        $new_championship_team_driver_data['date_edit'] = $date;

                        $newChampionshipTeamDriver = $championship_team_driver->createRow($new_championship_team_driver_data);
                        $newChampionshipTeamDriver->save();

                        $this->redirect($this->view->url(array('controller' => 'championship', 'action' => 'team', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true));
                    }
                }


                $this->view->form = $form;
            } else {
                $this->view->errMessage .= $this->view->translate('Команда не найдена!') . '<br/>';
                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Команда не найдена не найдена!'));
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Чемпионат не найден!') . '<br/>';
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Чемпионат не найден!'));
        }
    }

    public function dsAction() {
        $this->view->headTitle($this->view->translate('Личный зачет'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');
    }

    public function tsAction() {
        $this->view->headTitle($this->view->translate('Командный зачет'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');
    }

}