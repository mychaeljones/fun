<?php

class Application_Model_Crm{
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
    }
    
   public function createEvent($data) {
        try {              
            $insert =  $this->_db->insert('events', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
   } 
   
   public function getEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1) ORDER BY start ASC, event_id");
       return $events;
   }
   
   public function getEvent($eventId) {
       return $this->_db->fetchRow("SELECT * from events WHERE event_id = '$eventId'");       
   }
   public function getFollowup($customerId) {
       return $this->_db->fetchRow("SELECT * from events WHERE customer_id = '$customerId'");  
   }   
   public function getUpcomingEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND ADDDATE(start, INTERVAL 30 DAY) AND (user_id = '$userId' OR public = 1)
            AND events.start >= DATE(NOW()) ORDER BY start ASC, event_id");
       return $events;
   }
   public function getTodayEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end,customer_id, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1)
            AND DATE(start) = DATE(NOW()) ORDER BY start ASC, event_id");
       return $events;
   }
   //set 10 mins ahead of time, +/-2 mins variation
   public function getAlertEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND
            start BETWEEN (NOW() - INTERVAL 12 HOUR) AND (NOW() + INTERVAL 12 MINUTE)
            AND (user_id = '$userId' OR public = 1) AND popup_alert IS NULL
            ORDER BY start ASC, event_id");
       return $events;
   }
   
   public function updateEvent(array $data, $eventId, $followUp = NULL) {
        try {                 
         
           //check public value                      
           if (!$followUp) {
               $row = $this->_db->fetchRow("SELECT event_id from events WHERE user_id = '{$data['user_id']}' AND event_id = $eventId");          
               if(!$row) {
                    return 'Permission Error';
               }
           }
           $update = $this->_db->update('events', $data, $this->_db->quoteInto("event_id = ?", $eventId));                      
          
           return $update;
           
           
        } catch (Exception $e) {           
            return $e->getMessage();
        }        
        return;        
    }
    //delete followup
    public function deleteFollowup($customerId) {
        return $this->_db->delete('events', $this->_db->quoteInto("customer_id = ?", $customerId));
    }
    
    public function fileCategories()
    {
        return array('business' => 'Business Documents',            
            'graphics'=> 'Graphics',
            'marketing' => 'Marketing Material',
            'product' => 'Product Information'            
            );
    }
    
    public function saveFiles($data) 
    {
        try {              
            $insert =  $this->_db->insert('files', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
    public function findUsers($category, $value)
    {     
     

     if ($category == 'all') {        
        $term = addslashes($value);
            $query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type, status, account_user.parent_user, MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) AGAINST ('" .
                       '"' . $term . '"' . "' IN BOOLEAN MODE) AS score
            from user left join account_user on user.id = account_user.user
            WHERE type != 'Internal' AND MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) AGAINST('" .
                       '"' . $term . '"' . "' IN BOOLEAN MODE) ) a  
            left join user u on u.id = a.parent_user ORDER BY score DESC LIMIT 0,100";
            $result = $this->_db->fetchAll($query);
            if ($result) {
                return $result;
            }
            $parts = preg_split('/\s+/', $value);
            foreach ($parts as $part) {
                $term .= '+' . $part . ' ';
            }
            $query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type, status, account_user.parent_user, MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) 
            AGAINST ('$term' IN BOOLEAN MODE) AS score
            from user left join account_user on user.id = account_user.user
            WHERE type != 'Internal' AND MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) 
            AGAINST('$term' IN BOOLEAN MODE) ) a  
            left join user u on u.id = a.parent_user ORDER BY score DESC LIMIT 0,100";
            return $this->_db->fetchAll($query);
     } else { 
           if ($category == 'fullname') {
           $name = preg_split('/\s+/', $value, 2);                      
           $firstName = $this->_db->quote("%$name[0]%");
               if (isset($name[1])) {
                   $lastName = $this->_db->quote("%$name[1]%");
               }
           } else {           
               $value = $this->_db->quote("%$value%");
           }
            if ($category == 'email') {
                $filter = "email like $value OR email2 like $value";
            } else if ($category == 'businessname') {
                $filter = "businessname like $value";
            } else if ($category == 'firstname') {
                $filter = "firstname like $value";
            } else if ($category == 'lastname') {
                $filter = "lastname like $value";
            } else if ($category == 'contactphone') {
                $filter = "contactphone like $value OR contactphone2 like $value";
            } else if ($category == 'fullname') {               
                $filter = "firstname like $firstName";
                if (isset($lastName)) {
                    $filter .= " AND lastName like $lastName";
                }
            }
            $query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE type != 'Internal' AND ($filter) ) a  
            left join user u on u.id = a.parent_user ORDER BY businessname ASC LIMIT 0,100";
        }
        

     $result = $this->_db->fetchAll($query);
     return $result;
    }            
}
