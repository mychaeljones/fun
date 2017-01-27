<?php

class ProductionController extends Zend_Controller_Action {

    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity()) {
           $this->_helper->redirector('login', 'user');
        }
        $this->_auth = Zend_Auth::getInstance()->getIdentity();        
        $this->_product = new Application_Model_Product;
        $this->_users = new Application_Model_User; 
        $this->_orders = new Application_Model_Order; 
        
    }

    public function indexAction() {
        
    }
    public function processAction() {
        $this->view->orders = $this->_orders->getOrders('processing');       
        //$this->view->totalOrders = sizeof($orders);
    }
    public function getProcessAction() {
        $orders = $this->_orders->getOrders('processing');        
        echo json_encode($orders);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    public function onholdAction() {
        $this->view->orders = $this->_orders->getOrders('onhold');               
    }
    public function shippingAction() {
        $this->view->orders = $this->_orders->getOrders('shipping');               
    }    
    public function getUnshippedOrdersAction() {        
       
        /*if (!$this->_getParam('page')) {
            $page = 1;
        } else {
            $page = $this->_getParam('page');
        }*/
       $page = !$this->_getParam('page')?1: $this->_getParam('page');        
       $rows = !$this->_getParam('rows')?1:$this->_getParam('rows');
     
       $offset = $rows * ($page - 1);
       $sort = !$this->_getParam('sort') ? 'date_modified' : $this->_getParam('sort');
       $order = !$this->_getParam('order') ? 'ASC' : $this->_getParam('order');
        
       // echo $start; echo $end;
        
        $result['rows'] = $this->_orders->getUnshippedOrders($sort, $order, $offset, $rows);    
        $total = $this->_orders->getUnshippedOrdersTotal();
        $result['total'] = $total['total'];
        echo json_encode($result);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function changeStatusAction() {
        $data = array('order_status' => $_POST['status']);               
        if ($this->_orders->update($data,$_POST['order_id'])) {
            echo 'success';
        }
        //track it
        $data = array ('order_id' =>(int)$_POST['order_id'],                    
                    'notes' => 'Change order status to '. $_POST['status'],
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                );     
        $this->_orders->savenotes($data);
                   
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function updateQuantityAction() {
        
        $data = array('shipped_quantity' => trim($_POST['shipped_quantity']));        
        $item = $this->_orders->get_order_item($_POST['order_items_id']);
        if($item['quantity'] < $data['shipped_quantity']) {
            $return['result'] = 'error';
            $return['message'] = 'Shipped quantity can not be larger than ordered quantity.';
        } else {
            if($this->_orders->update_order_item($data, $_POST['order_items_id'])){
                $return['order_id'] = (int)$item['order_id'];
                $return['unshipped'] = $item['quantity'] - $data['shipped_quantity'];
                
                $notes = array ('order_id' =>(int)$item['order_id'],                    
                    'notes' => 'Change shipped quantity from '. $item['shipped_quantity'] . ' to '. $data['shipped_quantity'],
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                );     
                $this->_orders->savenotes($notes);                
                $return['notes'] = date('m/d/y g:i a'). ' '. $notes['author'].': '.$notes['notes']. '<br>';;               
                $return['result'] = 'success';
            }            
        }
        echo json_encode($return);
        
        /*if ($this->_orders->update($data,$_POST['order_id'])) {
            echo 'success';
        }
        //track it
        $data = array ('order_id' =>(int)$_POST['order_id'],                    
                    'notes' => 'Change order status to '. $_POST['status'],
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                );     
        $this->_orders->savenotes($data);
               */    
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function cancelOrderAction() {
        $data = array('active' => 0);               
        if ($this->_orders->update($data,$_POST['order_id'])) {
            echo 'success';
        }
        //track it
        $data = array ('order_id' =>(int)$_POST['order_id'],                    
                     'notes' => 'Canceled Order',
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                );     
        $this->_orders->savenotes($data);
                   
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function markPaidAction() {
        $data = array('payment_status' => 1);               
        $this->_orders->update($data,$_POST['order_id']);
           // echo 'success';
        
        //track it
        $data = array ('order_id' =>(int)$_POST['order_id'],                    
                     'notes' => 'Marked order as paid.',
                     'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                );     
       // $this->_orders->savenotes($data);
        if ($this->_orders->savenotes($data)) {
            echo date('Y-m-d G:i:s') . ' ' . $data['author'] . ':' . $data['notes'] . '<br>';
        }

        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    
    public function markShippedAction() {
        $data = array('order_status' => 'shipped');               
        $this->_orders->update($data,$_POST['order_id']);
        $this->_orders->set_shipped_quantity($_POST['order_id']);
       
        //track it
        $data = array ('order_id' =>(int)$_POST['order_id'],                    
                     'notes' => 'Marked order as shipped.',
                     'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                );     
       
        if ($this->_orders->savenotes($data)) {            
            echo 'success';
        }

        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function addnotesAction() {
        //var_dump($_POST);
        $this->view->order_id = $this->_getParam('id');
        if ($this->getRequest()->isPost()) {
            if (trim($_POST['notes'] != '')) {
                $data = array ('order_id' =>(int)$_POST['order_id'],                    
                    'notes' => trim($_POST['notes']),
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                );      
                if ($this->_orders->savenotes($data)) {
                    echo date('m/d/y g:i a') . ' '. $data['author'].':'.$data['notes'].'<br>';
                }                
                //if ajax call
                if ($this->getRequest()->isXmlHttpRequest()) {                   
                    $this->_helper->viewRenderer->setNoRender(true);                    
                }
            }
        }
        $this->_helper->layout()->disableLayout();
    }
    public function unshippedAction() {
        
        $total = $this->_orders->getUnshippedOrdersTotal();
        $this->view->total = $total['total'];        
    }
    
    /*public function getProcessDetailAction() {
        var_dump($this->_getParam('id'));
        die();
    }*/
    public function constructionAction() {
       // $this->_helper->layout()->disableLayout();
    }
    
    
}