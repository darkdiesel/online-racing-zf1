<?php

class Application_Model_DbTable_Country extends Zend_Db_Table_Abstract {

    protected $_name = 'country';
    protected $_primary = 'id';

    public function getName($country_id) {
        $model = new self;
        $select = $model->select()
                ->from(array('c' => $this->_name), 'c.id')
                ->where('с_t.id = ?', $country_id)
                ->columns(array('c.name'));
        $country = $model->fetchRow($select);

        if (count($country) != 0) {
            return $country->name;
        } else {
            return FALSE;
        }
    }

    public function getId($country_name) {
        $model = new self;
        $select = $model->select()
                ->from(array('с' => $this->_name), 'c.name')
                ->where('с.name = ?', $country_name)
                ->columns(array('c.id'));
        $country = $model->fetchRow($select);

        if (count($country) != 0) {
            return $country->id;
        } else {
            return FALSE;
        }
    }

    public function getCountryPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->from($this->_name, 'id')
                                ->columns(array('id', 'name', 'description', 'date_create', 'date_edit'))
                                ->order('id ' . $order));

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function getCountriesName($order) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'name')
                ->columns(array('id', 'name'))
                ->order('name ' . $order);

        $countries = $model->fetchAll($select);

        if (count($countries) != 0) {
            return $countries;
        } else {
            return FALSE;
        }
    }

}