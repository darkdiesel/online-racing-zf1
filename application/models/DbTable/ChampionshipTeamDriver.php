<?php

class Application_Model_DbTable_ChampionshipTeamDriver extends Zend_Db_Table_Abstract {

    protected $_name = 'championship_team_driver';
    protected $_primary = 'championship_id';

    public function checkChampionshipTeamDriverExist($championship_id, $team_id, $user_id) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name)
                ->where('championship_id = ?', $championship_id)
                ->where('team_id = ?', $team_id)
                ->where('user_id = ?', $user_id)
                ->columns('*');

        $championshipTeamDrivers = $model->fetchAll($select);

        if (count($championshipTeamDrivers) != 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function checkChampionshipDriverExist($championship_id, $user_id) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name)
                ->where('championship_id = ?', $championship_id)
                ->where('user_id = ?', $user_id)
                ->columns('*');

        $championshipTeamDrivers = $model->fetchAll($select);

        if (count($championshipTeamDrivers) != 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function getChampionshipTeamDrivers($championship_id, $team_id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('CTD' => $this->_name))
                ->where('CTD.championship_id = ?', $championship_id)
                ->where('CTD.team_id = ?', $team_id)
                ->join(array('u' => 'user'), 'CTD.user_id = u.id', array(
                    'user_id' => 'u.id', 
                    'user_login' => 'u.login',
                    'user_name' => 'u.name',
                    'user_surname' => 'u.surname',
                    'user_avatar_type' => 'u.avatar_type',
                    'user_country_id' => 'u.country_id'))
                ->join(array('c' => 'country'), 'u.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
                    'country_url_image_glossy_wave' => 'c.url_image_glossy_wave',
                    'country_native_name' => 'c.native_name',
                    'country_english_name' => 'c.english_name',))
                ->columns('CTD.driver_number')
                ->order('CTD.driver_number ASC');;

        $championshipTeamDrivers = $model->fetchAll($select);

        if (count($championshipTeamDrivers) != 0) {
            return $championshipTeamDrivers;
        } else {
            return FALSE;
        }
    }

}