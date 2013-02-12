<?php

class Application_Model_DbTable_Country extends Zend_Db_Table_Abstract {

    protected $_name = 'country';
    protected $_primary = 'id';

    public function getCountryData($id) {
        $model = new self;
        $select = $model->select()
                ->from($this->_name, 'id')
                ->where('id = ?', $id)
                ->columns('*');

        $country_data = $model->fetchRow($select);

        if (count($country_data) != 0) {
            return $country_data;
        } else {
            return FALSE;
        }
    }

    public function checkExistCountryNativeName($country_name) {
        $model = new self;
        $select = $model->select()
                ->from($this->_name, 'id')
                ->where('native_name = ?', $country_name)
                ->columns('id');

        $country_data = $model->fetchRow($select);

        if (count($country_data) != 0) {
            return $country_data->id;
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

        $country_data = $model->fetchRow($select);

        if (count($country_data) != 0) {
            return $country_data->id;
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

        $country_data = $model->fetchRow($select);

        if (count($country_data) != 0) {
            return $country_data->native_name;
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

        $country_data = $model->fetchRow($select);

        if (count($country_data) != 0) {
            return $country_data->id;
        } else {
            return FALSE;
        }
    }

    public function getCountriesPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->from($this->_name, 'id')
                                ->columns('*')
                                ->order('id ' . $order));

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

}