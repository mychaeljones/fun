<?php

class CheckoutController extends Zend_Controller_Action {

    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity()) {
           $this->_helper->redirector('login', 'user');
        }
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        $this->_checkout = new Application_Model_Checkout;
        $this->_product = new Application_Model_Product;
        $this->_users = new Application_Model_User; 
        $this->_orders = new Application_Model_Order; 
        $this->_cart = new Application_Model_Cart;
    }

    public function indexAction() {
        
    }
    
    public function billingAction() {
        
        $address = $this->_orders->get_cart($this->_auth->id);
        
        if (!$address['address_id']) {
             $this->_helper->flashMessenger->addMessage("Please choose a shipping address.");
              $this->_helper->redirector('shipping');
        }
        //get payment option
        $paymentOption = $this->_auth->payment_option;
        if ($paymentOption == 'paypal'){
             $this->_helper->redirector('paypal');
        }
        if ($paymentOption == 'wu' || $paymentOption == 'wire' || $paymentOption == 'mg'){
             $this->_helper->redirector('purchase');
        }
               
        $user = $this->_users->getUser( $this->_auth->id);    
        $form = new Application_Form_Card($user['country']);
        $form->populate($user);
        $this->view->form = $form;
        $request = new Application_Service_AuthorizeNetCIM;
        //$auth = Zend_Auth::getInstance()->getIdentity();
        if ($this->_auth->profile_id == 0 || is_null($this->_auth->profile_id)) {        
            $customerProfile                  = (object)array();
            $customerProfile->merchantCustomerId= $this->_auth->id;       
            $response = $request->createCustomerProfile($customerProfile); 
            if ($response->isOk()) {
                $this->_auth->profile_id = $response->getCustomerProfileId();           
            }
            //update      
            $data= array('profile_id' =>  $this->_auth->profile_id);        
            $this->_users->editUser($data, $this->_auth->id); 
        } 
        //get customer payment profile
        $this->view->profiles = $this->_users->getPaymentProfiles($this->_auth->id);       
    }
    
    public function purchaseAction() {
        $this->_presubmitAction();
        
        $orderId = $this->_orders->check_cart($this->_auth->id);
        $this->view->order = $this->_orders->get_order($orderId);
        $this->view->items = unserialize($this->view->order['items']);
        $this->view->paymentOption =  $this->_auth->payment_option;
       
        if ($this->getRequest()->isPost()) {          
            $order['order_status'] = 'onhold';
            $order['payment_method'] = $this->_auth->payment_option;
            $this->_orders->update($order, (int) $orderId);
            foreach ( $this->view->items as $item) {
                $data = array('order_id' => $orderId,
                    'product_id' => $item->productId,
                    'name' => $item->name,
                    'quantity' => $item->qty,
                    'price' => $item->price);
                $this->_orders->save_item($data);
            }
            $this->_cart->emptyCart();
            $this->_helper->redirector('thank-you');
        }
    }
    
   public function paypalAction() {
        $this->_presubmitAction();
        if ($this->getRequest()->isPost()) {
            $adapter = new Application_Service_Paypal_Client();
            
            $returnURL = 'http://wordpressweb/biz/checkout/paypalsuccess';
            $cancelURL = 'http://wordpressweb/biz/checkout/billing';
            $total = $this->_cart->getTotal();
                        
            //get token
            $reply = $adapter->ecSetExpressCheckout($total, $returnURL, $cancelURL, 
                    'USD', array());
                    
            // ...If we succeed, we must redirected to PayPal at this point.
            if ($reply->isSuccessful()) {                
                    // Let's turn that message body into something we can use...
                    $replyData = $adapter->parse($reply->getBody());
                                     
                    // If we did in fact succeed, we will now have a token to use
                    if ($replyData->ACK == 'Success' || $replyData->ACK == 'SUCCESSWITHWARNING') {

                            $token = $replyData->TOKEN; // ...It's already URL encoded for us.                          
                            // Redirect to the PayPal express checkout page, using the token.
                            header(
                                    'Location: ' . 
                                    $adapter->PAYPAL_URL. $token
                            );                            
                    }
            } else {
                    // Something went wrong.
                    throw new Exception('ECSetExpressCheckout: We failed to get a successfull response from PayPal.');
            } 
        }
   }
  
    public function validateCardAction() {
        $request = new Application_Service_AuthorizeNetCIM;
        $response = $request->validateCustomerPaymentProfile($this->_auth->profile_id, $_POST['paymentProfileId'], $_POST['cvv'], "liveMode");
        if ($response->isOk()) {
            $message['status'] = "OK";
             
          //  echo '<pre>';
            //var_dump($response->getTransactionResponse()->response);
            $return = explode(',', $response->getTransactionResponse()->response);
           // var_dump($return);                        
            $payment = array ('payment_firstname' => $return['13'],
                'payment_lastname' => $return['14'],
                'payment_company'=>$return['15'],
                'payment_address1'=>$return['16'],            
                'payment_city'=>$return['17'],
                'payment_state'=>$return['18'],
                'payment_zipcode'=>$return['19'],
                'payment_country'=>$return['20'],
                'payment_method'=>'CC',
                'payment_profile_id' => $_POST['paymentProfileId']
                );
             
            if ($orderId = $this->_orders->check_cart($this->_auth->id)) {             
                $this->_orders->update($payment, $orderId);
            }
          
        } else {
            $message['status'] = "FAILED";
        }
        echo json_encode($message);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE); 
    }
    
    protected function _presubmitAction() {
        //save session to the db
        $shippingCost = $this->_cart->getShippingCost();
        if (empty($shippingCost)) {
           $this->_helper->redirector('shipping');
        }
        $salesTax = $this->_cart->getSalesTax();      
        $total = $this->_cart->getTotal();
       
        $data = array('shipping_service' => NULL, 'shipping_rate' => 0.00,
            'tax_rate'=>0, 'tax_amount'=>0.00, 'tax_location_name'=> NULL, 'tax_location_code'=>NULL,
            'total'=> 0.00
        );
        
        if (isset($shippingCost['selected'])) {
            $data['shipping_service'] =  $shippingCost['selected']['service'];
            $data['shipping_rate']    =  $shippingCost['selected']['rate'];            
        }
        if (isset($salesTax['amount'])) {
                $data['tax_rate'] = $salesTax['taxRate'];
                $data['tax_amount'] = $salesTax['amount'];
                $data['tax_location_name'] = $salesTax['tax_location_name'];
                $data['tax_location_code'] = $salesTax['tax_location_code'];                
        }
        $data['total'] = $total;
        
        if ($orderId = $this->_orders->check_cart($this->_auth->id)) {  
            $this->_orders->update($data, $orderId);
        }              
                
    
    }
    
    
    public function confirmAction() {
        //save session to the db
        $this->_presubmitAction();                 
                
        $this->view->order =  $this->_orders->get_cart($this->_auth->id);
        $this->view->items = unserialize($this->view->order['items']);
        
       // echo '<pre>';
       // var_dump($this->view->items);
        
        $request = new Application_Service_AuthorizeNetCIM;
        $paymentProfile = $request->getCustomerPaymentProfile($this->_auth->profile_id, $this->view->order['payment_profile_id']);          
        $this->view->card = $paymentProfile->xml->paymentProfile->payment->creditCard;
        
      
        if ($this->getRequest()->isPost()) {
            
            $transaction = new AuthorizeNetTransaction;
            $transaction->amount = $this->view->order['total'];
            $transaction->customerProfileId = $this->_auth->profile_id;
            $transaction->customerPaymentProfileId = $this->view->order['payment_profile_id'];
            $transaction->order->invoiceNumber = $this->view->order['order_id'];

            foreach ($this->view->items as $item) {
               if(is_object($item)){
                $lineItem              = new AuthorizeNetLineItem;        
                $lineItem->itemId      = $item->productId;
                $lineItem->name        = substr($item->name, 0, 31); 
                $lineItem->quantity    = $item->qty;
                $lineItem->unitPrice   = $item->price;           
                $transaction->lineItems[] = $lineItem;            
               }
            }
            $response = $request->createCustomerProfileTransaction("AuthCapture", $transaction);
             //echo '<pre>';
            //var_dump($response);

            if ($response->isOk() ){
                $transactionResponse = explode(',', $response->getTransactionResponse()->response);
                
                if ($transactionResponse[0] == 1) {   
                    $order = array ('payment_status' => (int)$transactionResponse[0],
                    'payment_response' => $transactionResponse[3],
                    'payment_transactionId' => $transactionResponse[6],
                    'date_modified' =>  date("Y-m-d H:i:s"),
                    'comment' => $_POST['comment']
                    );
                //echo '<pre>';
                //var_dump($order);                    
                  //  $order['order_status'] = $this->view->order['total'] < 4000?'processing':'onhold';     
                    if ($this->view->order['total'] < 4000) {
                        $order['order_status'] = 'processing';
                    } else {
                        $order['order_status'] = 'onhold';
                        $notes = array ('order_id' => $this->view->order['order_id'],'author' => 'Processor',
                            'notes'=> 'Onhold due to Large Dollar Amount');
                        $this->_orders->savenotes($notes);
                    }
                    
                    $this->_orders->update($order, (int)$this->view->order['order_id']);
                    foreach ($this->view->items as $item) {
                        if(is_object($item)){
                        $data = array ('order_id' => $this->view->order['order_id'],
                                'product_id' => $item->productId,
                                'name' => $item->name,
                                'quantity'=>$item->qty,
                                'price' => $item->price);
                        $this->_orders->save_item($data); 
                        }
                        //save item options
                        if (!empty($item->options)) {
                            foreach ($item->options as $option) {
                                $options = array('order_id' => $this->view->order['order_id'],
                                    'order_product_id' =>  $item->productId,
                                    'option_description' => $option['descriptions'],
                                    'product_option_value_id' => serialize($option['values']),
                                    'quantity' => $option['qty']
                                );
                             //   var_dump($options);
                              //  die();
                                $this->_orders->save_order_option($options);
                            }
                        }
                    }                    
                    $this->_cart->emptyCart();
                   // $this->_helper->redirector('thank-you');                    
                    $this->_redirect('/checkout/thank-you/id/' . base64_encode($this->view->order['order_id']));
                    
                } 

            }  else {
                //A duplicate transaction has been submitted
               // echo '<pre>';
                //var_dump($response);
            }
        }
        
    }
    
    public function shippingAction() {       
        $this->view->contact_email = $this->_auth->email;
        $this->view->contact_phone = $this->_auth->contactphone;
        
        $this->view->addresses = $this->_users->getAddresses($this->_auth->id);   
        $this->view->flashMessage = $this->_helper->flashMessenger->getMessages();        
        //select the address id from the session        
       // var_dump($this->_cart->getShipping());
        if ($address = $this->_cart->getShipping()) {   
            
           // var_dump($address);
            if(!empty($address['address_id'])) {
                $this->view->selected = $address['address_id'];
            }
            if(!empty($address['contact_phone'])) {
                $this->view->contact_phone = $address['contact_phone'];
            } 
            if(!empty($address['contact_email'])) {
                $this->view->contact_email = $address['contact_email'];
            }
        }
    }
    
    public function updateShippingAction() {
      //  var_dump($_POST);
        //get shipping address data
        $address =  $this->_users->getAddress($this->_auth->id, $_POST['addressId']);
        
        $shippingAddress = array('shipping_firstname' => $address['firstname'],
            'shipping_lastname'=>$address['lastname'],
            'shipping_company'=>$address['company'],
            'shipping_address1'=>$address['address1'],
            'shipping_address2'=>$address['address2'],
            'shipping_city'=>$address['city'],
            'shipping_state'=>$address['state'],
            'shipping_zipcode'=>$address['zipcode'],
            'shipping_country'=>$address['country'],
            'address_id'=>$_POST['addressId'], 
            'contact_email'=>$_POST['contact_email'],
            'contact_phone'=>$_POST['contact_phone'],
            );
       
        if ($orderId = $this->_orders->check_cart($this->_auth->id)) {            
            $this->_orders->update($shippingAddress, $orderId); //save shipping in db
            $this->_cart->setShipping($shippingAddress); //update shipping in the session.           
        }      
                
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE); 
    }
    
    
   public function shippingRateAction() {
   
       $request = 1;
       
       $data['shipTo'] = $this->_cart->getShipping();     
   
       //shipping cost is in the session
       $shipping = $this->_cart->getShippingCost();
      // echo '<pre>';
       //var_dump($shipping);
       //var_dump($this->_getParam('row'));
       //address didn't contain rating info
       if (isset($shipping['ups']) && !is_array($shipping['ups'])) {
           $this->view->message = $shipping['ups'];
       }            
       
       if (isset($shipping['ups']) && is_array($shipping['ups'])) {            
            //it's the same address id as last one                     
            if ($shipping['address_id'] == $data['shipTo']['address_id']) {
                $this->view->rates = $shipping['ups'];                 
                if (is_numeric($this->_getParam('row'))) {
                    $index = (int)$this->_getParam('row');
                    //var_dump($index);
                    $this->view->selected = $shipping['ups'][$index];
                   // var_dump($this->view->selected);
                    $this->_cart->setShippingCost($this->view->rates, $shipping['address_id'], $this->view->selected);  
                } else {
                    $this->view->selected = $shipping['selected'];
                }
                $request = 0;
            } 
        }
               
        if ($request == 1 && $this->getRequest()->isXmlHttpRequest()) {
            $items = $this->_cart->getCartItems();             
            
            $data['package'] =  $this->_orders->calculate_shipping_info($items);          
                       
            $address_id = $data['shipTo']['address_id'];
            unset($data['shipTo']['address_id']);      
            $request = new Application_Service_Ups_Rate($data);  
            $this->view->rates = $request->rates;
            
            //new request, make first array as default          
            if (is_array($this->view->rates)) {
                $this->view->message = '';
                $this->view->selected = $this->view->rates[0];          
                $this->_cart->setShippingCost($this->view->rates, $address_id, $this->view->selected);            
            } else {
                $this->view->message = $this->view->rates.' Please check your shipping address.';
                $this->_cart->setShippingCost($this->view->message, $address_id, array());            
            }
        }
       
        if ($this->getRequest()->isXmlHttpRequest()) {  
            $this->_helper->layout->disableLayout();
           // $this->_helper->viewRenderer->setNoRender(TRUE); 
        }       
   }
    
    public function completeAction() {
        if ($this->getRequest()->isPost()) {
             $data = $this->_getAllParams();
             echo '<pre>';
             var_dump($data);
             $shippingAddress = array(
                 'user_id' => $this->_auth->id,
                 'type' => 'shipping', 
                 'firstname'=> $data['firstname'], 
                 'lastname' => $data['lastname'],
                 'address1' => $data['address1'],
                 'address2' => $data['address2'],
                 'city' => $data['city'],
                 'state' => $data['state'],
                 'zipcode' => $data['zipcode'], 
                 'country' => $data['country'],
                 'main' => 1,
             );
             $paymentAddress = array(
                 'user_id' => $this->_auth->id,
                 'type' => 'payment', 
                 'firstname'=> $data['billing_firstname'], 
                 'lastname' => $data['billing_lastname'],
                 'address1' => $data['billing_address1'],
                 'address2' => $data['billing_address2'],
                 'city' => $data['billing_city'],
                 'state' => $data['billing_state'],
                 'zipcode' => $data['billing_zipcode'], 
                 'country' => $data['billing_country'],
                 'main' => 1
            );
             
             $this->_checkout->saveAddress($shippingAddress);
             $this->_checkout->saveAddress($paymentAddress);
        }    
        $this->_helper->redirector('thank-you');
   }
   
  
   public function paypalCheckoutAction() 
   {
       $items = array(
            0 => array(
                    'name' => 'Dog Bowl',
                    'amt' => 1,
                    'qty' => 5.00
                
            ),
            1 => array(
                    'name' => 'Chew Toy',
                    'amt' => 3,
                    'qty'=>6.00
            ),
            2 => array(
                    'name' => 'Doggy Mints',
                    'amt' => 1,
                    'qty' => 4.99                    
            )
          
      
        );
 
       $this->view->items = $items;
       
// ...Did the user submit the form?
        if (!empty($_REQUEST['submit'])) {
            // Great! They've confirmed their order.
            // Let's try out our new checkout code.

            // First off, we need to obtain an
            // authorization token.

            $adapter = new Application_Service_Paypal_Client();

            $amount = 0.0;

            foreach($items as $item) {
                    $amount += $item['qty'] * $item['amt'];
            }

            $returnURL = 'http://wordpressweb/biz/checkout/thank-you';
            $cancelURL = 'http://wordpressweb/biz/checkout/billing';

            $currency_code = 'USD'; // Assuming we're using the US Dollar.

            // Let's ask for a token.
            /*$reply = $adapter->ecSetExpressCheckout(
                    $amount, 
                    $returnURL, 
                    $cancelURL, 
                    $currency_code
            );*/
            
            $reply = $adapter->ecSetExpressCheckout($amount, $returnURL, $cancelURL, 
                    $currency_code, $items);
               
            // ...If we succeed, we must redirected to PayPal at this point.
            if ($reply->isSuccessful()) {
                  
                    // Let's turn that message body into something we can use...
                    $replyData = $adapter->parse($reply->getBody());
                  
                    // If we did in fact succeed, we will now have a token to use
                    if ($replyData->ACK == 'Success' || $replyData->ACK == 'SUCCESSWITHWARNING') {

                            $token = $replyData->TOKEN; // ...It's already URL encoded for us.
                            
                            // Save the amount total... We must use this when we capture the funds.
                            $_SESSION['CHECKOUT_AMOUNT'] = $amount;

                            // Redirect to the PayPal express checkout page, using the token.
                            header(
                                    'Location: ' . 
                                    $adapter->PAYPAL_URL. $token
                            );
                    }
	} else {
		// Something went wrong.
		throw new Exception('ECSetExpressCheckout: We failed to get a successfull response from PayPal.');
	}
}

 

       
   }
   
   /**
    * redirect from paypal
    * 
    */  
   public function paypalsuccessAction() {
        $adapter = new Application_Service_Paypal_Client();
      
        $token = $_REQUEST['token'];
	$payer_id = $_REQUEST['PayerID'];

        $orderId = $this->_orders->check_cart($this->_auth->id);
        $this->view->order = $this->_orders->get_order($orderId);
       
        //get it out of the session
        $this->view->shipTo = $this->_cart->getShipping(); 
        $this->view->shippingCost = $this->_cart->getShippingCost();
        
        $this->view->salesTax = $this->_cart->getSalesTax();
       // $this->view->total = $this->_cart->getTotal();               
        $this->view->cartItems = unserialize($this->view->order['items']);  
       
    
        $parameter= array('itemAmt' => $this->view->cartItems['subTotal'],            
            'shippingAmt' => $this->view->shippingCost['selected']['rate'],
            'taxAmt' => $this->view->salesTax['amount'],
            'finalTotal' =>  $this->view->order['total'], 
            'orderId' => $orderId,
            'shipName' => $this->view->shipTo['Name'],
            'address' => $this->view->shipTo['Address']
            );
        $parameter['taxAmt'] = isset($this->view->salesTax['amount'])?$this->view->salesTax['amount']:'0.00';        
        $parameter['items'] =  $this->view->cartItems;
        
        $reply = $adapter->ecDoExpressCheckout($token, $payer_id, $parameter, 'USD', 'Sale');
            
 
	// Did we get a valid reply?
	if ($reply->isSuccessful()) {
		// Yes! We would usually save our order data at this point,
		// but, we can just output to the screen for now. :-)
		// The funds may or may not have been captured.
		// Check the $replyData->ACK property to know for sure.
 
		$replyData = $adapter->parse($reply->getBody());
              
                if ($replyData->ACK == 'Success') {                      
                    $order = array ('payment_status' => 1,
                    'payment_response' => '',
                    'payment_transactionId' => $replyData->PAYMENTINFO_0_TRANSACTIONID,
                    'date_modified' =>  date("Y-m-d H:i:s"),
                  //  'comment' => $_POST['comment']
                    );
                    
                    $order['order_status'] = $this->view->order['total'] < 4000?'processing':'onhold';     
                    
                    $order['payment_method'] = 'paypal';
                    $this->_orders->update($order, (int)$orderId);
                    foreach ( $parameter['items'] as $item) {
                        $data = array ('order_id' => $orderId,
                                'product_id' => $item->productId,
                                'name' => $item->name,
                                'quantity'=>$item->qty,
                                'price' => $item->price);
                        $this->_orders->save_item($data);                        
                    }                    
                    $this->_cart->emptyCart();
                    $this->_helper->redirector('thank-you');
                    
                }
              //  echo '<pre>';
	//	print_r($replyData); 
		$this->_helper->viewRenderer->setNoRender(TRUE);  
		
	} else {
		// No. Throw an exception.
		throw new Exception('We were unable to complete the Paypal ECDoExpressCheckout API call.');
                $this->_helper->viewRenderer->setNoRender(TRUE); 
        }
           
   }
   
   public function thankYouAction() {
       
        $this->view->headTitle('Beaming White - Thank you for your order.');
        $orderId = base64_decode($this->_getParam('id'));
        $this->view->order = $this->_orders->get_order($orderId);
        if ($this->view->order['user_id'] !=  $this->_auth->id) {
             $this->_helper->redirector('index', 'user');
        }
        $this->view->items = $this->_orders->get_invoice_items($orderId);
      
   }
    
}