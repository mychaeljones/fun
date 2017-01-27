<?php

class Application_Model_User {
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
    }

    protected static function _getAuthAdapter() {     
    
        $dbAdapter = Application::getDBConnection();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
             
        $authAdapter = new Zend_Auth_Adapter_DbTable(
                $dbAdapter, 'user', 'email', 'password',  "SHA1(CONCAT(?,salt)) AND status = 'Active'"
        );                    
        return $authAdapter;
    }
    
    public function authenticate($post)
    {
        // Get our authentication adapter and check credentials
        $adapter = self::_getAuthAdapter();
        $adapter->setIdentity($post['email']); 
      //  echo md5($post['password']);
        $adapter->setCredential($post['password']);
       
        $auth = Zend_Auth::getInstance();
        
        $result = $auth->authenticate($adapter);
        if ($result->isValid()) {        	        	
            $user = $adapter->getResultRowObject();            
            $auth->getStorage()->write($user);             
          
            $authSession = new Zend_Session_Namespace('Zend_Auth');
            $authSession->setExpirationSeconds(36000);
            
            self::editUser(array('last_login' =>date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']), (int)$auth->getIdentity()->id);
            return true;
        }
        return false;
    }  
    
    public function getUser($id = null) {
        $user = $this->_db->fetchRow("SELECT user.*, account_user.parent_user from user left join account_user on account_user.user = user.id             
                where user.id = '$id'");        
        return $user?$user:false;
    }
    public function getUserByEmail($email = null) {
        $user = $this->_db->fetchRow("SELECT * from user where email = '$email'");        
        return $user?$user:false;
    }
    
    
    public function getUsers($parentId = null, $filter = null) {       
        $sql = "select * from account_user left join user on user.id = account_user.user
            WHERE parent_user = '$parentId' AND  status != 'Disabled'
            AND type = '$filter'
            ORDER BY user.firstname";      
        $results = $this->_db->fetchAll($sql);
        return $results;
    }
    
    public function getUsersPage ($type, $sort, $order, $start, $end, $parent_user = null, $potential = null, $source = null, $lastAttempt = null) {    
        $filter = '';
        if ($parent_user) {
            $filter = " AND account_user.parent_user = '$parent_user' ";
        } else {
            $filter = " AND account_user.parent_user != '10318' ";
        }
        if ($potential) {
            $filter .= " AND user.potential = '$potential' ";
        }
        if ($source) {
            $filter .= " AND user.source = '$source' ";
        }
        if ($sort == 'show_time') {
            $sort = 'created_time';
        }
        $contact = '';  $lastContact = '';                
        if ($lastAttempt) {
            if($lastAttempt == 10) {              
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >= 1 AND DATEDIFF(NOW() , user.created_time ) < 10
                    ELSE contactDays >=1 AND contactDays < 10 END";                 
            }elseif($lastAttempt == 30) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >=10 AND DATEDIFF(NOW() , user.created_time ) < 30
                    ELSE contactDays >=10 AND contactDays < 30 END";                
            }elseif($lastAttempt == 60) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >=30 AND DATEDIFF(NOW() , user.created_time ) < 60
                    ELSE contactDays >= 30 AND contactDays < 60 END";                 
            }elseif($lastAttempt == 90) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >=60 AND DATEDIFF(NOW() , user.created_time ) < 90
                    ELSE contactDays >=60 AND contactDays <90 END";                 
            }elseif($lastAttempt == 91) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >= 91
                    ELSE contactDays >=91 END";                 
            }          
            
            $query = "select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, user.id, user.businessname, user.contactphone, 
        user.firstname, user.lastname, user.email, user.created_time, user.state, user.country,
                DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, 
                account_user.parent_user, user.potential,contactDays,               
                
                case when note_time = user.created_time THEN NULL ELSE enter_time END enter_time
                from user left join account_user on user.id = account_user.user 
                and user.status != 'Disabled' AND user.type = '$type' $filter
        left join user u on u.id = account_user.parent_user 
        left join (SELECT DATE_FORMAT(max(enter_time),'%m/%d/%y   %h:%i %p') as enter_time,
                max(enter_time) as note_time,
                DATEDIFF(NOW(), max(enter_time)) AS contactDays, user_id 
                FROM `user_notes` group by user_id) b on b.user_id = user.id 
        WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
            ORDER BY $sort $order LIMIT $start, $end";     
                       
        } 
         if (!$lastAttempt) {
            $ids = $this->_db->fetchAll("SELECT id from user 
                    left join account_user on account_user.user = user.id
                    WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
                    ORDER BY $sort $order LIMIT $start, $end
                    ");       
            if(!$ids) return;

            $thisIds = ''; $index = 0;
            foreach ($ids as $id) {
                $thisIds[$index] = $id['id'];
                ++$index;
            }
            $finalIds = "'" . implode("','", $thisIds) . "'"; 

            $query = "select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, user.id, user.businessname, user.contactphone, 
            user.firstname, user.lastname, user.email, user.created_time,  user.state, user.country,
                    DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, 
                    account_user.parent_user, user.potential,contactDays,               

                    case when note_time = user.created_time THEN NULL ELSE enter_time END enter_time
                    from user left join account_user on user.id = account_user.user                 
            left join user u on u.id = account_user.parent_user 
            left join (SELECT DATE_FORMAT(max(enter_time),'%m/%d/%y   %h:%i %p') as enter_time,
                    max(enter_time) as note_time,
                    DATEDIFF(NOW(), max(enter_time)) AS contactDays, user_id 
                    FROM `user_notes` 
                    WHERE user_id in ($finalIds) group by user_id) 
                    b on b.user_id = user.id 
            WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
                ORDER BY $sort $order LIMIT $start, $end";
            
         }
        $result = $this->_db->fetchAll($query);        
        return $result;
    }
    
    public function getUsersTotal($type, $parent_user = null, $potential = null, $source = null, $lastAttempt = null) {  
        $filter = '';
        if ($parent_user) {
            $filter = " AND account_user.parent_user = '$parent_user' ";
        } else {
            $filter = " AND account_user.parent_user != '10318' ";
        }
        if($potential) {
            $filter = " AND user.potential = '$potential' ";
        }
        if ($source) {
            $filter .= " AND user.source = '$source' ";
        }
        $contact = '';      
        if ($lastAttempt) {
            if($lastAttempt == 10) {              
                $contact = " AND contactDays >=1 AND contactDays < 10";
            }elseif($lastAttempt == 30) {
                $contact = " AND contactDays >=10 AND contactDays < 30";
            }elseif($lastAttempt == 60) {
                $contact = " AND contactDays >=30 AND contactDays < 60";
            }elseif($lastAttempt == 90) {
                $contact = " AND contactDays >=60 AND contactDays < 90";
            }elseif($lastAttempt == 91) {
                $contact = " AND contactDays >=91";
            }            
        }
        
       /* $result = $this->_db->fetchRow("SELECT count(id) as total from `user` left join account_user on user.id = account_user.user
            WHERE user.status != 'Disabled' and type = '$type' $filter");*/
        $query = "select count(user.id) as total from user
            left join account_user on user.id = account_user.user 
            inner join (SELECT DATEDIFF(NOW(), max(enter_time)) AS contactDays, 
            user_id
            FROM `user_notes`            
            group by user_id) b on b.user_id = user.id 
            WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact";
       // echo $query;
        //die();
        $result = $this->_db->fetchRow($query);
        return $result;
    }
    
    public function getPreviousNext($id, $type, $link) {
        $action = $link == 'prev'?'>':'<';   
        $order = $link == 'prev'?'ASC':'DESC';   
        
        //previous
        $query = "SELECT user.*, account_user.parent_user from user left join account_user on account_user.user = user.id 
            where created_time $action= (SELECT created_time FROM user WHERE id = '$id') AND id !='$id' AND type = 'Lead' 
                AND status != 'Disabled' order by created_time $order limit 1";
       
        $user = $this->_db->fetchRow($query);       
        return $user?$user:false;
    }
    public function getLast() {
        $query = "SELECT user.*, account_user.parent_user from user left join account_user on account_user.user = user.id 
            where status != 'Disabled' order by created_time DESC, id DESC limit 1";       
        $user = $this->_db->fetchRow($query);
        return $user?$user:false;
    }
    
    /*public function getChildAccounts($id = null) {
        $sql = "select * from account_user left join user on user.id = account_user.user
            WHERE parent_user = '$id' AND status != 'pending' ORDER BY user.firstname";   
        //echo $sql;
        $results = $this->_db->fetchAll($sql);
        return $results;        
    }*/
    
    public function getSalesUsers () {       
        $sql = "SELECT id, concat(firstname, ' ', lastname) as name, email FROM user WHERE (role like 'sales%' 
                OR firstname = 'Luis' OR firstname = 'Heather' OR firstname = 'Loli' OR firstname = 'Bridgette')
                and status = 'Active' AND type = 'Internal' ORDER BY firstname";
        $results = $this->_db->fetchAll($sql);
        return $results;
    }
    public function getSalesRep ($userId) {
        $result = $this->_db->fetchRow("SELECT parent_user, concat(firstname, ' ', lastname) as name, user.email from account_user 
            LEFT JOIN user ON account_user.parent_user = user.id 
            WHERE account_user.user = '$userId' ");
        return $result;
    }     
    
    public function assign_account_user($parent, $child){
        
        //if exist, update, else add
        $accountUser = $this->_db->fetchRow("select * from account_user where user = '$child'");                
        $data = array('parent_user' => (int)$parent, 'user' => (int)$child);        
       
        if($accountUser && $accountUser['parent_user'] != $parent) {             
            //find the original rep
           $originalRep = self::getSalesRep($child);            
           $change =  $this->_db->query("UPDATE  account_user set parent_user = '$parent', user='$child' WHERE account_user_id = '{$accountUser['account_user_id']}'");          
           $newRep = self::getSalesRep($child);
           
           //send notification to the new assinee
            if ($newRep['parent_user'] != 1) {
                $mail = new Zend_Mail();
                $message = "Dear {$newRep['name']},<br><br>
                        A new contact has been assigned to you. Please click <a href='http://www.beamingwhite.com/biz/crm/customer/id/$child'>here</a> to see the detail.";
                $message .= "<br><br>Thank you, <br><br>
                         Beaming White CRM";
                $mail->setBodyHTML($message);
                $mail->setFrom('no_reply@beamingwhite.com', 'New Contact Assigned');
                $mail->addTo($newRep['email'], $newRep['name']);
                $mail->setSubject("Notification - contact assigned");
                $mail->send();
            }
           $auth = Zend_Auth::getInstance()->getIdentity();
           
           if($auth) {
               $log = array('user_id' => $child, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
               $log['notes'] = 'Change sales rep from '. $originalRep['name']. ' to '. $newRep['name'];           
               self::savenotes($log);
           }
           
        } elseif(!$accountUser) {
           $change =  $this->_db->insert('account_user', $data);           
        }        
        return $change;
    }
      
    
    
    public function editUser(array $data, $id) {
        try {
            if (isset($data['parentAccountID'])) {
                $parentId = $data['parentAccountID'];
                unset($data['parentAccountID']);
                $change = self::assign_account_user($parentId, $id);                
            }            
            $update = $this->_db->update('user', $data, $this->_db->quoteInto("id = ?", $id));  
            
            $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
            $path = $config->toArray();
            $writer = new Zend_Log_Writer_Stream($path['userLogPath'] . date("Ymd") . '.txt');
            $logger = new Zend_Log($writer);   
            $author = '';
            if (Zend_Auth::getInstance()->getIdentity()) {
                $author = Zend_Auth::getInstance()->getIdentity()->firstname.' '.Zend_Auth::getInstance()->getIdentity()->lastname;
            }
            $data['user_id'] = $id;
            $logger->info ($author. '--'.serialize($data));
            
            return $update;
        } catch (Exception $e) {         
            return $e->getMessage();
        }        
        return;        
    }
    public function deleteUser($userId) {     
        
        $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
        $path = $config->toArray();
              
        $user = self::getUser($userId);
        $writer = new Zend_Log_Writer_Stream($path['userDeleteLogPath'] . date("Ymd") . '.txt');
        $logger = new Zend_Log($writer);     
        $logger->info (Zend_Auth::getInstance()->getIdentity()->firstname. '--'.serialize($user));
        //delete user profile from ANet
        $profile = $this->_db->fetchRow("SELECT profile_id FROM user_profile WHERE user_id = '$userId'");
        if ($profile && $profile['profile_id']) {
            $request = new Application_Service_AuthorizeNetCIM;            
            $request->deleteCustomerProfile($profile['profile_id']);            
        }        
        $this->_db->delete('user_profile', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('user_address', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('user_billing', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('user_notes', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('account_conversion', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('account_user', $this->_db->quoteInto("user = ?", $userId));
        $this->_db->delete('user', $this->_db->quoteInto("id = ?", $userId));
        return true;
    }
       
    public function checkPassword($id, $password) {       
                
        $user = $this->_db->fetchRow("select id from user where password = sha1(CONCAT('$password', salt)) and id= $id");
        return $user?$user:FALSE;
    }
    
    //set temparary password
    public function setPasswordByEmail($email, $password) {
        //get new salt
        $data['salt'] = md5($this->_getSalt());
        $data['password'] = sha1($password . $data['salt']);
        
        $update = $this->_db->update('user', $data, $this->_db->quoteInto("email = ?", $email));
        return $update;        
    }
    
       
    
    public function resetPassword($id, $password) {
        //get new salt
        $data['salt'] = md5($this->_getSalt());
        $data['password'] = sha1($password . $data['salt']);
        $update = $this->_db->update('user', $data, $this->_db->quoteInto("id = ?", $id));
        return $update;
    }
    public function password_reset($data) {
        try {             
            $insert =  $this->_db->insert('password_reset', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {              
            return $e->getMessage();
        }
        return; 
    }
    public function check_reset($id) {        
        $reset = $this->_db->fetchRow("SELECT reset from password_reset where user_id = $id order by requested_time desc limit 1");                        
        if(!$reset || ($reset && $reset['reset'] == 1))
            return 1;
        return 0;         
    }
    public function update_reset($userId) {        
        $current = date("Y-m-d H:i:s");
       // echo "Update password_reset set reset = 1, reset_time = '$current'
         //   WHERE user_id = $userId order by requested_time DESC limit 1";
        //die();
        return $this->_db->query("Update password_reset set reset = 1, reset_time = '$current'
            WHERE user_id = $userId order by requested_time DESC limit 1"); 
    }
    
    public function save(array $data) {
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['salt'] = md5($this->_getSalt());
        $data['password'] = sha1($data['password'] . $data['salt']);
       
        try {
            //default BW as the parent account            
            $rep = isset($data['parent_user'])?$data['parent_user']:1;
            unset($data['parent_user']);
            $data['firstname'] = ucwords(strtolower($data['firstname']));
            $data['lastname'] = ucwords(strtolower($data['lastname']));
            $insert = $this->_db->insert('user', $data);     
            $userId = $this->_db->lastInsertId();
            
            self::assign_account_user($rep, $userId);
            //create FM account
           // self::_createFmUser($data);
            //log raw data
            $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
            $path = $config->toArray();
            $writer = new Zend_Log_Writer_Stream($path['userLogPath'] . date("Ymd") . '.txt');
            $logger = new Zend_Log($writer);   
            $author = '';
            if (Zend_Auth::getInstance()->getIdentity()) {
                $author = Zend_Auth::getInstance()->getIdentity()->firstname.' '.Zend_Auth::getInstance()->getIdentity()->lastname;
            }
            $logger->info ($author. '--'.serialize($data));
            
            return $userId;                       
        } catch (Exception $e) {                       
            return $e->getMessage();
        }
        
    }
    
    private function _createFmUser(array $data) {
        $file_user['user'] = $file_user['email'] = $data['email'];
        $file_user['name'] = $data['firstname'].' '.$data['lastname'];
        $file_user['created_by'] = 'web';
        $file_user['active'] = 1;
        $insert = $this->_db->insert('tbl_users', $file_user);   
        if ($lastId = $this->_db->lastInsertId()) {        
            $this->_db->insert('tbl_members', array('added_by' =>'web' , 'client_id' => $lastId, 'group_id'=> 1 ));
        }
    }
    
    public function savenotes($data) {
        try {              
            $insert =  $this->_db->insert('user_notes', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
    public function account_conversion($data) {
        try {              
            $insert = $this->_db->insert('account_conversion', $data);
            
            $mail = new Zend_Mail();
            $message = "This is an automatic email to inform you that an account has been converted. Please 
                    click <a href='http://www.beamingwhite.com/biz/crm/customer/id/{$data['user_id']}'>here</a> to see the account detail. <br><br>";
            $mail->setBodyHTML($message);
            $mail->setFrom('no_reply@beamingwhite.com', 'Beaming White Sales CRM');
            $mail->addTo('dg@beamingwhite.com', 'Darragh');            
            $mail->setSubject('BeamingWhite Account Conversion Notification');
            $mail->send();            
            return $insert;
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
    
    
    public function deleteCard ($id) {
        $data['active'] = 0;        
        return $this->_db->update('user_card', $data, $this->_db->quoteInto("card_id = ?", $id));        
    }
    public function deletePaymentProfile ($id) {
        $data['active'] = 0;
        $data['action_time'] = date("Y-m-d H:i:s");
        return $this->_db->update('user_profile', $data, $this->_db->quoteInto("user_profile_id = ?", $id));        
    }
    
   /* public function saveCard ($data) {          
        try {     
            $check = $this->_db->fetchRow("SELECT card_id FROM user_card WHERE number = '{$data['number']}'");              
            if (!empty($check)) {
                $this->_db->update('user_card', $data, $this->_db->quoteInto("card_id = ?", $check['card_id']));
                return $check['card_id'];
            }
            $insert =  $this->_db->insert('user_card', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return; 
    }*/
    public function saveProfile ($data) {
         $data['action_time'] = date("Y-m-d H:i:s");
         try {     
            $check = $this->_db->fetchRow("SELECT user_profile_id FROM user_profile WHERE payment_profile_id = '{$data['payment_profile_id']}'");              
            if (!empty($check)) {
                $this->_db->update('user_profile', $data, $this->_db->quoteInto("user_profile_id = ?", $check['user_profile_id']));
                return $check['user_profile_id'];
            }
            $insert =  $this->_db->insert('user_profile', $data);
            return $insert;                       
        } catch (Exception $e) {           
            echo $e->getMessage();
            return false;
        }
        return false;
    }
     public function deleteAddress ($id) {
        $data['active'] = 0;        
        return $this->_db->update('user_address', $data, $this->_db->quoteInto("address_id = ?", $id));        
    }
    
    public function saveAddress ($data) {          
        try {        
           
            //$check = $this->_db->fetchRow("SELECT address_id FROM user_address WHERE address_id = '{$data['address_id']}'");       
            //if (!empty($check)) {
            if (isset($data['address_id'])) {
                $this->_db->update('user_address', $data, $this->_db->quoteInto("address_id = ?", $data['address_id']));
                return $data['address_id'];
            }                
            //}
            $insert =  $this->_db->insert('user_address', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {   
           // echo $e->getMessage();
            return $e->getMessage();
        }
        return; 
    }
    
        
    public function saveBillingAddress (array $data, $id) {          
       try {
           $address = $this->_db->fetchRow("SELECT address_id from user_billing WHERE user_id = '$id'");
           if ($address) {
               $update = $this->_db->update('user_billing', $data, $this->_db->quoteInto("user_id = ?", $id));
           } else {
               $data['user_id'] = $id;
               $update =  $this->_db->insert('user_billing', $data);
           }      
           return $update;
        } catch (Exception $e) {
          //  echo $e->getMessage();
           
            return $e->getMessage();
        }        
        return;
    }
      
    
    public function getNotes($userId, $type="note") {        
        return $this->_db->fetchAll("SELECT * FROM user_notes WHERE user_id = '$userId' and type='$type' order by enter_time DESC ");         
    }
    public function getCardType($ccNum) {
        if (preg_match("/^5[1-5][0-9]{14}$/", $ccNum))                
                return "MasterCard";
 
        if (preg_match("/^4[0-9]{12}([0-9]{3})?$/", $ccNum))
                return "Visa";
 
        if (preg_match("/^3[47][0-9]{13}$/", $ccNum))
                return "American Express";
         
        if (preg_match("/^6011[0-9]{12}$/", $ccNum))
                return "Discover";
        
         if (preg_match("/^3(0[0-5]|[68][0-9])[0-9]{11}$/", $ccNum))
                return "Diners Club";
 
        if (preg_match("/^(3[0-9]{4}|2131|1800)[0-9]{11}$/", $ccNum))
                return "JCB";
    }
    
   
    public function getPaymentProfiles($userId) {
        return $this->_db->fetchAll("SELECT * FROM user_profile WHERE user_id = '$userId' AND active = 1");
    }
    public function getPaymentProfile($userId, $paymentProfileId) {
        return $this->_db->fetchRow("SELECT * FROM user_profile WHERE user_id = '$userId' AND payment_profile_id = '$paymentProfileId' AND active = 1");
    }
    public function getPaymentProfileById($user_profile_id) {
        return $this->_db->fetchRow("SELECT * FROM user_profile WHERE user_profile_id = '$user_profile_id'");
    } 
    
    public function getBillingAddress($userId) {
        return $this->_db->fetchRow("SELECT * FROM user_billing WHERE user_id = '$userId' AND active = 1");
    }    
    
    public function getAddresses($userId) {       
        return $this->_db->fetchAll("SELECT * FROM user_address WHERE user_id = '$userId' AND active = 1");
    }
   /* public function getAddressesByEmail($email) {
        return $this->_db->fetchAll("SELECT user_address.* FROM user_address 
        left join user on user.id = user_address.user_id   
        WHERE user_address.active = 1 and user.email = '$email'");
    }*/
    
    public function getAddress($userId, $addressId) {
        $check = $this->_db->fetchRow("SELECT * FROM user_address WHERE user_id = '$userId' AND address_id = '$addressId' ");
        return $check;
    }  
    public function getUserAddress($addressId) {
        return $this->_db->fetchRow("SELECT * FROM user_address WHERE address_id = '$addressId' ");     
    } 
    
    public function getCard($userId, $cardId) {
        $check = $this->_db->fetchRow("SELECT * FROM user_card WHERE user_id = '$userId' AND card_id = '$cardId' ");
        return $check;
    }
    public function getOrders ($userId, $from, $offset) {            
        return $this->_db->fetchAll("SELECT * FROM `order` WHERE user_id = '$userId' AND 
            order_status in ('processing','onhold', 'shipped') ORDER BY date_modified DESC LIMIT $from, $offset");
    }
    public function getOrderTotal($userId) {
        return $this->_db->fetchRow("SELECT count(order_id) as total FROM `order` WHERE user_id = '$userId' AND order_status in ('processing','onhold', 'shipped')");
    }
    
    public static function getLeadSource() {
       /* return array (
            "" => '',
            "Facebook" => "Facebook",
            "Instagram" => "Instagram",     
            "Pinterest" => "Pinterest", 
            "Phone Call" => 'Phone Call',
            "Quickbook" => 'Quickbook', 
            "Registration" => 'Registration',  
            "Tradeshow" => "Trade Show",
            "Twitter"=> "Twitter",            
            "Voice Mail" => 'Voice Mail', 
            "Web Form" => 'Web Form',
            "Website" => 'Website',
            "Youtube" => 'Youtube',
            "Zoho" => 'Zoho',
            "Other" => 'Other'
            );*/
        return array ("Contact Form" => 'Contact Form',
            "Registration" => 'Registration', 
            "Phone Call" => 'Phone Call',
            "Email" => 'Email',
            "Zoho" => 'Zoho',            
            "Other" => 'Other'
            );
    }
    public static function getSource() {        
        return array('Internet'=>'Internet Search',
            'Outside Sales'=>'Outside Sales',
            'Referred'=>'Referred',            
            'Social Media'=>'Social Media',
            'Tradeshow' => 'Trade Show',
            'Other' => 'Other'
            );
    }
    public static function getAccountType() {
        return array (
            "Lead" => 'Lead',
            "Prospect" => 'Prospect',
            "Account" => 'Account'
           );
    }
    public static function getCustomerType() {
        return array (
            "Business" => 'Business',
            "Individual" => 'Individual',            
           );
    }
    public static function getPaymentOptions() {
        return array (
            "card" => 'Credit Card',
            "paypal" => 'Paypal',
            "wire" => 'Wire',
            "wu" => "Western Union",
            "mg" => "MG"            
           );
    }
    public static function getSoldByOptions() {
        return array (          
            'Beaming White' => 'Beaming White',
            'Cliona' => 'Cliona',
            'Cool Smart Product' => 'Cool Smart Product',
            'Magic White' => 'Magic White',
            'Sapient Dental' => 'Sapient Dental',
            'Teeth Whitening Technology' => 'Teeth Whitening Technology'            
           );
    }    
    
    public static function getAccountStatus() {
        return array('Pending'=>'Pending', 'Disabled'=>'Disabled', 'Active'=>'Active');
    }
    public static function getAccountPotential() {        
        return array('Hot'=>'HOT(ready to buy)', 'Warm'=>'Warm (maybe soon)', 'Cool'=>'Cool (future maybe)',
            'Cold'=>'Cold (unlikely)', 'Big'=>'BIG (Min $5,000)', 'Huge'=>'HUGE (Min. $10,000)');
    }
    
    public function getCountries() {
        $countries = $this->_db->fetchAll("SELECT name, iso_code_2 FROM country WHERE status = 1 ORDER BY name");
        return $countries;
    }
    
    public function getRegions($country) {
        $regions = $this->_db->fetchAll("SELECT zone.code, zone.name FROM `zone` left join country on zone.country_id = country.country_id where country.iso_code_2 = '$country' order by zone.name");
      
        if (!$regions) {
            $regions = $this->_db->fetchAll("SELECT zone.code, zone.name FROM `zone` left join country on zone.country_id = country.country_id where country.iso_code_2 = 'US' order by zone.name");
        }        
        return $regions;
    }
    
    public function getCountryName($iso2) {
        return $this->_db->fetchRow("SELECT name from country WHERE iso_code_2  = '$iso2'");
    }
    public function getZoneName($code, $country) {
        return $this->_db->fetchRow("SELECT zone.name FROM zone left join country on zone.country_id = country.country_id where 
            country.iso_code_2 = '$country' AND zone.code='$code'");
    }
        
    public static function gen_password() {                
        $characters = '#@!$%ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvqxyz01234567890';
        $string ='';
               
        for ($p = 0; $p < 8; $p++) {
            $string .= $characters[mt_rand(1, strlen($characters)-1 )];
        }
        return $string;        
    }
    
    
    public static function getBusinessType() {
        return  array('Dentist' => 'Dentist', 'Distributor'=>'Distributor', 'Individual'=>'Individual', 
            'Mobile'=>'Mobile', 'Planet Beach'=> 'Planet Beach', 'Retail'=>'Retail', 'Stationary'=>'Salon/Tanning Salon/Spa/Clinic/Kiosk', 'Other'=>'Other');
    }
    
    public function getEuTraining($userId) {
       return $this->_db->fetchRow("SELECT * from eu_training WHERE user_id = '$userId' AND active = 1");
    }
    public function setEuTraining($userId, $active) {
        $euTraining = $this->_db->fetchRow("SELECT * from eu_training WHERE user_id = '$userId'");
        if ($euTraining){ //is exissting,            
            $set = $this->_db->update('eu_training', array('active'=>$active), $this->_db->quoteInto("id = ?", $euTraining['id']));
        } else {
            $set =  $this->_db->insert('eu_training', array('user_id' => $userId, 'active'=>1));
        }
        return $set;
    }
    public function saveExam($data, $userId) {
        $euTraining = $this->_db->fetchRow("SELECT * from eu_training WHERE user_id = '$userId'");
        if ($euTraining){ //is exissting,            
            $set = $this->_db->update('eu_training', $data, $this->_db->quoteInto("id = ?", $euTraining['id']));
        }        
    }
        
    private static function _getSalt() {
        $salt = '';
        for ($i = 0; $i < 50; $i++) {
            $salt .= chr(rand(33, 126));
        }
        return $salt;
    }
    
    public function getUserFiles($userId, $cat) {        

      $files = $this->_db->fetchAll("select * from files where category = '$cat' AND (userType = 'All'  
          OR userType in (select case when businesstype = 'Dentist' then 'Dentist' else 'Non-Dentist' end 
          filetype from user where id = '$userId')) ORDER BY uploadTime DESC");   
      return $files;
    }
       
    public function saveZohoNotes($data) {
       try {              
            $insert =  $this->_db->insert('zoho_notes', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;   
    }
    public function getZohoNotes() {
        $result = $this->_db->fetchAll("SELECT zoho_notes . * , user.id, user.resale_number
FROM `zoho_notes`
LEFT JOIN user ON user.resale_number = zoho_notes.zoho_id");
        return $result;
    }
    public function fixName($email, $data) {                
       // $update = $this->_db->update('user', $data, $this->_db->quoteInto("email = ?", $email));
        $query = "Update user set firstname = '{$data['firstname']}', lastname='{$data['lastname']}'
            WHERE email = '$email'";
        echo $query.';'.'<br>';
         //return $this->_db->query($query); 
             
    }
    /*for zoho import*/
     public function saveZohoBillingAddress ($data) {          
        try {        
           
            //$check = $this->_db->fetchRow("SELECT address_id FROM user_address WHERE address_id = '{$data['address_id']}'");       
            //if (!empty($check)) {
            if (isset($data['address_id'])) {
                $this->_db->update('user_billing', $data, $this->_db->quoteInto("address_id = ?", $data['address_id']));
                return $data['address_id'];
            }                
            //}
            $insert =  $this->_db->insert('user_billing', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {   
           // echo $e->getMessage();
            return $e->getMessage();
        }
        return; 
    }
}
/*
select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, a.*, b.contactDays, b.enter_time from (select user.id, businessname, contactphone, firstname, lastname, email, created_time, DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, account_user.parent_user, user.potential from user left join account_user on user.id = account_user.user WHERE status != 'Disabled' AND type = 'Lead' AND account_user.parent_user != '10318' ) a left join user u on u.id = a.parent_user left join (SELECT DATE_FORMAT(max(enter_time),'%m/%d/%y   %h:%i %p') as enter_time, DATEDIFF(NOW(), max(enter_time)) AS contactDays, user_id FROM `user_notes` WHERE type = 'attempt' group by user_id) b on b.user_id = a.id ORDER BY created_time DESC LIMIT 0, 30

select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, user.id, user.businessname, user.contactphone, 
        user.firstname, user.lastname, user.email, user.created_time, 
                DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, 
                account_user.parent_user, user.potential 
                from user left join account_user on user.id = account_user.user 
                and user.status != 'Disabled' AND user.type = 'Lead' AND account_user.parent_user != '10318'
        left join user u on u.id = account_user.parent_user 
        left join (SELECT DATE_FORMAT(max(enter_time),'%m/%d/%y   %h:%i %p') as enter_time, 
                DATEDIFF(NOW(), max(enter_time)) AS contactDays, user_id 
                FROM `user_notes` WHERE type = 'attempt' group by user_id) b on b.user_id = user.id 
        WHERE WHERE CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >= 91
                    ELSE contactDays >=91 END ORDER BY created_time DESC LIMIT 0, 30
 * 
 * */
  
