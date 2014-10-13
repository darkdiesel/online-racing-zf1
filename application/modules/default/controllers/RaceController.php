<?php

class RaceController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
//		$this->view->headTitle($this->view->t('Гонка'));
    }

    public function idAction()
    {
        $request = $this->getRequest();
        $league_id = (int)$request->getParam('league_id');
        $championship_id = $request->getParam('championship_id');
        $race_id = $request->getParam('race_id');

        // Gel Leagues
        $query = Doctrine_Query::create()
            ->from('Default_Model_League l')
            ->leftJoin('l.User u')
            ->where('l.ID = ?', $league_id);
        $result = $query->fetchArray();
        $leagueData = $result[0];

        if ($leagueData) {
            $championship = new Application_Model_DbTable_Championship();
            $championship_data = $championship->getChampionshipData($league_id, $championship_id);

            $this->view->headTitle($this->view->t('Лига'));
            $this->view->headTitle($leagueData['Name']);

            $this->view->leagueData = $leagueData;

            if ($championship_data) {
                $this->view->championship_data = $championship_data;
                $this->view->headTitle($this->view->t('Чемпионат'));
                $this->view->headTitle($championship_data->name);
                $this->view->headTitle($this->view->t('Гонка'));

                $race_data = $this->db->get('championship_race')->getItem(array(
                    'id' => $race_id,
                    'championship_id' => array('value' => $championship_id,
                        'condition' => "AND")
                ));

                if ($race_data) {
                    $this->view->pageTitle($race_data->name);
                    $this->view->race_data = $race_data;

                    // Set breadcrumbs for this page
                    $this->view->breadcrumb()->LeagueAll('1')->league($league_id, $leagueData['Name'], '1')
                        ->championship($league_id, $championship_id, $championship_data->name, "1")
                        ->championship_race($league_id, $championship_id, $race_id, $race_data->name);
                    //->championship_race($league_id, $championship_id, $race_id, $race_data->name);
                } else {
                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->messages->addError($this->view->t('Запрашиваемая гонка не найдена!'));
                }
            } else {
                //error message if championship not found
                $this->messages->addError($this->view->t('Запрашиваемый чемпионат не найден!'));
                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Ошибка!'));
            }
        } else {
            //error message if league not found
            $this->messages->addError($this->view->t('Запрашиваемая лига не существует!'));
            //head title
            $this->view->headTitle($this->view->t('Ошибка!'));
            $this->view->pageTitle($this->view->t('Ошибка!'));
        }
    }
}
