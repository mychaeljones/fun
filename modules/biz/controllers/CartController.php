<?php

class CartController extends Zend_Controller_Action {

    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity()) {
           $this->_helper->redirector('login', 'user');
        }
        $this->_cart = new Application_Model_Cart;
        $this->_product = new Application_Model_Product;
        $this->_order = new Application_Model_Order;
        $this->userId = Zend_Auth::getInstance()->getIdentity()->id;
    }

    public function indexAction() {
        
    }

    /**
     * on top of add to cart page

      public function showAction() {
      echo 'product id'. $_POST['product_id']. ' added';
      $this->_helper->viewRenderer->setNoRender(TRUE);
      $this->_helper->layout->disableLayout();
      } */
    public function itemSummaryAction() {
        $this->view->headTitle('Your shopping cart');
        $this->view->cartItems = $this->_cart->getCartItems();       
        $this->_cart->saveCartItems();        
    }
    
    public function reorderAction() 
    {        
      $this->_cart->loadOrderItems($this->_getParam('id'));
      $this->_helper->redirector('item-summary');
    }
    
    public function showcartAction(){      
        $orderId = $this->_order->check_cart($this->userId);
        $order = $this->_order->get_order($orderId);
        $this->view->order = $order;
        //get it out of the session
        $this->view->shipTo = $this->_cart->getShipping(); 
        $this->view->shippingCost = $this->_cart->getShippingCost();
        //echo '<pre>';
        //var_dump($this->view->shippingCost['selected']);
        $this->view->salesTax = $this->_cart->getSalesTax();
        $this->view->total = $this->_cart->getTotal();
               
        $this->view->cartItems = unserialize($order['items']);   
        if ($this->getRequest()->isXmlHttpRequest()) {       
            $this->_helper->layout->disableLayout();              
        }
    }
            

    public function itemCountAction() {
        //$this->view->itemCount =  $this->_cart->getItemCount();        
        echo $this->_cart->getItemCount();

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    public function addAction() {
      //  echo '<pre>';
       
        $product = $this->_product->getProductById($this->_getParam('product_id'));
        if (null === $product) {
            throw Exception('Product could not be added to cart as it does not exist');
        }
        $options =  $this->_getParam('options')?$this->_getParam('options'):''; 
        
        $this->_cart->addItem($product, $this->_getParam('quantity'), $options);
       // var_dump($this->_cart->getCartItems()->options);
        $return['itemCount'] = $this->_cart->getItemCount();
        echo json_encode($return);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout->disableLayout();
    }

    public function updateAction() {
        if ($this->getRequest()->isPost()) {
          /* $data = $this->_getAllParams();
           var_dump($data['option']);
           die();
           
           foreach ($data as $key => $value) {         
                $temp = explode('_', $key);
                if ($temp[0] == 'quantity') {
                   $this->_cart->updateItem($this->_product->getProductById($temp[1]), $value);
                }
           }*/
            
           $option =  $this->_getParam('option')?$this->_getParam('option'):'';         
           $this->_cart->updateItem($this->_product->getProductById($this->_getParam('productId')), $this->_getParam('quantity'), $option);
           //reset shipping and shipping cost
           $this->_cart->unsetShipping();
           $this->_helper->redirector('item-summary');            
        }
     
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout->disableLayout();
    }

    public function removeAction() {
        $options =  $this->_getParam('options')?$this->_getParam('options'):''; 
        $this->_cart->removeItem((int) $this->_getParam('id'), $options);
        $this->_helper->redirector('item-summary', 'cart');
    }

}