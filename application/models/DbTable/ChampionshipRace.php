<?php

class Application_Model_DbTable_ChampionshipRace extends Zend_Db_Table_Abstract {

	protected $_name = 'championship_race';
	protected $_primary = 'id';

	public function getRaceData($id) {
		$model = new self;
		$select = $model->select()
				->setIntegrityCheck(false)
				->from(array('t' => $this->_name))
				->where('t.id = ?', $id)
				->join(array('c' => 'country'), 't.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
					'track_url_image_glossy_wave' => 'c.url_image_glossy_wave',
					'track_url_image_round' => 'c.url_image_round',))
				->columns('*');

		$track_data = $model->fetchRow($select);

		if (count($track_data) != 0) {
			return $track_data;
		} else {
			return FALSE;
		}
	}

	public function getRacesPagerByChampionship($count, $page, $page_range, $order, $championship_id) {
		$model = new self;

		$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
						->select()
						->setIntegrityCheck(false)
						->from(array('c_r' => $this->_name))
						->where('c_r.championship_id = ?', $championship_id)
						->join(array('t' => 'track'), 'c_r.track_id = t.id', array('track_name' => 't.name',
							'track_country_id' => 't.country_id',
							'track_url_scheme' => 't.url_track_scheme'))
						->join(array('c' => 'country'), 'c.id = t.country_id', array('country_url_image_glossy_wave' => 'c.url_image_glossy_wave',
							'country_url_image_round' => 'c.url_image_round',))
						->columns('*')
						->order('c_r.id ' . $order)
		);

		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($count);
		$paginator->setCurrentPageNumber($page);
		$paginator->setPageRange($page_range);

		return $paginator;
	}

	public function getRacesPager($count, $page, $page_range, $order) {
		$model = new self;

		$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
						->select()
						->setIntegrityCheck(false)
						->from(array('t' => $this->_name))
						->where('t.id = ?', $id)
						->join(array('c' => 'country'), 't.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
							'track_url_image_glossy_wave' => 'c.url_image_glossy_wave',
							'track_url_image_round' => 'c.url_image_round',))
						->columns('*')
						->order('id ' . $order));

		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($count);
		$paginator->setCurrentPageNumber($page);
		$paginator->setPageRange($page_range);

		return $paginator;
	}

}
