<?php

class Application_Model_Order {
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
    }

    public function save(array $data)
    {        
        try {          
            $this->_db->insert('order', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;     
    }
  
    public function update(array $data, $order_id)
    {
       try {
           $update = $this->_db->update('order', $data, $this->_db->quoteInto("order_id = ?", $order_id));
           return $update;
       } catch (Exception $e) {           
            return $e->getMessage();
       }
        return;
    }
    
    public function save_internal_order($data, $userId)
    {
               
        $order = $this->_db->fetchRow("SELECT `order`.order_id FROM `order` 
            WHERE `order`. user_id = '$userId' AND order_status = 'incomplete' AND submitted_by IS NOT NULL");
              
        //already there, update
        if ($order) {
            $result = self::update($data, $order['order_id']);
            return $result?$order['order_id']:FALSE;
        } else {
            $result = self::save($data);
            return $result;
        }
        return FALSE;
    }
    public function save_shiprates(array $data)
    {        
        try {          
            $this->_db->insert('order_shiprates', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;     
    }
    public function get_order_shiprates($orderId) {
        $rates = $this->_db->fetchRow("SELECT shipping_rates FROM order_shiprates WHERE order_id = '$orderId' ORDER BY requested_time DESC LIMIT 1");       
        return unserialize($rates['shipping_rates']);
    }
            
    
    public function update_order_item(array $data, $order_items_id)
    {
       try {
           $update = $this->_db->update('order_items', $data, $this->_db->quoteInto("order_items_id = ?", $order_items_id));
           return $update;
       } catch (Exception $e) {           
            return $e->getMessage();
       }
        return;
    }
    
    public function set_shipped_quantity($order_id) {        
       try {
           $update = $this->_db->query("update order_items set shipped_quantity = quantity where order_id = '$order_id'");
           return $update;
       } catch (Exception $e) {           
            return $e->getMessage();
       }
       return;
    }
    
    //check if the user cart is empty
    public function check_cart($userId)
    {
      $order = $this->_db->fetchRow("SELECT order_id from `order` WHERE user_id = '$userId' AND order_status = 'incomplete'");
      return $order?$order['order_id']:FALSE;
    }
    //check if user cart is empty for internal order
    public function check_order($userId, $repId)
    {
      $order = $this->_db->fetchRow("SELECT order_id from `order` WHERE user_id = '$userId' AND submitted_by is NOT NULL  
          AND order_status = 'incomplete'");
      return $order?$order['order_id']:FALSE;
    }
    
    //get incomplete order in the customer shopping cart
    public function get_cart($userId)
    {
        return $this->_db->fetchRow("SELECT `order`.*, user_profile.type, user_profile.month, user_profile.year FROM `order` left join user_profile on user_profile.payment_profile_id = order.payment_profile_id 
            WHERE `order`. user_id = '$userId' AND order_status = 'incomplete' AND submitted_by IS NULL");
    }
    
    //get incomplete order placing by rep
    public function get_rep_order($userId)
    {
        return $this->_db->fetchRow("SELECT `order`.*, user_profile.type, user_profile.month, user_profile.year FROM 
            `order` left join user_profile on user_profile.payment_profile_id = order.payment_profile_id 
            WHERE `order`. user_id = '$userId' AND order_status = 'incomplete' AND submitted_by IS NOT NULL");
    }
    
    //get order 
    public function get_order($orderId)
    {
        return $this->_db->fetchRow("SELECT * FROM `order` WHERE order_id = '$orderId'");
    }

    /**
     * 
     * @param type $orderId     
     * @return type used when order load their cart, price is current
     */
    public function get_order_items($orderId)
    {
        $items = $this->_db->fetchAll("SELECT oi.quantity as qty, oi.product_id as productId,oi.order_items_id, oi.shipped_quantity, pp.price * oi.quantity AS lineCost, 
            pp.price, p.name, p.volumn, p.weight 
            from order_items oi
            LEFT JOIN `order` ON order.order_id = oi.order_id
            LEFT JOIN product_price pp ON pp.product_id = oi.product_id AND pp.user_id = `order`.user_id AND oi.quantity >= pp.min AND oi.quantity <= pp.max AND pp.active = 1
            LEFT JOIN product p ON p.product_id = oi.product_id            
            WHERE oi.order_id = '$orderId'                 
                ");
        $data['subTotal'] = 0;
        foreach ($items as $item) {
            //only return those that has price, so to remove those that don't weren't offered
            if ($item['price']) {
                 $data[$item["productId"]] = (object)$item;
                $data['subTotal'] += $item['lineCost'];
            }           
        }
      
        return $data;
    }
    
    /**
     * 
     * @param type $orderId
     * @param type $userId
     * @return type used when order load their cart, price is at the time when it's ordered
     */
    public function get_invoice_items($orderId)
    {
        $items = $this->_db->fetchAll("SELECT oi.quantity as qty, oi.product_id as productId,oi.order_items_id, oi.shipped_quantity, 
            oi.price * oi.quantity AS lineCost, 
            oi.price, p.name, p.volumn, p.weight, `order`.*
            from order_items oi            
            LEFT JOIN `order` ON order.order_id = oi.order_id
            LEFT JOIN product p ON p.product_id = oi.product_id
            
            WHERE oi.order_id = '$orderId'                 
                ");
        $data['subTotal'] = 0;
        foreach ($items as $item) {
            $data[$item["productId"]] = (object)$item;
            $data['subTotal'] += $item['lineCost'];
            //write the options in
            $options = $this->_db->fetchAll("SELECT order_option.order_product_id, order_option.option_description, product_option_value_id, order_option.quantity
                    FROM order_option 
                    LEFT JOIN `order_items` oi ON oi.order_id = order_option.order_id AND oi.product_id = order_option.order_product_id
                    WHERE oi.order_id = '$orderId' AND oi.product_id = '{$item['productId']}'");           
            if ($options) {
                    $data[$item["productId"]]->options = $options;
            }
        }
        return $data;
    }
    
    
    public function get_order_item($order_items_id)
    {
        return $this->_db->fetchRow("SELECT * FROM order_items WHERE order_items_id = '$order_items_id'");
    }            
    
    
    //save item
    public function save_item($data)
    {
        try {          
            $this->_db->insert('order_items', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return; 
    }
    
    //save order item options
    public function save_order_option($data)
    {
        try {          
            $this->_db->insert('order_option', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return; 
    }
    
    
    /*public function getProductByName($name)  {
        return $this->_db->fetchRow("SELECT * FROM product WHERE name = '$name' ");        
    }*/
    
    public function getAddressById ($id) {
        return $this->_db->fetchRow("SELECT * from user_address WHERE address_id = '$id'");
    }
    
    
    //calculate shipping needed meta information
    public function calculate_shipping_info($items)
    {
        $totalVolumn = 0; $totalWeight = 0;
        foreach ($items as $item) {
            if(is_object($item)){
                $totalVolumn += $item->volumn * $item->qty;
                $totalWeight += $item->weight * $item->qty;
            }
        }
           
        //calculate what box to use
        $box = $this->_db->fetchRow("SELECT * FROM box WHERE volumn > $totalVolumn order by volumn limit 1");       
        //one box fits all 
        if ($box) {
            $result[0]['Length'] = $box['Length'];
            $result[0]['Width']  = $box['Width'];
            $result[0]['Height'] = $box['Height'];
             //oz to pounds
            $result[0]['Weight'] = round($totalWeight * 0.0625);
            if ($result[0]['Weight'] == 0) {
                $result[0]['Weight'] = 1;
            }
            return $result;
        }
        $boxes = $this->_db->fetchRow("SELECT $totalVolumn DIV max(volumn) AS boxes, $totalVolumn % max(volumn) AS remainVolumn, dimension, Length, Width, Height, volumn 
            FROM `box` group by volumn order by volumn desc limit 1");
       
        for($i = 0; $i <= (int)$boxes['boxes'] - 1; ++$i) {
            $result[$i]['Length'] = $boxes['Length'];
            $result[$i]['Width'] = $boxes['Width'];
            $result[$i]['Height'] = $boxes['Height'];
           // $result[$i]['Weight'] = round($totalWeight * 0.0625 /(int)$boxes['boxes']);
            //http://www.ups.com/content/us/en/resources/ship/packaging/dim_weight.html
            //$result[$i]['Weight'] = round($boxes['volumn']/166);
            $result[$i]['Weight'] = round(($totalWeight/$totalVolumn) * $boxes['volumn'] *0.0625);
        }
        if ($boxes['remainVolumn'] > 0) {
           $box = $this->_db->fetchRow("SELECT * FROM box WHERE volumn > {$boxes['remainVolumn']} order by volumn limit 1");
           $result[$i]['Length'] = $box['Length'];
           $result[$i]['Width'] = $box['Width'];
           $result[$i]['Height'] = $box['Height'];
             //oz to pounds
           $result[$i]['Weight'] = round(($totalWeight/$totalVolumn) * $box['volumn'] *0.0625);
           if ($result[$i]['Weight'] == 0) {
               $result[$i]['Weight'] = 1;
           }
        }
        return $result;
                
    }
    
    //
    public function get_wa_taxrate($data) {
        $address = urlencode(trim($data['address1']) . ' ' . trim($data['address2']));
        $city = urlencode($data['city']);
        $zip = urlencode($data['zip']);
        $post_url = "http://dor.wa.gov/AddressRates.aspx?output=xml&addr=$address&city=$city&zip=$zip";
        //echo $post_url;
        if ($handle = curl_init($post_url)) {
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($handle, CURLOPT_HEADER, 0);
            curl_setopt($handle, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($handle, CURLOPT_FRESH_CONNECT, 1);
            if ($rate_data = curl_exec($handle)) {
                curl_close($handle);
                $taxObj = simplexml_load_string($rate_data);

                return $taxObj;
            }
        }
        return FALSE;
    }
    
    public function getOrders($status) {
        $result = $this->_db->fetchAll("SELECT * from `order` WHERE order_status = '$status' AND active = 1 order by date_modified ASC");
        return $result;
    }
    public function getUnshippedOrders ($sort, $order, $start, $end) {
                
        $result = $this->_db->fetchAll("SELECT * from `order` WHERE order_status != 'incomplete' 
             and active = 1 order by $sort $order LIMIT $start, $end");
        return $result;
    }
    public function getUnshippedOrdersTotal() {
        
        $result = $this->_db->fetchRow("SELECT count(order_id) as total from `order` WHERE order_status != 'incomplete' 
             and active = 1");
        return $result;
    }
    
    public function savenotes($data) {
        try {          
            $this->_db->insert('order_notes', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    public function getInternalNotes($orderId) {
        return $this->_db->fetchAll("SELECT * FROM order_notes WHERE order_id = '$orderId' order by enter_time DESC ");         
    }
    
}