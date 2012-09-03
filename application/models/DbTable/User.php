<?php
    // application/models/DbTable/User.php
     
    /**
    * This is the DbTable class for the guestbook table.
    */
    class Application_Model_DbTable_User extends Zend_Db_Table_Abstract
    {
        /** Table name */
        protected $_name    = 'user';