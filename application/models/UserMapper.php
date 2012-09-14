<?php
class Application_Model_UserMapper
{
  protected $_dbTable;

  public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }
 
  public function getDbTable()
  {
      if (null === $this->_dbTable) {
          $this->setDbTable('Application_Model_DbTable_User');
      }
      return $this->_dbTable;
  }

	public function fetchAll()
  {
      $resultSet = $this->getDbTable()->fetchAll();
      $entries   = array();
      foreach ($resultSet as $row) {
          $entry = new Application_Model_User();
          /*$entry->setId($row->id)
                ->setEmail($row->email)
                ->setComment($row->comment)
                ->setCreated($row->created);
          $entries[] = $entry;*/
      }
      return $entries;
  }

  public function emailIsAvailable($email)
  {
    $result = $this->getDbTable()->fetchRow(array('email = ?' => strtolower($email)));

    if (0 == count($result)) {
       return true;
    } else {
      return false;
    }
  }

  public function AddNewUser(Application_Model_User $newUser)
  {
      $data = array(
        'login'   => $newUser->getLogin(),
        'email'   => $newUser->getEmail(),
        'password' => $newUser->getPassword(),
        'activate' => $newUser->getActivate(),
        'enabled' => $newUser->getEnabled(),
        'role_id' => $newUser->getRole_id()
      );

      if (null === ($id = $newUser->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
  }
}