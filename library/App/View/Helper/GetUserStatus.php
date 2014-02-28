<?php

class App_View_Helper_getUserStatus extends Zend_View_Helper_Abstract {

	private $time_period;
	
	public function __construct() {
		$this->time_period = 420;;
	}
	
	function getUserStatus($last_activity) {
		$date_now = new Zend_Date ();
		$date_last_active = new Zend_Date($last_activity);
		
		if (($date_now->toValue() - $date_last_active->toValue()) <= $this->time_period){
			return 'online';
		} else {
			return 'ofline';
		}
	}

}
