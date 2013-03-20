<?php

class Application_Model_DbTable_ChampionshipTeam extends Zend_Db_Table_Abstract {

    protected $_name = 'championship_team';
    protected $_primary = 'championship_id';

    //get data for championship team and it drivers
    public function getFullTeamData($championship_id, $team_id) {
        $model = new self;

        $select = $model->select()
                ->from(array('CT' => $this->_name))
                ->setIntegrityCheck(false)
                ->where('CT.championship_id = ?', $championship_id)
                ->where('CT.team_id = ?', $team_id)
                ->join(array('C' => 'championship'), 'C.id = CT.championship_id', array(
                    'championship_name' => 'C.name',
                ))
                ->columns('*');

        $team_data = $model->fetchRow($select);

        if (count($team_data) != 0) {
            return $team_data;
        } else {
            return FALSE;
        }
    }

    // get data for one team at championship
    public function getTeamData($team_id, $championship_id) {
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

    // get data for all team at championship
    public function getAllTeamData($championship_id) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name)
                ->where('championship_id = ?', $championship_id)
                ->columns('*')
                ->order('team_number ASC');

        $championshipTeams = $model->fetchAll($select);

        if (count($championshipTeams) != 0) {
            return $championshipTeams;
        } else {
            return FALSE;
        }
    }

    public function checkTeamExist($championship_id, $team_id) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name)
                ->where('championship_id = ?', $championship_id)
                ->where('team_id = ?', $team_id)
                ->columns('*');

        $championshipTeams = $model->fetchAll($select);

        if (count($championshipTeams) != 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

}