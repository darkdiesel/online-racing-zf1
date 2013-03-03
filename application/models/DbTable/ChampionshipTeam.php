<?php

class Application_Model_DbTable_ChampionshipTeam extends Zend_Db_Table_Abstract {

    protected $_name = 'championship_team';
    protected $_primary = 'championship_id';

    public function getId($role_name) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'id')
                ->where('name = ?', $role_name)
                ->columns('id');

        $user_role_data = $model->fetchRow($select);

        if (count($user_role_data) != 0) {
            return $user_role_data->id;
        } else {
            return FALSE;
        }
    }

    // get data for one team at championship
    public function getData($team_id, $championship_id) {
        $model = new self;
        
        $select = $model->select()
		        ->from($this->_name)
		        ->where('championship_id = ?', $championship_id)
		        ->where('team_id = ?', $team_id)
		        ->columns('*');
        
        $team_data = $model->fetchRow($select);
        
        if (count($team_data) != 0) {
        	return $team_data;
        } else {
        	return FALSE;
        }
    }

    // get data for one team at championship
    public function getAllData($championship_id) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name)
                ->where('championship_id = ?', $championship_id)
                ->columns('*');

        $championshipTeams = $model->fetchAll($select);

        if (count($championshipTeams) != 0) {
            return $championshipTeams;
        } else {
            return FALSE;
        }
    }

}