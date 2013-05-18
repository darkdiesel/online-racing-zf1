<?php

class Application_Model_DbTable_Track extends Zend_Db_Table_Abstract {

    protected $_name = 'track';
    protected $_primary = 'id';

    public function getTrackData($id) {
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

    public function getTracksPager($count, $page, $page_range, $order) {
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

    /*
      public function checkExistCountryNativeName($country_name) {
      $model = new self;
      $select = $model->select()
      ->from($this->_name, 'id')
      ->where('native_name = ?', $country_name)
      ->columns('id');

      $track_data = $model->fetchRow($select);

      if (count($track_data) != 0) {
      return $track_data->id;
      } else {
      return FALSE;
      }
      }

      public function checkExistCountryAbbreviation($country_abbreviation) {
      $model = new self;
      $select = $model->select()
      ->from($this->_name, 'id')
      ->where('abbreviation = ?', $country_abbreviation)
      ->columns('id');

      $track_data = $model->fetchRow($select);

      if (count($track_data) != 0) {
      return $track_data->id;
      } else {
      return FALSE;
      }
      }

      public function getCountryName($id) {
      $model = new self;
      $select = $model->select()
      ->from($this->_name, 'id')
      ->where('id = ?', $id)
      ->columns(array('native_name'));

      $track_data = $model->fetchRow($select);

      if (count($track_data) != 0) {
      return $track_data->native_name;
      } else {
      return FALSE;
      }
      }

      public function getCountriesName($order) {
      $model = new self;

      $select = $model->select()
      ->from($this->_name, 'native_name')
      ->columns(array('id', 'native_name', 'english_name'))
      ->order('native_name ' . $order);

      $countries = $model->fetchAll($select);

      if (count($countries) != 0) {
      return $countries;
      } else {
      return FALSE;
      }
      }

      public function getCountryId($country_name) {
      $model = new self;
      $select = $model->select()
      ->from($this->_name, 'native_name')
      ->where('native_name = ?', $country_name)
      ->columns(array('id'));

      $track_data = $model->fetchRow($select);

      if (count($track_data) != 0) {
      return $track_data->id;
      } else {
      return FALSE;
      }
      }

     */
}