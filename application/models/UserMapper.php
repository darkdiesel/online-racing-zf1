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

  public function checkUserStatus($id){
    $result = $this->getDbTable()->fetchRow(array('id = ?' => $id));
    if ($result->activate != "") {
      return 1;
    } else if ($result->enabled != 1) {
              return 2;
            } else {
              return 0;
            }
  }

  public function activateUserByEmail($email, $password, $confirmCode){
    $result = $this->getDbTable()->fetchRow(array('email = ?' => strtolower($email)));

    if (($result->password == $password) && ($result->activate == $confirmCode)) {
      $data = array(
        'activate' => "",
        'enabled' => 1
      );

      $this->getDbTable()->update($data, array('email = ?' => $email));
      return 1;
    } else {
      return 0;
    }
  }

  public function AddNewUser(Application_Model_User $newUser)
  {
      $data = array(
        'login'   => $newUser->getLogin(),
        'email'   => $newUser->getEmail(),
        'password' => sha1($newUser->getPassword()),
        'activate' => $newUser->getActivate(),
        'enabled' => "1",
        'role_id' => "3",
        'created' => date('Y-m-d H:i:s')
      );

      if (null === ($id = $newUser->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
  }
  
  public function  getUserRole($id){
      $result = $this->getDbTable()->fetchRow(array('id = ?' => $id));
      return $result->role_id;
  }
}