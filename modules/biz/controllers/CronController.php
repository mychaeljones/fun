<?php

class CronController extends Zend_Controller_Action {

    public function init() {
        $this->_users = new Application_Model_User;   
        $this->_crms = new Application_Model_Crm;
        $this->_db = Zend_Registry::get('db');
       /* if (!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
           $this->_helper->redirector('login', 'user');
        }
           
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        $this->_crms = new Application_Model_Crm;  
        $this->_products = new Application_Model_Product;*/
    }
    //3 day attempt
    public function attemptAction() {        
        
        $sales = $this->_users->getSalesUsers(); 
        foreach ($sales as $rep) {
            $mail = new Zend_Mail();
            if ($rep['id'] == 1) {
                continue;
            }
            $message = "Dear {$rep['name']},<br><br>
                    This is an automatic email to remind you that it has been more than 3 days since your last attempt with the follwing contacts: <br><br>"; 
            $message .= '<table width="65%" style="collapse: collapse; text-align:left;background-color:#FFFCFE;">
                <tr><th>Name</th><th>Email</th><th>Business Name</th><th>Contact Phone</th><th>Last Attempt Time</th><th># of days since Last Contact</th></tr>';
            $results = $this->_db->fetchAll("SELECT a.*, DATEDIFF(NOW(), latest) AS contactDays
            from (SELECT user_id, max(enter_time) as latest, concat(user.firstname, ' ', user.lastname) as name, user.email, user.businessname, user.contactphone, user.type, user_notes.notes as contactType FROM `user_notes` 
            left join user on user.id = user_notes.user_id
            LEFT JOIN account_user au ON au.user = user_notes.user_id 
            WHERE user_notes.type = 'attempt' and (user.type = 'Lead' || user.type='Prospect')
            and au.parent_user = '{$rep['id']}' and user.status != 'Disabled'                
            GROUP BY user_notes.user_id order by user_notes.user_id, enter_time) a
            WHERE CASE WHEN DAYOFWEEK(latest) = 1 or DAYOFWEEK(latest) = 2 or DAYOFWEEK(latest) = 3 
                            THEN DATE(latest) < DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                       WHEN DAYOFWEEK(latest) = 4 or DAYOFWEEK(latest) = 5 or DAYOFWEEK(latest) = 6                        
                            THEN DATE(latest) < DATE_SUB(CURDATE(), INTERVAL 5 DAY)
                       WHEN DAYOFWEEK(latest) = 7                       
                            THEN DATE(latest) < DATE_SUB(CURDATE(), INTERVAL 4 DAY)
                      ELSE DATE(latest) < DATE_SUB(CURDATE(), INTERVAL 4 DAY) END 
                      ORDER BY latest DESC");                    
         
            if ($results) {
                 foreach ($results as $data) {                     
                     $message .="<tr><td><a href='http://www.beamingwhite.mx/crm/customer/id/{$data['user_id']}'>{$data['name']}</a></td>
                         <td>{$data['email']}</td>
                             <td>{$data['businessname']}</td>
                            <td>{$data['contactphone']}</td>
                                <td>{$data['latest']}</td>                                
                                <td>{$data['contactDays']} Days</td>
                                </tr>";
                 }  
                 $message .= "</table><br><br>Thank you, <br><br>
                     Beaming White CRM";
           
                 $mail->setBodyHTML($message);
                 $mail->setFrom('no_reply@beamingwhite.com', 'Beaming White Sales CRM');
                 $mail->addTo($rep['email'], $rep['name']);
                 if ($rep['id'] == 2 || $rep['id'] == 3 || $rep['id'] == 5 || $rep['id'] == 13 || $rep['id'] == 11152 || $rep['id'] == 12661) {
                    $mail->addCc('crmmanagement@beamingwhite.com', 'CRM Management');
                 }
                 $mail->setSubject("Reminder - Attempt follow up");                
                 $mail->send();
                 echo date("Y-m-d H:i:s").": Email Sent to {$rep['email']}\n\r";
            }   
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);      
    }
   //Account three follow up. 
   public function followupAction() {
       
       $sales = $this->_users->getSalesUsers();                   
       
       $three = array ('DAY','WEEK','MONTH');             
       foreach ($three as $freq) {
           
        
        foreach ($sales as $rep) {
            if ($rep['id'] == 1) {
                continue;
            }
            $mail = new Zend_Mail();
            $message = "Dear {$rep['name']},<br><br>
                    This is an automatic email to remind you the follwing accounts are up for 3 $freq follow up: <br><br>"; 
            $message .= '<table width="60%" style="collapse: collapse; text-align:left;background-color:#FFFCFE;">
                <tr><th>Name</th><th>Email</th><th>Business Name</th><th>Contact Phone</th></tr>';
                              
            if ($freq == 'DAY') {
                $query = "SELECT ac.user_id, user.firstname, user.lastname, user.email, user.businessname, user.contactphone,
                        uu.email as repEmail, concat(uu.firstname, ' ', uu.lastname) as rep, ac.action_time
                        FROM `account_conversion` ac
                        LEFT JOIN user ON user.id = ac.user_id 
                        LEFT JOIN account_user au ON au.user = ac.user_id
                        LEFT JOIN user uu ON uu.id = au.parent_user
                        where             

                        CASE WHEN DAYOFWEEK(action_time) = 1 or DAYOFWEEK(action_time) = 2 or DAYOFWEEK(action_time) = 3          
                                        THEN DATE(action_time) = DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                                   WHEN DAYOFWEEK(action_time) = 4 or DAYOFWEEK(action_time) = 5 or DAYOFWEEK(action_time) = 6 
                                        THEN DATE(action_time) = DATE_SUB(CURDATE(), INTERVAL 5 DAY)
                                   WHEN DAYOFWEEK(action_time) = 7 
                                        THEN DATE(action_time) = DATE_SUB(CURDATE(), INTERVAL 4 DAY)     
                                  ELSE DATE(action_time) = DATE_SUB(CURDATE(), INTERVAL 4 DAY) END 

                        AND au.parent_user = '{$rep['id']}'
                        AND user.status != 'Disabled'  
                        ORDER BY action_time ASC";
            } else {
                 $query = "SELECT ac.user_id, user.firstname, user.lastname, user.email, user.businessname, user.contactphone,
                uu.email as repEmail, concat(uu.firstname, ' ', uu.lastname) as rep
                FROM `account_conversion` ac
                LEFT JOIN user ON user.id = ac.user_id 
                LEFT JOIN account_user au ON au.user = ac.user_id
                LEFT JOIN user uu ON uu.id = au.parent_user
                where DATE(action_time) = DATE_SUB(CURDATE(), INTERVAL 3 $freq) AND au.parent_user = '{$rep['id']}'
                AND user.status != 'Disabled'  
                ORDER BY action_time ASC"; 
            }
            
            $results = $this->_db->fetchAll($query);
         
            if ($results) {
                 foreach ($results as $data) {
                     $name = $data['firstname']. ' '. $data['lastname'];
                     $message .="<tr><td><a href='http://www.beamingwhite.mx/crm/customer/id/{$data['user_id']}'>$name</a></td>
                         <td>{$data['email']}</td>
                             <td>{$data['businessname']}</td>
                            <td>{$data['contactphone']}</td></tr>";
                 }  
                 $message .= "</table><br><br>Thank you, <br><br>
                     Beaming White CRM";
               //  echo $message;
                 $mail->setBodyHTML($message);
                 $mail->setFrom('no_reply@beamingwhite.com', 'Beaming White Sales CRM');
                 $mail->addTo($rep['email'], $rep['name']);
                 if ($rep['id'] == 2 || $rep['id'] == 3 || $rep['id'] == 5 || $rep['id'] == 13 || $rep['id'] == 11152 || $rep['id'] == 12661) {
                    $mail->addCc('crmmanagement@beamingwhite.com', 'CRM Management');
                 }                 
                // $mail->addTo('jing@beamingwhite.com', $rep['name']);
                 $mail->setSubject("Reminder - Account 3 $freq follow up");                
                 $mail->send();
                 echo date("Y-m-d H:i:s").": Email Sent to {$rep['email']}\n\r";
            }        

        }
       }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);       
   } 
   public function newAssignAction() {
       $this->_helper->layout()->disableLayout();     
       $this->_helper->viewRenderer->setNoRender(TRUE);       
       $sales = $this->_users->getSalesUsers();      
       foreach ($sales as $rep) {
           
            if ($rep['id'] == 1) {
                continue;
            }
            $mail = new Zend_Mail();
            $message = "Dear {$rep['name']},<br><br>
                    This is an automatic email to remind you that your recent assigned leads need attempt action: <br><br>"; 
            $message .= '<table width="60%" style="collapse: collapse; text-align:left;background-color:#FFFCFE;">
                <tr><th>Name</th><th>Email</th><th>Business Name</th><th>Contact Phone</th></tr>';
            
            
            $query = "SELECT au.user, au.parent_user, 
                au.assign_time, concat(user.firstname, ' ', user.lastname) as name,  user.email, user.businessname, user.contactphone,
                             uu.email as repEmail, concat(uu.firstname, ' ', uu.lastname) as rep                         
             FROM account_user au
             LEFT JOIN user ON user.id = au.user   
             LEFT JOIN user_notes ON user_notes.user_id = au.user and user_notes.type = 'attempt'
             LEFT JOIN user uu ON uu.id = au.parent_user
             where 
               CASE WHEN DAYOFWEEK(assign_time) = 1 or DAYOFWEEK(assign_time) = 2 or DAYOFWEEK(assign_time) = 3 or DAYOFWEEK(assign_time) = 4         
                                             THEN DATE(assign_time) = DATE_SUB(CURDATE(), INTERVAL 2 DAY)
                                        WHEN DAYOFWEEK(assign_time) = 5 or DAYOFWEEK(assign_time) = 6 
                                             THEN DATE(assign_time) = DATE_SUB(CURDATE(), INTERVAL 4 DAY)
                                        WHEN DAYOFWEEK(assign_time) = 7 
                                             THEN DATE(assign_time) = DATE_SUB(CURDATE(), INTERVAL 3 DAY)     
                                        ELSE DATE(assign_time) = DATE_SUB(CURDATE(), INTERVAL 3 DAY) END       
             AND au.parent_user !=1 AND au.parent_user = '{$rep['id']}'
             AND (user.type = 'Lead' || user.type='Prospect') AND user_notes.user_id is NULL AND user.status != 'Disabled'  
             ORDER BY assign_time ASC";
           
             $results = $this->_db->fetchAll($query);
             if ($results) {
                 foreach ($results as $data) {
                  
                     $message .="<tr><td><a href='http://www.beamingwhite.mx/crm/customer/id/{$data['user']}'>{$data['name']}</a></td>
                         <td>{$data['email']}</td>
                             <td>{$data['businessname']}</td>
                            <td>{$data['contactphone']}</td></tr>";
                 }  
                 $message .= "</table><br><br>Thank you, <br><br>
                     Beaming White CRM";               
                 $mail->setBodyHTML($message);
                 $mail->setFrom('no_reply@beamingwhite.com', 'Beaming White Sales CRM');
                 $mail->addTo($rep['email'], $rep['name']);
                 if ($rep['id'] == 2 || $rep['id'] == 3 || $rep['id'] == 5 || $rep['id'] == 13 || $rep['id'] == 11152 || $rep['id'] == 12661 ) {
                    $mail->addCc('crmmanagement@beamingwhite.com', 'CRM Management');
                 }
                 
                 //$mail->addTo('jing@beamingwhite.com', $rep['name']);
                 $mail->setSubject("Reminder - Newly Assigned Leads");                
                 $mail->send();
                 echo date("Y-m-d H:i:s").": Email Sent to {$rep['email']}\n\r";
            }
       }
   }
   
