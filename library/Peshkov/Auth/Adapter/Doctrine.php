<?php

class Peshkov_Auth_Adapter_Doctrine implements Zend_Auth_Adapter_Interface
{
    // array containing authenticated user record
    protected $_resultRow;

    // constructor
    // accepts username and password
    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function setIdentityColumn($idencityColumn)
    {
        $this->idencityColumn = $idencityColumn;
        return $this;
    }

    public function setCredentialColumn($credemcialColumn)
    {
        $this->credemcialColumn = $credemcialColumn;
        return $this;
    }

    public function setIdentity($login)
    {
        $this->login = $login;
        return $this;
    }

    public function setCredential($password)
    {
        $this->password = $password;
        return $this;
    }

    // main authentication method
    // queries database for match to authentication credentials
    // returns Zend_Auth_Result with success/failure code
    public function authenticate()
    {
        $q = Doctrine_Query::create()
            ->from($this->modelClass . ' t')
            ->where('t.' . $this->idencityColumn . ' = ?', $this->login);
        $result = $q->fetchArray();

        if (count($result) == 1) {

            if ($result[0][$this->credemcialColumn] == $this->password) {
                $this->_resultRow = $result[0];
                return new Zend_Auth_Result(
                    Zend_Auth_Result::SUCCESS, $this->login, array());
            } else {
                return new Zend_Auth_Result(
                    Zend_Auth_Result::FAILURE, null,
                    array('Authentication unsuccessful')
                );
            }
        } else {
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE, null,
                array('Authentication unsuccessful')
            );
        }
    }

    /**
     * getResultRowObject() - Returns the result row as a stdClass object
     *
     * @param  string|array $returnColumns
     * @param  string|array $omitColumns
     * @return stdClass|boolean
     */
    public function getResultRowObject($returnColumns = null, $omitColumns = null)
    {
        if (!$this->_resultRow) {
            return false;
        }

        $returnObject = new stdClass();

        if (null !== $returnColumns) {

            $availableColumns = array_keys($this->_resultRow);
            foreach ((array)$returnColumns as $returnColumn) {
                if (in_array($returnColumn, $availableColumns)) {
                    $returnObject->{$returnColumn} = $this->_resultRow[$returnColumn];
                }
            }
            return $returnObject;
        } elseif (null !== $omitColumns) {

            $omitColumns = (array)$omitColumns;
            foreach ($this->_resultRow as $resultColumn => $resultValue) {
                if (!in_array($resultColumn, $omitColumns)) {
                    $returnObject->{$resultColumn} = $resultValue;
                }
            }
            return $returnObject;

        } else {
            foreach ($this->_resultRow as $resultColumn => $resultValue) {
                $returnObject->{$resultColumn} = $resultValue;
            }
            return $returnObject;
        }
    }

    /**
     * getResultArray() - Returns the result array
     *
     * @param  string|array $excludeFields
     * @return array
     */
    public function getResultArray($excludeFields = null)
    {
        if (!$this->_resultRow) {
            return false;
        }

        if ($excludeFields != null) {
            $excludeFields = (array)$excludeFields;
            foreach ($this->_resultRow as $key => $value) {
                if (!in_array($key, $excludeFields)) {
                    $returnArray[$key] = $value;
                }
            }
            return $returnArray;
        } else {
            return $this->_resultRow;
        }
    }
}
