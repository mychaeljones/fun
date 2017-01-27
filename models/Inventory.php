<?php

class Application_Model_Inventory {
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
    }

    public function get_orders(){
      return $this->_db->fetchAll("SELECT inventory_order.*, user.firstname FROM inventory_order 
        left join user on user.id = inventory_order.created_by  
        ORDER BY created_time DESC, item ASC");          
    }
    
    public function get_order($id) {
        return $this->_db->fetchRow("SELECT inventory_order.*, user.firstname, u2.firstname as modifiedBy           
            from inventory_order 
            left join user on user.id=inventory_order.created_by 
            left join user as u2 on u2.id = inventory_order.modified_by            
            WHERE inventory_order.id = '$id'");
    }
    public function get_inventory_notes($id, $type) {
        return $this->_db->fetchAll("SELECT * from inventory_notes WHERE type='$type' and id  = '$id'");        
    }
    
    public function get_inventory() {
        return $this->_db->fetchAll("SELECT item, sum( quantity_ordered) as quantity_ordered , sum( quantity_received ) 
            as quantity_received, sum( quantity_oh_china ) as quantity_oh , sum( total_shipped ) as total_shipped
            FROM `inventory_order`
            GROUP BY item");
    }
    
    public function update_orders($data, $id) {        
        try {              
            $update = $this->_db->update('inventory_order', $data, $this->_db->quoteInto("id = ?", $id));
            //var_dump($update);
            return $update;                       
        } catch (Exception $e) {         
           // var_dump ($e->getMessage());
            //die();
            return $e->getMessage();
        }
    }
    public function save_inventory_notes($data){           
        try {          
            $this->_db->insert('inventory_notes', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    
    public function log($data) {
        try {          
            $this->_db->insert('inventory_action_log', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    public function get_log($id, $type) {
       return $this->_db->fetchAll("SELECT * from  inventory_action_log WHERE id  = '$id' AND type = '$type' ORDER BY action_time");   
    }
    
    public function save_orders($data){           
        try {          
            $this->_db->insert('inventory_order', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    } 
    public function save_shipment($data){           
        try {          
            $this->_db->insert('inventory_shipment', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    } 
    public function update_shipment($data, $shipmentId) {        
        try {              
            $update = $this->_db->update('inventory_shipment', $data, $this->_db->quoteInto("inventory_shipment_id = ?", $shipmentId));
            //var_dump($update);
            return $update;                       
        } catch (Exception $e) {         
            
            return $e->getMessage();
        }
    }
    public function get_shipments($id) {
       return $this->_db->fetchAll("SELECT * from inventory_shipment WHERE order_id  = '$id'");  
    }
    public function get_shipment($shipmentId) {
       return $this->_db->fetchRow("SELECT * from inventory_shipment WHERE inventory_shipment_id  = '$shipmentId'");   
    }
    
    public function shipment_total($id) {
       $result = $this->_db->fetchRow("SELECT sum(quantity) as totalShip from inventory_shipment WHERE order_id = '$id' ");
       return $result?(int)$result['totalShip']:0;
    }
    
}