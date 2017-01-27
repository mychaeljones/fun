<?php

class InventoryController extends Zend_Controller_Action {

    private $_requriedFields = array('item'  => 'Item Field is Required',
                                     'quantity_ordered'   => 'Quantity Field is Required',                                     
                                    );
    private $_shipmentRequriedFields = array('destination'  => 'Destination Field is Required',
                                     'carrier'   => 'Carrier Field is Required',                                     
                                     'quantity' => 'Quantity Field is Required'
                                    );
    
    
    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
           $this->_helper->redirector('login', 'user');
        }
        $this->_auth = Zend_Auth::getInstance()->getIdentity();        
        $this->m_product = new Application_Model_Product;
        $this->m_user = new Application_Model_User; 
        $this->m_inventory = new Application_Model_Inventory;         
    }

    public function viewAction() {
        $this->view->orders = $this->m_inventory->get_orders();
    }
    
    public function logAction() {
         $id = $this->_getParam('id');
         $type = $this->_getParam('type');
         $this->view->logs = $this->m_inventory->get_log($id, $type);          
         $this->_helper->layout()->disableLayout();
    }
    
    private function _validator($data = array())
    {
       foreach ($this->_requriedFields as $field => $message) {
           if (empty($data[$field])) {
               $this->error .= $message . '<br>';
           }
       }
    }
    
    public function updateOrderAction() {
        $id = $this->_getParam('id');
        $this->view->order = $this->m_inventory->get_order($id);  
        $originalOrder = $this->view->order;
        $this->view->notes = $this->m_inventory->get_inventory_notes($id, 'order');
        $this->view->shipment = $this->m_inventory->get_shipments($id);
        
        if ($this->getRequest()->isPost()) {            
            $data = array('item' => trim($this->_getParam('item')), 
                          'quantity_ordered' => trim($this->_getParam('quantity_ordered')),
                          'priority' => $this->_getParam('priority'),
                          'supplier' => trim($this->_getParam('supplier')), 
                          'supplier_english' => trim($this->_getParam('supplier_english')), 
                          'specification' => $this->_getParam('specification'),                         
                          'quantity_ordered'=> $this->_getParam('quantity_ordered'),
                          'quantity_received'=> $this->_getParam('quantity_received'),
                          'EDD' => $this->_getParam('EDD'),
                          'ADD' => $this->_getParam('ADD'),
                          'modified_by' => $this->_auth->id,
                          'modified_time' => date('Y-m-d H:i:s'));         
           $this->_validator($data);

           //convinently assign this to display new input values
           // $this->view->order = $data;
            
            if (isset($this->error)) {
                $this->view->error = $this->error;
                return;
            } 
            
            if ($data['quantity_received'] != $originalOrder['quantity_received'] ){
                $data['total_shipped'] = $this->m_inventory->shipment_total($id);
                $data['quantity_oh_china'] = $data['quantity_received'] - $data['total_shipped'];
            }
           //save order notes
            if (trim($this->_getParam('notes')) != '') {
                $notes = array('notes' =>  $this->_getParam('notes'), 
                               'type' => 'order',
                               'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                               'id' => $id);
                $this->m_inventory->save_inventory_notes($notes);
            }
        
            if ($this->m_inventory->update_orders($data, $id)) {
                //log it
                $logFields = array('item' =>'item',
                                   'priority'=>'priority',
                                   'quantity_ordered' => 'quantity ordered',
                                   'quantity_received' => 'quantity received',
                                   'EDD' => 'EDD',
                                   'ADD' => 'ADD');
                foreach ($logFields as $key=> $logField) {
                    if ($originalOrder[$key] != $data[$key]) {
                         $log = array('action' => 'Changed',
                             'id'=> $id,
                             'type' => 'order',
                             'item' => $logField,
                             'value' => $data[$key],                             
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                             'original_values' => serialize($originalOrder));
                         $this->m_inventory->log($log);
                    }
                }
                
                
                /*$mail = new Zend_Mail();
                $message = "This is an automatic email to notify you an order for {$data['item']} has been requested.";
		$mail->setBodyHTML($message);
		$mail->setFrom('inventory@beamingwhite.com', 'beamingwhite.com');		
		$mail->addTo('jing@beamingwhite.com', 'Support');
		$mail->setSubject('Item Order Created');
                */
                $this->view->order = $this->m_inventory->get_order($id);  
                $this->view->message = 'Update Successfully';
                 //$this->_redirector->gotoUrl('/my-controller/my-action/param1/test/param2/test2');
                //$this->_helper->flashMessenger->addMessage("Order info updated successfully.");
               // $this->_redirect('/inventory/update-order/id/' . $id);
           } else {
               $this->view->error = 'Unable to update';
           }
        }
    }
    
    public function createShipmentAction() {
        $id = $this->_getParam('id');
        $this->view->order = $this->m_inventory->get_order($id);  
        $this->view->notes = $this->m_inventory->get_inventory_notes($id, 'shipment');
        
        if (($this->_getParam('shipment'))) {            
            $shipmentId = (int)$this->_getParam('shipment');            
            $this->view->shipment = $this->m_inventory->get_shipment($shipmentId); 
            $originalShipment = $this->view->shipment;
            $this->view->notes = $this->m_inventory->get_inventory_notes($shipmentId, 'shipment');
            $this->view->action = 'Update';
        } else {
            $this->view->action = 'Create';
        }
        
        if ($this->getRequest()->isPost()) {
            $data = array('destination' => trim($this->_getParam('destination')),
                'carrier' => trim($this->_getParam('carrier')),
                'order_id' => $id,
                'depart_date'=>  trim($this->_getParam('depart_date')),
                'quantity' => (int)$this->_getParam('quantity'),
                'unit_vol' => $this->_getParam('unit_vol'),
                'unit_dimension' => $this->_getParam('unit_dimension'),
                'unit_quantity' => $this->_getParam('unit_quantity'),
                'unit_weight' => $this->_getParam('unit_weight'),
                'created_by' => $this->_auth->id,
                'created_time' => date('Y-m-d H:i:s'));
            //convinently assign this to display new input values
            $this->view->shipment = $data;
            foreach ($this->_shipmentRequriedFields as $field => $message) {
                if (empty($data[$field])) {
                    $this->view->error .= $message . '<br>';
                }
            }

            if (isset($this->view->error)) {                
                return;
            } 
            if (trim($this->_getParam('notes')) != '') {
                $notes = array('notes' =>  $this->_getParam('notes'), 
                               'type' => 'shipment',
                               'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                               'id' => $id);
                $this->m_inventory->save_inventory_notes($notes);
            }
            //var_dump($this->m_inventory->save_orders($data));
            if($this->view->action == 'Create') {
                $shipmentId = $this->m_inventory->save_shipment($data);
                if (is_int($shipmentId)) {
                    //save notes
                    if (trim($this->_getParam('notes')) != '') {
                        $notes = array('notes' =>  $this->_getParam('notes'), 
                                       'type' => 'shipment',
                                       'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                                       'id' => $shipmentId);
                        $this->m_inventory->save_inventory_notes($notes);
                    }                    
                    //update shipment total if quantity is changed
                    if($data['quantity'] != $originalShipment['quantity']) {
                        $order['total_shipped'] = $this->m_inventory->shipment_total($id);
                        $order['quantity_oh_china'] = $this->view->order['quantity_received'] - $order['total_shipped'];
                        $this->m_inventory->update_orders($order, $id);
                    }
                    //log it
                     $log = array('action' => 'New',
                             'type' => 'shipment',                             
                             'id' => $shipmentId,
                             'item' => '',
                             'value' => $data['quantity'].' '.$this->view->order['item'].' to '.$data['destination']. ' departed '. $data['depart_date'],
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname);
                     $this->m_inventory->log($log);
                
                
                    $mail = new Zend_Mail();
                    $message = "This is an automatic email to notify you a shipment for {$data['item']} is created.
                    Please click here to see detail <br>";
                    $mail->setBodyHTML($message);
                    $mail->setFrom('inventory@beamingwhite.com', 'beamingwhite.com');
                    $mail->addTo('jing@beamingwhite.com', 'Support');
                    $mail->setSubject('Shipment Created');

                    $this->_redirect('/inventory/update-order/id/' . $id);
                }
            }
            if($this->view->action == 'Update') {
                if ($this->m_inventory->update_shipment($data, $shipmentId)) {
                    //save notes if there is any
                    if (trim($this->_getParam('notes')) != '') {
                        $notes = array('notes' =>  $this->_getParam('notes'), 
                                       'type' => 'shipment',
                                       'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                                       'id' => $shipmentId);
                        $this->m_inventory->save_inventory_notes($notes);
                    }
                    
                    //update shipment total if quantity is changed
                    if($data['quantity'] !=  $originalShipment['quantity']) {
                        $order['total_shipped'] = $this->m_inventory->shipment_total($id);
                        $order['quantity_oh_china'] = $this->view->order['quantity_received'] - $order['total_shipped'];
                        $this->m_inventory->update_orders($order, $id);
                    }
                    //log it
                    $logFields = array('destination' => 'destination',
                        'depart_date' => 'depart date',
                        'carrier' => 'carrier',
                        'quantity' => 'quantity',
                        );
                    foreach ($logFields as $key => $logField) {
                        if ($originalShipment[$key] != $data[$key]) {
                            $log = array('action' => 'Changed',
                                'id' => $shipmentId,
                                'type' => 'shipment',
                                'item' => $logField,
                                'value' => $data[$key],
                                'author' => $this->_auth->firstname . ' ' . $this->_auth->lastname,
                                'original_values' => serialize($originalShipment));
                            $this->m_inventory->log($log);
                        }
                    }
                    
                    $mail = new Zend_Mail();
                    $message = "This is an automatic email to notify you a shipment for {$data['item']} is updated.
                    Please click here to see detail <br>";
                    $mail->setBodyHTML($message);
                    $mail->setFrom('inventory@beamingwhite.com', 'beamingwhite.com');
                    $mail->addTo('jing@beamingwhite.com', 'Support');
                    $mail->setSubject('Shipment Updated');
                    $this->_redirect('/inventory/update-order/id/' . $id);                      
                }
            }
        }
        
    }
    public function stockAction() {
        $this->view->inventory = $this->m_inventory->get_inventory();
    }
    
    
    public function createOrderAction() {
        if ($this->getRequest()->isPost()) {            
            $data = array('item' => trim($this->_getParam('item')), 
                          'quantity_ordered' => trim($this->_getParam('quantity_ordered')),
                          'priority' => $this->_getParam('priority'),
                          'specification' => $this->_getParam('specification'),
                          'notes' => trim($this->_getParam('notes')),
                          'created_by' => $this->_auth->id,
                          'created_time' => date('Y-m-d H:i:s'));         
           $this->_validator($data);

            if ($this->error) {
                $this->view->error = $this->error;
                return;
            } 
            //var_dump($this->m_inventory->save_orders($data));
            $id = $this->m_inventory->save_orders($data);
            if (is_int($id)) {
                $log = array('action' => 'New',
                             'type' => 'order',
                             'id' => $id,
                             'item' => '',                    
                             'value' => $data['item']. ' quantity '.$data['quantity_ordered'],
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname);
                $this->m_inventory->log($log);
                
                $mail = new Zend_Mail();
                $message = "This is an automatic email to notify you an order for {$data['item']} has been requested.";
		$mail->setBodyHTML($message);
		$mail->setFrom('inventory@beamingwhite.com', 'beamingwhite.com');		
		$mail->addTo('jing@beamingwhite.com', 'Support');
		$mail->setSubject('Inventory Order Created');
                
                $this->_helper->redirector('view', 'inventory');
           }
        }
    }
    public function importInventoryAction() {
      
        $row = 1;
        if (($handle = fopen("../biz/data/inventory.xls", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                $orders = array(                                     
                    'item' => $data[2],
                    'quantity_ordered' => $data[3],
                    'quantity_received' => $data[4],
                    'quantity_oh_china' => $data[5],
                    'supplier' => $data[10],
                    'total_shipped' => $data[13]                   
                );
             
             
                if ($orders['item'] !='' && $orders['quantity_ordered'] > 0 ) {
                     echo '<pre>';
                var_dump($orders);
                echo "<br />\n";
                $this->m_inventory->save_orders($orders);
                }
            }
            fclose($handle);
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
}