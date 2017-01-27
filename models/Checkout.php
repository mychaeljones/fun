<?php

class Application_Model_Checkout {
    # Object level variables

    protected $_db;
    
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');        
    }
    
    public static function getShippingCountries() {
     
        return array("US" => 'United States',
                     "Canada" => 'Canada');
    }   
    
    public function saveAddress($data) {
         try {          
            $insert = $this->_db->insert('user_address', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    public function getAddress($userId, $type, $preference = 0) {
       
        if ($preference == 1) {
            $preference = 'and preference = 1';
        }
           
        $address = $this->_db->fetchAll("SELECT * FROM user_address WHERE user_id = '$userId'  
            AND active = 1 $preference");
        
        return $address;
    }
       
}
