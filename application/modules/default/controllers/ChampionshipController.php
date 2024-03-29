<?php

class ChampionshipController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
    }

    public function idAction()
    {
        $request = $this->getRequest();
        $league_id = (int)$request->getParam('league_id');
        $championship_id = (int)$request->getParam('championship_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->league_data = $league_data;

            $this->view->headTitle($this->view->translate('Лига'));
            $this->view->headTitle($league_data->name);

            $championship = new Application_Model_DbTable_Championship();
            $championship_data = $championship->getChampionshipData($league_id, $championship_id);

            if ($championship_data) {
                $this->view->championship_data = $championship_data;
                $this->view->headTitle($this->view->translate('Чемпионат'));
                $this->view->headTitle($championship_data->name);

                $this->view->pageTitle($championship_data->name);

                // Set breadcrumbs for this page
                $this->view->breadcrumb()->LeagueAll('1')->league($league_id, $league_data->name, '1')
                    ->championship($league_id, $championship_id, $championship_data->name);
            } else {
                $this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Чемпионат не найден!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
            }
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));

            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Лига не найдена!'));

            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }


    public function allAction()
    {
        $this->view->headTitle($this->view->translate('Все'));
    }

    public function teamAction()
    {
        $request = $this->getRequest();
        $league_id = (int)$request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');
        $team_id = $request->getParam('team_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
            $this->view->headTitle($this->view->translate('Чемпионат'));
            $this->view->league_data = $league_data;

            $championship_data = $this->db->get('championship')->getItem($championship_id);

            if ($championship_data) {
                $this->view->championship_data = $championship_data;

                $this->view->headTitle($championship_data->name);

                $championship_team = new Application_Model_DbTable_ChampionshipTeam();
                $championship_team_data = $championship_team->getTeamData($championship_id, $team_id);
                if ($championship_team_data) {
                    $this->view->breadcrumb()->LeagueAll('1')->league($league_id, $league_data->name, '1')
                        ->championship($league_id, $championship_id, $championship_data->name, '1')
                        ->drivers($league_id, $championship_id)
                        ->championship_team($league_id, $championship_id, $team_id, $championship_team_data->name);

                    //head title
                    $this->view->headTitle("{$this->view->translate('Команда')} :: {$championship_team_data->name}");
                    $this->view->pageTitle("{$this->view->translate('Команда')} :: {$championship_team_data->name}");

                    $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();
                    $championship_team_driver_data = $championship_team_driver->getChampionshipTeamDrivers(
                        $championship_id, $team_id
                    );

                    $this->view->team_data = $championship_team_data;
                    $this->view->team_driver_data = $championship_team_driver_data;
                } else {
                    //error message if team for this championship not found
                    $this->messages->addError("{$this->view->translate('Команда в чемпионате не существует!')}");
                    //head title
                    $this->view->headTitle(
                        "{$this->view->translate('Команда')} :: {$this->view->translate(
                            'Ошибка!'
                        )} :: {$this->view->translate('Команда не существует!')}"
                    );
                    $this->view->pageTitle(
                        "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команда не существует!')}"
                    );
                }
            } else {
                //error message if championship not found
                $this->messages->addError("{$this->view->translate('Чемпионат не существует!')}");
                //head title
                $this->view->headTitle(
                    "{$this->view->translate('Команда')} :: {$this->view->translate(
                        'Ошибка!'
                    )} :: {$this->view->translate('Чемпионат не существует!')}"
                );
                $this->view->pageTitle(
                    "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не существует!')}"
                );
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
            //head title
            $this->view->headTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
            $this->view->pageTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
        }
    }

    public function teamAddAction()
    {
        $request = $this->getRequest();
        $league_id = (int)$request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
            $this->view->headTitle($this->view->translate('Чемпионат'));
            $this->view->league_data = $league_data;

            $championship = new Application_Model_DbTable_Championship();
            $championship_data = $championship->getChampionshipData($league_id, $championship_id);

            if ($championship_data) {
                $this->view->headTitle($championship_data->name);
                $this->view->headTitle($this->view->translate('Добавить команду'));
                $this->view->pageTitle($this->view->translate('Добавить команду'));

                $form = new Application_Form_Championship_Team_Add();
                $form->setAction(
                    $this->view->url(
                        array('controller'      => 'championship', 'action' => 'team-add', 'league_id' => $league_id,
                              'championship_id' => $championship_id), 'defaultChampionshipAction', true
                    )
                );
                $this->view->form = $form;

                // add teams
                $teams_data = $this->db->get('team')->getAll(false, array("id", "name"), "ASC");

                $championship_team_db = new Application_Model_DbTable_ChampionshipTeam();

                if ($teams_data) {
                    foreach ($teams_data as $team):
                        if (!$championship_team_db->checkTeamExist($championship_id, $team->id)) {
                            $form->team->addMultiOption($team->id, $team->name);
                        }
                    endforeach;
                } else {
                    $this->messages->addError("{$this->view->translate('Команды не найдены!')}");
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
                                                                                  => $file['logo']['destination'] . '/'
                                . $newName, 'overwrite'                           => true));

                                $filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

                                $new_championship_team_data['url_logo']
                                    = '/data-content/data-uploads/championship/team/logo/' . $newName;
                            }
                        }

                        //receive and rename logo file
                        if ($form->getValue('logo_team')) {
                            if ($form->logo->receive()) {
                                $file = $form->logo_team->getFileInfo();
                                $ext = pathinfo($file['logo_team']['name'], PATHINFO_EXTENSION);
                                $newName = Date('Y-m-d_H-i-s') . strtolower('_logo_car' . '.' . $ext);

                                $filterRename = new Zend_Filter_File_Rename(array('target'
                                                                                  => $file['logo_team']['destination']
                                . '/' . $newName, 'overwrite'                     => true));

                                $filterRename->filter(
                                    $file['logo_team']['destination'] . '/' . $file['logo_team']['name']
                                );

                                $new_championship_team_data['url_logo_car']
                                    = '/data-content/data-uploads/championship/team/car/' . $newName;
                            }
                        }

                        // save new championship to db
                        $date = date('Y-m-d H:i:s');

                        $new_championship_team_data['name'] = $form->getValue('name');
                        $new_championship_team_data['championship_id'] = $championship_id;
                        $new_championship_team_data['team_id'] = $form->getValue('team');
                        $new_championship_team_data['team_number'] = $form->getValue('team_number');
                        $new_championship_team_data['date_create'] = $date;
                        $new_championship_team_data['date_edit'] = $date;

                        $championship_team = new Application_Model_DbTable_ChampionshipTeam();
                        $newChampionshipTeam = $championship_team->createRow($new_championship_team_data);
                        $newChampionshipTeam->save();

                        $this->redirect(
                            $this->view->url(
                                array('module'          => 'default', 'controller' => 'championship', 'action' => 'id',
                                      'league_id'       => $league_id,
                                      'championship_id' => $championship_id), 'defaultChampionshipId', true
                            )
                        );
                    } else {
                        $this->messages->addError(
                            $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }
            } else {
                //error message if championship not found
                $this->messages->addError("{$this->view->translate('Чемпионат не существует!')}");
                //head title
                $this->view->headTitle(
                    "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не существует!')}"
                );
                $this->view->pageTitle(
                    "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не существует!')}"
                );
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
            //head title
            $this->view->headTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

    public function teamEditAction()
    {
        $request = $this->getRequest();
        $league_id = $request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');
        $team_id = $request->getParam('team_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->league_data = $league_data;
            //Set titles for html head title
            $this->view->headTitle($this->view->translate('Лига'));
            $this->view->headTitle($league_data->name);

            $championship = new Application_Model_DbTable_Championship();
            $championship_data = $championship->getChampionshipData($league_id, $championship_id);

            if ($championship_data) {
                $championship_team = new Application_Model_DbTable_ChampionshipTeam();
                $championship_team_data = $championship_team->getTeamData($championship_id, $team_id);

                if ($championship_team_data) {
                    //Set titles for html head title
                    $this->view->headTitle($this->view->translate('Чемпионат'));
                    $this->view->headTitle($championship_data->name);
                    $this->view->headTitle($this->view->translate('Редактировать команду'));
                    // Set page title
                    $this->view->pageTitle($this->view->translate('Редактировать команду'));

                    $form = new Application_Form_Championship_Team_Edit();
                    $form->setAction(
                        $this->view->url(
                            array('controller' => 'championship', 'action' => 'team-edit',
                                  'league_id'  => $league_id, 'championship_id' => $championship_id,
                                  'team_id'    => $team_id), 'defaultChampionshipTeam', true
                        )
                    );
                    $form->cancel->setAttrib(
                        'onClick', "location.href=\"{$this->view->url(
                            array('controller' => 'championship', 'action' => 'team',
                                  'league_id'  => $league_id, 'championship_id' => $championship_id,
                                  'team_id'    => $team_id), 'defaultChampionshipTeam', true
                        )}\""
                    );

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
                                                                                                              =>
                                                                                      $file['logo']['destination'] . '/'
                                                                                      . $newName, 'overwrite' => true));

                                    $filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

                                    $new_championship_team_data['url_logo']
                                        = '/data-content/data-uploads/championship/team/logo/' . $newName;

                                    if ($new_championship_team_data['url_logo'] != $championship_team_data['url_logo']
                                    ) {
                                        unlink(
                                            APPLICATION_PATH . '/../public_html' . $championship_team_data['url_logo']
                                        );
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
                                                                                                  =>
                                                                                      $file['logo_team']['destination']
                                                                                      . '/' . $newName,
                                                                                      'overwrite' => true));

                                    $filterRename->filter(
                                        $file['logo_team']['destination'] . '/' . $file['logo_team']['name']
                                    );

                                    $new_championship_team_data['url_logo_car']
                                        = '/data-content/data-uploads/championship/team/car/' . $newName;

                                    if ($new_championship_team_data['url_logo_car']
                                        != $championship_team_data['url_logo_car']
                                    ) {
                                        unlink(
                                            APPLICATION_PATH . '/../public_html'
                                            . $championship_team_data['url_logo_car']
                                        );
                                    }
                                }
                            }

                            // save new championship to db
                            $date = date('Y-m-d H:i:s');

                            $new_championship_team_data['name'] = $form->getValue('name');
                            $new_championship_team_data['team_id'] = $form->getValue('team');
                            $new_championship_team_data['team_number'] = $form->getValue('team_number');
                            $new_championship_team_data['date_edit'] = $date;

                            $championship_team_where = $championship_team->getAdapter()->quoteInto(
                                'championship_id = ' . $championship_id . ' and team_id = ' . $team_id
                            );
                            $championship_team->update($new_championship_team_data, $championship_team_where);

                            // Update team drivers if base team changed
                            if ($new_championship_team_data['team_id'] != $team_id) {

                                $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();
                                $championship_team_driver_data = $championship_team_driver->getChampionshipTeamDrivers(
                                    $championship_id, $team_id
                                );

                                foreach ($championship_team_driver_data as $driver) {
                                    $new_championship_team_driver_data['team_id']
                                        = $new_championship_team_data['team_id'];
                                    $new_championship_team_driver_data['date_edit'] = $date;

                                    $championship_where = $championship_team_driver->getAdapter()->quoteInto(
                                        'championship_id = ' . $championship_id . ' and team_id = ' . $team_id
                                        . ' and user_id = ' . $driver->user_id
                                    );
                                    $championship_team_driver->update(
                                        $new_championship_team_driver_data, $championship_where
                                    );
                                }
                            }

                            $this->redirect(
                                $this->view->url(
                                    array('controller' => 'championship', 'action' => 'team',
                                          'league_id'  => $league_id, 'championship_id' => $championship_id,
                                          'team_id'    => $new_championship_team_data['team_id']), 'defaultChampionshipTeam',
                                    true
                                )
                            );
                        } else {
                            $this->messages->addError(
                                $this->view->translate(
                                    'Исправьте следующие ошибки для корректного завершения операции!'
                                )
                            );
                        }
                    }

                    $form->name->setValue($championship_team_data->name);
                    $form->team_number->setValue($championship_team_data->team_number);

                    // add teams
                    $teams_data = $this->db->get('team')->getAll(false, array("id", "name"), "ASC");

                    if ($teams_data) {
                        foreach ($teams_data as $team):
                            if (!$championship_team->checkTeamExist($championship_id, $team->id)) {
                                $form->team->addMultiOption($team->id, $team->name);
                            } elseif ($team->id == $championship_team_data->team_id) {
                                $form->team->addMultiOption($team->id, $team->name);
                            }
                        endforeach;
                    } else {
                        $this->messages->addError("{$this->view->translate('Команды не найдены!')}");
                    }

                    $form->team->setValue($championship_team_data->team_id);

                    $this->view->form = $form;
                } else {
                    //error message if team in the championship not found
                    $this->view->errMessage .= $this->view->translate('Команда не существует!') . '<br/>';
                    //head title
                    $this->view->headTitle($this->view->translate('Ошибка!'));
                    $this->view->headTitle($this->view->translate('Команда не существует!'));
                }
            } else {
                //error message if championship not found
                $this->view->errMessage .= $this->view->translate('Чемпионат не существует!') . '<br/>';
                //head title
                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Чемпионат не существует!'));
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
            //head title
            $this->view->headTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
            $this->view->pageTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
        }
    }

    public function teamDeleteAction()
    {

    }

    public function driversAction()
    {
        $request = $this->getRequest();
        $league_id = (int)$request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
            $this->view->headTitle($this->view->translate('Чемпионат'));

            $this->view->league_data = $league_data;

            $championship_data = $this->db->get('championship')->getItem($championship_id);

            if ($championship_data) {
                $this->view->championship_data = $championship_data;

                // Set breadcrumbs for this page
                $this->view->breadcrumb()->LeagueAll('1')->league($league_id, $league_data->name, '1')
                    ->championship($league_id, $championship_id, $championship_data->name, '1')
                    ->drivers($league_id, $championship_id);

                $championshipTeam = new Application_Model_DbTable_ChampionshipTeam();

                //head title
                $this->view->headTitle($championship_data->name);
                $this->view->headTitle($this->view->translate('Команды и Гонщики'));
                $this->view->pageTitle($this->view->translate('Команды и Гонщики'));

                $championship_team_data = $championshipTeam->getAllTeamsData($championship_id);

                if ($championship_team_data) {
                    $this->view->championshipTeams = $championship_team_data;
                } else {
                    //error message if team in the championship not found
                    $this->messages->addInfo(
                        $this->view->translate(
                            "В чемпионате <strong>\"{$championship_data->name}\"</strong> нет команд!"
                        )
                    );
                    //head title
                    $this->view->headTitle(
                        "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команды не найдены!')}"
                    );
                    $this->view->pageTitle(
                        "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команды не найдены!')}"
                    );
                }
            } else {
                //error message if championship not found
                $this->messages->addError($this->view->translate('Запрашиваемый чемпионат не существует!'));
                //head title
                $this->view->headTitle(
                    "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не существует!')}"
                );
                $this->view->pageTitle(
                    "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не существует!')}"
                );
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
            //head title
            $this->view->headTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
            $this->view->pageTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
        }
    }

    public function driverAddAction()
    {
        $request = $this->getRequest();
        $league_id = $request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');
        $team_id = $request->getParam('team_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
            $this->view->headTitle($this->view->translate('Чемпионат'));

            $this->view->league_data = $league_data;

            $championship_data = $this->db->get('championship')->getItem($championship_id);

            if ($championship_data) {
                //head title
                $this->view->headTitle($championship_data->name);
                $this->view->championship_data = $championship_data;

                $championship_team = new Application_Model_DbTable_ChampionshipTeam();
                $championship_team_data = $championship_team->getTeamData($championship_id, $team_id);
                if ($championship_team_data) {
                    $this->view->team_data = $championship_team_data;
                    //head title
                    $this->view->headTitle(
                        "{$this->view->translate(
                            'Команда'
                        )} :: {$championship_team_data->name} :: {$this->view->translate('Добавить гонщика')}"
                    );
                    //page title
                    $this->view->pageTitle(
                        "{$this->view->translate(
                            'Команда'
                        )} :: {$championship_team_data->name} :: {$this->view->translate('Добавить гонщика')}"
                    );

                    $form = new Application_Form_Championship_Driver_Add();
                    $form->setAction(
                        $this->view->url(
                            array('controller' => 'championship', 'action' => 'driver-add',
                                  'league_id'  => $league_id, 'championship_id' => $championship_id,
                                  'team_id'    => $team_id), 'defaultChampionshipTeam', true
                        )
                    );
                    $form->cancel->setAttrib(
                        'onClick', "location.href=\"{$this->view->url(
                            array('controller' => 'championship', 'action' => 'team',
                                  'league_id'  => $league_id, 'championship_id' => $championship_id,
                                  'team_id'    => $team_id), 'defaultChampionshipTeam', true
                        )}\""
                    );

                    $user = new Application_Model_DbTable_User();

                    $users_data = $user->getAllUsers('ASC');

                    $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();

                    if ($users_data) {
                        foreach ($users_data as $user) {
                            if (!$championship_team_driver->checkChampionshipDriverExist($championship_id, $user->id)) {
                                $form->driver->addMultiOption(
                                    $user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')'
                                );
                            }
                        }
                    } else {
                        //error message if no users on the site
                        $this->messages->addError("{$this->view->translate('Гонщики на сайте не найдены!')}");
                    }

                    if ($this->getRequest()->isPost()) {
                        if ($form->isValid($request->getPost())) {
                            if (!$championship_team_driver->checkChampionshipDriverExist(
                                $championship_id, $form->getValue('driver')
                            )
                            ) {
                                //saving new data to DB
                                $new_championship_team_driver_data = array();

                                // save new driver to team at championship to db
                                $date = date('Y-m-d H:i:s');

                                $new_championship_team_driver_data['championship_id'] = $championship_id;
                                $new_championship_team_driver_data['team_id'] = $team_id;
                                $new_championship_team_driver_data['user_id'] = $form->getValue('driver');
                                //$new_championship_team_driver_data['team_role_id'] = $form->getValue('team_role_id');
                                $new_championship_team_driver_data['driver_number'] = $form->getValue('driver_number');
                                $new_championship_team_driver_data['date_create'] = $date;
                                $new_championship_team_driver_data['date_edit'] = $date;

                                $newChampionshipTeamDriver = $championship_team_driver->createRow(
                                    $new_championship_team_driver_data
                                );
                                $newChampionshipTeamDriver->save();

                                $this->messages->addSuccess(
                                    "{$this->view->translate(
                                        "Гонщик <strong>\"{$form->driver->getMultiOption(
                                            $form->getValue('driver')
                                        )}\"</strong> успешно добавлен в комаду <strong>\"{$championship_team_data->name}\"</strong>"
                                    )}"
                                );
                                $this->redirect(
                                    $this->view->url(
                                        array('controller' => 'championship', 'action' => 'team',
                                              'league_id'  => $league_id, 'championship_id' => $championship_id,
                                              'team_id'    => $team_id), 'defaultChampionshipTeam', true
                                    )
                                );
                            }
                        } else {
                            $this->messages->addError(
                                $this->view->translate(
                                    'Исправьте следующие ошибки для корректного завершения операции!'
                                )
                            );
                        }
                    }
                    $this->view->form = $form;
                } else {
                    //error message if team for this championship not found
                    $this->messages->addError("{$this->view->translate('Команда в чемпионате не существует!')}");
                    //head title
                    $this->view->headTitle(
                        "{$this->view->translate('Добавить гонщика в команду')} :: {$this->view->translate(
                            'Ошибка!'
                        )} :: {$this->view->translate('Команда не существует!')}"
                    );
                    $this->view->pageTitle(
                        "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команда не существует!')}"
                    );
                }
            } else {
                //error message if championship not found
                $this->messages->addError("{$this->view->translate('Чемпионат не существует!')}");
                //head title
                $this->view->headTitle(
                    "{$this->view->translate('Добавить гонщика в команду')} :: {$this->view->translate(
                        'Ошибка!'
                    )} :: {$this->view->translate('Чемпионат не существует!')}"
                );
                $this->view->pageTitle(
                    "{$this->view->translate('Добавить гонщика в команду')} :: {$this->view->translate(
                        'Ошибка!'
                    )} :: {$this->view->translate('Чемпионат не существует!')}"
                );
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
            //head title
            $this->view->headTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
            $this->view->pageTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
        }
    }

    public function driverDeleteAction()
    {
        $request = $this->getRequest();
        $league_id = $request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');
        $team_id = $request->getParam('team_id');
        $user_id = $request->getParam('user_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
            $this->view->headTitle($this->view->translate('Чемпионат'));

            $this->view->league_data = $league_data;

            $championship = new Application_Model_DbTable_Championship();
            $championship_data = $championship->getChampionshipData($league_id, $championship_id);

            if ($championship_data) {
                //head title
                $this->view->headTitle($championship_data->name);
                $this->view->championship_data = $championship_data;

                $championship_team = new Application_Model_DbTable_ChampionshipTeam();
                $championship_team_data = $championship_team->getTeamData($championship_id, $team_id);
                if ($championship_team_data) {
                    $this->view->team_data = $championship_team_data;
                    $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();
                    //get data for deleting driver
                    $championship_team_driver_data = $championship_team_driver->getChampionshipTeamDriver(
                        $championship_id, $team_id, $user_id
                    );

                    if ($championship_team_driver_data) {
                        //head title1
                        $this->view->headTitle(
                            "{$this->view->translate(
                                'Команда'
                            )} :: {$championship_team_data->name} :: {$this->view->translate('Удалить гонщика')}"
                        );
                        $this->view->pageTitle(
                            "{$this->view->translate(
                                'Команда'
                            )} :: {$championship_team_data->name} :: {$this->view->translate('Удалить гонщика')}"
                        );

                        $form = new Application_Form_Championship_Driver_Delete();
                        $form->setAction(
                            $this->view->url(
                                array('controller' => 'championship', 'action' => 'driver-delete',
                                      'league_id'  => $league_id, 'championship_id' => $championship_id,
                                      'team_id'    => $team_id, 'user_id' => $user_id), 'defaultChampionshipTeamDriver', true
                            )
                        );
                        $form->cancel->setAttrib(
                            'onClick', "location.href=\"{$this->view->url(
                                array('controller' => 'championship', 'action' => 'team',
                                      'league_id'  => $league_id, 'championship_id' => $championship_id,
                                      'team_id'    => $team_id), 'defaultChampionshipTeam', true
                            )}\""
                        );

                        $this->messages->addWarning(
                            "{$this->view->translate('Вы действительно хотите удалить гонщика')} "
                            . "<strong>\"{$championship_team_driver_data->user_name} {$championship_team_driver_data->user_surname} ({$championship_team_driver_data->user_login})\" </strong>"
                            . "{$this->view->translate('из команды')} <strong>{$championship_team_data->name}</strong>?"
                        );

                        if ($this->getRequest()->isPost()) {
                            if ($form->isValid($request->getPost())) {
                                //remove driver from the team
                                $championship_team_driver_where = $championship_team_driver->getAdapter()->quoteInto(
                                    "championship_id = {$championship_id} and team_id = {$team_id} and user_id = {$user_id}"
                                );
                                $championship_team_driver->delete($championship_team_driver_where);

                                $this->view->showMessages()->clearMessages();
                                $this->messages->addSuccess(
                                    "{$this->view->translate(
                                        "Гонщик <strong>\"{$championship_team_driver_data->user_name} {$championship_team_driver_data->user_surname} ({$championship_team_driver_data->user_login})\"</strong> успешно удален из комады <strong>\"{$championship_team_data->name}\"</strong>"
                                    )}"
                                );

                                $this->redirect(
                                    $this->view->url(
                                        array('controller' => 'championship', 'action' => 'team',
                                              'league_id'  => $league_id, 'championship_id' => $championship_id,
                                              'team_id'    => $team_id), 'defaultChampionshipTeam', true
                                    )
                                );
                            } else {
                                $this->messages->addError(
                                    $this->view->translate(
                                        'Исправьте следующие ошибки для корректного завершения операции!'
                                    )
                                );
                            }
                        }
                        $this->view->team_driver_data = $championship_team_driver_data;
                        $this->view->form = $form;
                    } else {
                        //error message if driver in the team for this championship not found
                        $this->messages->addError("{$this->view->translate('Гонщик в команде не существует!')}");

                        $this->view->headTitle(
                            "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Гонщик не существует!')}"
                        );
                        $this->view->pageTitle(
                            "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Гонщик не существует!')}"
                        );
                    }
                } else {
                    //error message if team for this championship not found
                    $this->messages->addError("{$this->view->translate('Команда в чемпионате не существует!')}");
                    //head title
                    $this->view->headTitle(
                        "{$this->view->translate('Удалить гонщика из команды')} :: {$this->view->translate(
                            'Ошибка!'
                        )} :: {$this->view->translate('Команда не существует!')}"
                    );
                    $this->view->pageTitle(
                        "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команда не существует!')}"
                    );
                }
            } else {
                //error message if championship not found
                $this->messages->addError("{$this->view->translate('Чемпионат не существует!')}");
                //head title
                $this->view->headTitle(
                    "{$this->view->translate('Удалить гонщика из команды')} :: {$this->view->translate(
                        'Ошибка!'
                    )} :: {$this->view->translate('Чемпионат не существует!')}"
                );
                $this->view->pageTitle(
                    "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не существует!')}"
                );
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
            //head title
            $this->view->headTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
            $this->view->pageTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
        }
    }

    public function driverEditAction()
    {
        $request = $this->getRequest();

        $league_id = $request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');
        $team_id = $request->getParam('team_id');
        $user_id = $request->getParam('user_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
            $this->view->headTitle($this->view->translate('Чемпионат'));
            $this->view->league_data = $league_data;

            $championship = new Application_Model_DbTable_Championship();
            $championship_data = $championship->getChampionshipData($league_id, $championship_id);

            if ($championship_data) {
                //head title
                $this->view->headTitle($championship_data->name);
                $this->view->championship_data = $championship_data;

                $championship_team = new Application_Model_DbTable_ChampionshipTeam();
                $championship_team_data = $championship_team->getTeamData($championship_id, $team_id);
                if ($championship_team_data) {
                    $this->view->team_data = $championship_team_data;
                    $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();
                    //get data for deleting driver
                    $championship_team_driver_data = $championship_team_driver->getChampionshipTeamDriver(
                        $championship_id, $team_id, $user_id
                    );

                    if ($championship_team_driver_data) {
                        //head title
                        $this->view->headTitle(
                            "{$this->view->translate(
                                'Команда'
                            )} :: {$championship_team_data->name} :: {$this->view->translate('Редактировать гонщика')}"
                        );
                        $this->view->pageTitle(
                            "{$this->view->translate(
                                'Команда'
                            )} :: {$championship_team_data->name} :: {$this->view->translate('Редактировать гонщика')} :: "
                            . "{$championship_team_driver_data->user_name} {$championship_team_driver_data->user_surname}"
                        );

                        $form = new Application_Form_Championship_Driver_Edit();
                        $form->setAction(
                            $this->view->url(
                                array('controller' => 'championship', 'action' => 'driver-edit',
                                      'league_id'  => $league_id, 'championship_id' => $championship_id,
                                      'team_id'    => $team_id, 'user_id' => $user_id), 'defaultChampionshipTeamDriver', true
                            )
                        );
                        $form->cancel->setAttrib(
                            'onClick', "location.href=\"{$this->view->url(
                                array('controller' => 'championship', 'action' => 'team',
                                      'league_id'  => $league_id, 'championship_id' => $championship_id,
                                      'team_id'    => $team_id), 'defaultChampionshipTeam', true
                            )}\""
                        );

                        $user = new Application_Model_DbTable_User();

                        $users_data = $user->getAllUsers('ASC');

                        $championship_team_driver = new Application_Model_DbTable_ChampionshipTeamDriver();

                        if ($users_data) {
                            foreach ($users_data as $user) {
                                if (!($championship_team_driver->checkChampionshipDriverExist(
                                        $championship_id, $user->id
                                    ))
                                    || ($user->id == $championship_team_driver_data->user_id)
                                ) {
                                    $form->driver->addMultiOption(
                                        $user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')'
                                    );
                                }
                            }
                            $form->driver->setvalue($championship_team_driver_data->user_id);
                        } else {
                            //error message if no users on the site
                            $this->messages->addError("{$this->view->translate('Гонщики на сайте не найдены!')}");
                        }

                        if ($this->getRequest()->isPost()) {
                            if ($form->isValid($request->getPost())) {
                                if (!($championship_team_driver->checkChampionshipDriverExist(
                                        $championship_id, $form->getValue('driver')
                                    ))
                                    || ($form->getValue('driver') == $championship_team_driver_data->user_id)
                                ) {
                                    //saving new data to DB
                                    $new_championship_team_driver_data = array();

                                    // save new driver to team at championship to db
                                    $date = date('Y-m-d H:i:s');

                                    //$new_championship_team_driver_data['championship_id'] = $championship_id;
                                    //$new_championship_team_driver_data['team_id'] = $team_id;
                                    $new_championship_team_driver_data['user_id'] = $form->getValue('driver');
                                    //$new_championship_team_driver_data['team_role_id'] = $form->getValue('team_role_id');
                                    $new_championship_team_driver_data['driver_number'] = $form->getValue(
                                        'driver_number'
                                    );
                                    $new_championship_team_driver_data['date_edit'] = $date;

                                    $championship_where = $championship_team_driver->getAdapter()->quoteInto(
                                        "championship_id = {$championship_id} and team_id = {$team_id} and user_id = {$user_id}"
                                    );

                                    $championship_team_driver->update(
                                        $new_championship_team_driver_data, $championship_where
                                    );

                                    $this->messages->addSuccess(
                                        "{$this->view->translate(
                                            "Данные гонщика <strong>\"{$form->driver->getMultiOption(
                                                $form->getValue('driver')
                                            )}\"</strong> успешно обновлены в комаде <strong>\"{$championship_team_data->name}\"</strong>"
                                        )}"
                                    );
                                    $this->redirect(
                                        $this->view->url(
                                            array('controller' => 'championship', 'action' => 'team',
                                                  'league_id'  => $league_id, 'championship_id' => $championship_id,
                                                  'team_id'    => $team_id), 'defaultChampionshipTeam', true
                                        )
                                    );
                                }
                            } else {
                                $this->messages->addError(
                                    $this->view->translate(
                                        'Исправьте следующие ошибки для корректного завершения операции!'
                                    )
                                );
                            }
                        }
                        //$form->driver_team_role->setvalue($championship_team_driver_data->team_role_id);
                        $form->driver_number->setvalue($championship_team_driver_data->driver_number);
                        $this->view->form = $form;
                    } else {
                        //error message if driver in the team for this championship not found
                        $this->messages->addError("{$this->view->translate('Гонщик в команде не существует!')}");

                        $this->view->headTitle(
                            "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Гонщик не существует!')}"
                        );
                        $this->view->pageTitle(
                            "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Гонщик не существует!')}"
                        );
                    }
                } else {
                    //error message if team for this championship not found
                    $this->messages->addError("{$this->view->translate('Команда в чемпионате не существует!')}");
                    //head title
                    $this->view->headTitle($this->view->translate('Редактировать гонщика'));
                    $this->view->pageTitle(
                        "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команда не существует!')}"
                    );
                }
            } else {
                //error message if championship not found
                $this->messages->addError("{$this->view->translate('Чемпионат не существует!')}");
                //head title
                $this->view->headTitle($this->view->translate('Редактировать гонщика'));
                $this->view->pageTitle(
                    "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не существует!')}"
                );
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
            //head title
            $this->view->headTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
            $this->view->pageTitle(
                "{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
            );
        }
    }

    public function dsAction()
    {
        $this->view->headTitle($this->view->translate('Личный зачет'));
        $this->view->pageTitle($this->view->translate('Личный зачет'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');

        $this->messages->addInfo(
            $this->view->translate('Приносим свои извинения. Функционал данной страницы находится в разработке!')
        );
    }

    public function tsAction()
    {
        $this->view->headTitle($this->view->translate('Командный зачет'));
        $this->view->pageTitle($this->view->translate('Командный зачет'));

        $request = $this->getRequest();
        $championship_id = $request->getParam('championship_id');

        $this->messages->addInfo(
            $this->view->translate('Приносим свои извинения. Функционал данной страницы находится в разработке!')
        );
    }

    public function calendarAction()
    {
        $this->view->headTitle($this->view->translate('Календарь'));
        $this->view->pageTitle($this->view->translate('Календарь'));

        $request = $this->getRequest();

        $league_id = (int)$request->getParam('league_id');
        $championship_id = (int)$request->getParam('championship_id');

        $league_data = $this->db->get('league')->getItem($league_id);

        if ($league_data) {
            $this->view->league_data = $league_data;

            $this->view->headTitle($this->view->translate('Лига'));
            $this->view->headTitle($league_data->name);

            $championship_data = $this->db->get('championship')->getItem($championship_id);

            if ($championship_data) {
                $this->view->championship_data = $championship_data;
                $this->view->headTitle($this->view->translate('Чемпионат'));
                $this->view->headTitle($championship_data->name);
                $this->view->pageTitle($this->view->translate('Чемпионат'));
                $this->view->pageTitle($championship_data->name);

                //Set breadcrumbs for this page
                $this->view->breadcrumb()->LeagueAll('1')->league($league_id, $league_data->name, '1')
                    ->championship($league_id, $championship_id, $championship_data->name, '1')
                    ->calendar($league_id, $championship_id);

                $championship_races_data = $this->db->get('championship_race')->getAll(
                    array(
                         'championship_id' => $championship_data->id
                    ), "id, name, description", array('race_number' => 'ASC'), false
                );

                if ($championship_races_data) {
                    $this->view->championship_races_data = $championship_races_data;
                } else {
                    $this->messages->addInfo($this->view->translate('В этом чемпионате нет гонок!'));
                }
            }
        }
    }
}
