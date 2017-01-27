<?php

class Application_Model_Report{
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
    }
   
    public function account_conversion($from, $to, $parent_id = '') {
        $rep = '';
        if ($parent_id) {
            $rep = " AND parent_id = '$parent_id'";
        }
        $conversion = $this->_db->fetchAll("SELECT source, count(distinct user_id) as totalAccount FROM `account_conversion`  
            WHERE action_time >= '$from 00:00:00' AND action_time <= '$to 23:59:59' $rep group by source");
        return $conversion;
    }
    
     public function conversion_accounts($from, $to, $parent_id = '') {
        $rep = '';
        if ($parent_id) {
            $rep = " AND parent_id = '$parent_id'";
        }
        $accounts = $this->_db->fetchAll("SELECT account_conversion.user_type, account_conversion.source, lead_source, action_time, u.email,u.created_time, u.businessname,concat(u.firstname, ' ', u.lastname) as name,
            concat(uu.firstname, ' ', uu.lastname) as rep
            FROM `account_conversion`  
            left join user u on u.id = account_conversion.user_id 
            left join user uu on uu.id = account_conversion.parent_id 
            WHERE action_time >= '$from 00:00:00' AND action_time <= '$to 23:59:59' $rep order by lead_source, action_time, rep");
        return $accounts;
    }
    
    public function phone_conversion($from, $to, $parent_id = '') {
        $rep = '';
        if ($parent_id) {
            $rep = " AND parent_user = '$parent_id'";
        }
        
        $conversion = $this->_db->fetchAll("SELECT count(id) as totalContacts FROM `user`
            left join account_user on user.id = account_user.user 
            WHERE created_time >= '$from 00:00:00' AND created_time <= '$to 23:59:59' and imported = 'Phone Call' 
            AND status != 'Disabled' $rep 
            union 
            SELECT count(id) as totalAccounts FROM `user`  
            left join account_user on user.id = account_user.user
            WHERE created_time >= '$from 00:00:00' AND created_time <= '$to 23:59:59' and imported = 'Phone Call' 
            AND status != 'Disabled' $rep AND type = 'Account'");
        
        return $conversion;
    }
    public function phone_accounts($from, $to, $parent_id = '') {
        $rep = '';
        if ($parent_id) {
            $rep = " AND au.parent_user = '$parent_id'";
        }
        $accounts = $this->_db->fetchAll("select a.*,  concat(uu.firstname, ' ', uu.lastname) as rep from 
            (SELECT u.type, u.id, action_time, u.email,u.created_time, u.businessname,concat(u.firstname, ' ', u.lastname) as name, au.parent_user       
            FROM `user` u
            left join account_conversion ac on u.id = ac.user_id 
            left join account_user au on u.id = au.user 
            
            WHERE u.created_time >= '$from 00:00:00' AND u.created_time <= '$to 23:59:59' 
            AND imported='Phone Call' AND u.status != 'Disabled' $rep order by u.created_time, action_time) a
            left join user uu on uu.id = a.parent_user");
        return $accounts;
    }
    public function user_source($from, $to) {     
        return $this->_db->fetchAll("SELECT count(id) as count, source_text  from user 
            where imported = 'Contact Form' and source_text like '%business%' and 
            created_time >= '$from 00:00:00' AND created_time <= '$to 23:59:59'  group by source_text 
            order by count desc");        
    }
    public function total_contacts($from, $to) {
        $result = $this->_db->fetchRow("SELECT count(id) as total from user 
            where created_time >= '$from 00:00:00' AND created_time <= '$to 23:59:59' AND type !='Internal'");
        return $result?$result['total']:0;
    }
    public function response_time($from, $to,  $parent_id = '', $status) {
        $rep = '';
        if ($parent_id) {
            $rep = " AND au.parent_user = '$parent_id'";
        }
        
        $notResponded = $responded = '';
        if($status =='notResponded') {
            $join = 'LEFT JOIN';
            $notResponded = ' b.user_id is NULL AND';
        } else {
            $join = 'JOIN';
            $responded = 'WHERE firstContact > assignTime';
        }
        
       /* return $this->_db->fetchAll("SELECT  a.*, TIMESTAMPDIFF(HOUR,created_time,firstContact) AS responseTime 
            FROM (SELECT user_id, created_time, MIN(enter_time) AS firstContact, 
            case when businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE businessname end businessname, 
            email, concat(user.firstname, ' ', user.lastname) as name FROM user_notes JOIN user ON user_notes.user_id = user.id  
            WHERE  user_notes.author != 'Request Info' AND user.role = 'customer' AND user.status != 'Disabled' AND 
            user.created_time > '$from 00:00:00' AND user.created_time <= '$to 23:59:59' GROUP BY user_notes.user_id ) as a");*/
        
        /*select a.user_id, b.firstContact from user join 
 (select user_id, enter_time from user_notes where notes like 'Change sales rep from%' group by user_id) a on a.user_id = user.id
left join (select user_id, min(enter_time) as firstContact from user_notes where notes not like 'Change sales rep from%' and author != 'Request Info' group by user_id) b on user.id = b.user_id
where user.role = 'customer' AND user.status != 'Disabled' and b.user_id is null
*/
        $query = "select c.*, TIMESTAMPDIFF(HOUR,assignTime,firstContact) AS responseTime from 
            (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where notes like 'Change sales rep from%' group by user_id) 
            as a on user.id = a.user_id 
            $join (select user_id, min(enter_time) as firstContact from user_notes where notes not like 'Change sales rep from%' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' AND $notResponded
            user.created_time > '$from 00:00:00' AND user.created_time <= '$to 23:59:59' $rep ) c $responded ORDER BY assignTime";
       // echo $query;
        return $this->_db->fetchAll($query);
        
    }
    public function avg_response_time($from, $to, $parent_id = '') {
        /*return $this->_db->fetchRow("SELECT count(user_id) AS total, AVG(TIMESTAMPDIFF(HOUR,created_time,firstContact)) AS avgResponseTime 
            FROM (SELECT user_id, created_time, MIN(enter_time) AS firstContact, businessname, email, concat(user.firstname, ' ', user.lastname) as name FROM user_notes JOIN user ON user_notes.user_id = user.id  
            WHERE  user_notes.author != 'Request Info' AND user.role = 'customer' AND user.status != 'Disabled' AND 
            user.created_time > '$from 00:00:00' AND user.created_time <= '$to 23:59:59' GROUP BY user_notes.user_id ) as a");*/
         $rep = '';
         if ($parent_id) {
             $rep = " AND au.parent_user = '$parent_id'";
         }
         return $this->_db->fetchRow("select count(id) as total, AVG(TIMESTAMPDIFF(HOUR,assignTime,firstContact)) AS avgResponseTime  from (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where notes like 'Change sales rep from%' group by user_id) 
            as a on user.id = a.user_id 
            join (select user_id, min(enter_time) as firstContact from user_notes where notes not like 'Change sales rep from%' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' AND 
            user.created_time > '$from 00:00:00' AND user.created_time <= '$to 23:59:59' $rep) c WHERE firstContact > assignTime");
    } 
    
    public function accounts($data) {
        $groupBy = $data['groupBy'];        
        
        $filter = '';
        if($groupBy == 'state')
            $filter .= "AND country = 'US'";
        
        if($data['rep']) {
            $filter .= "AND parent_user = '{$data['rep']}'";
        }
        foreach ($data as $key=>$value) {
            if(in_array($key, array('type', 'businesstype', 'soldBy', 'source', 'imported')) && $value) {
                $filter .= " AND $key = '$value' ";
            }
        }
       
        $query = "SELECT count(id) as count, $groupBy FROM user 
            LEFT JOIN account_user ON account_user.user = user.id 
            WHERE user.role = 'customer' AND user.status != 'Disabled' AND 
            user.created_time > '{$data['from']} 00:00:00' AND user.created_time <= '{$data['to']} 23:59:59' 
            $filter
            GROUP BY $groupBy ORDER BY $groupBy";
                   
        return $this->_db->fetchAll($query);
    }
}
