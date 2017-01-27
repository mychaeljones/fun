<?php

class Application_Model_Support{
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
    }
  
     public function saveMessage($data) {
        try {              
            $insert =  $this->_db->insert('message', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
  
}