   public function eventNotificationAction() {
       $this->_helper->layout()->disableLayout();     
       $this->_helper->viewRenderer->setNoRender(TRUE);
       $query = "SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title, customer_id, user_id, user.email
            FROM events 
            left join user on user.id = events.user_id
            WHERE events.active = 1 AND
            events.start BETWEEN (NOW() - INTERVAL 12 HOUR) AND (NOW() + INTERVAL 12 MINUTE)
            AND events.email_alert IS NULL
            ORDER BY events.start ASC, events.event_id";
        $results = $this->_db->fetchAll($query);
        
        foreach ($results as $event) {
         
            $mail = new Zend_Mail();
            $user = $this->_users->getUser($event['customer_id']);
            $message = 'When: ' . date('m/d/y g:i A', strtotime($event['start'])) . '<br>';
            $message .= 'Follow-up with: ' . "<a href='http://beamingwhite.mx/crm/customer/id/{$event['customer_id']}'>" . $user['firstname'] . ' ' . $user['lastname'] . '</a>';
            
            $mail->setBodyHTML($message);
            $mail->setFrom('crmmanagement@beamingwhite.com', 'Beaming White Sales CRM');
            $mail->addTo($event['email']);
            
         //   $mail->addBcc('jing@beamingwhite.com');
            $mail->setSubject($event['title']. ' @ '. date('m/d/y g:i A', strtotime($event['start'])));
            $mail->send();
            echo date("Y-m-d H:i:s").": Email Sent to {$event['email']}\n\r";
            $update = $this->_crms->updateEvent(array('email_alert' => date('Y-m-d H:i:s')), $event['id'], 'followup');        
           
        }
   }
              
}
