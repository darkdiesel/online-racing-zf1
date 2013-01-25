<?php

class Application_Model_DbTable_Championship extends Zend_Db_Table_Abstract {

    protected $_name = 'championship';
    protected $_primary = 'id';

    public function getChampionshipData($id) {
        $model = new self;

        $select = $model
                ->select()
                ->setIntegrityCheck(false)
                ->from(array('c' => $this->_name), 'c.id')
                ->where('c.id = ?', $id)
                ->join(array('l' => 'league'), 'c.league_id = l.id', array('league_name' => 'l.name'))
                ->join(array('a' => 'article'), 'c.article_id = a.id', array('rule_name' => 'a.title'))
                ->join(array('g' => 'game'), 'c.game_id = g.id', array('game_name' => 'g.name'))
                ->join(array('u' => 'user'), 'c.user_id = u.id', array('user_login' => 'u.login'))
                ->columns('*');

        $championship = $model->fetchRow($select);

        if (count($championship) != 0) {
            return $championship;
        } else {
            return FALSE;
        }
    }
    
    public function getChampionshipsPagerByLeague($count, $page, $page_range, $order, $league_id) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                    ->select()
                    ->setIntegrityCheck(false)
                    ->from(array('c' => $this->_name), 'c.id')
                    ->where('c.league_id = ?', $league_id)
                    ->join(array('l' => 'league'), 'c.league_id = l.id', array('league_name' => 'l.name'))
                    ->join(array('a' => 'article'), 'c.article_id = a.id', array('rule_name' => 'a.title'))
                    ->join(array('g' => 'game'), 'c.game_id = g.id', array('game_name' => 'g.name'))
                    ->join(array('u' => 'user'), 'c.user_id = u.id', array('user_login' => 'u.login'))
                    ->columns('*')
                    ->order('c.id ' . $order)
                );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

}
