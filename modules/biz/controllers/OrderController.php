<?php

class OrderController extends Zend_Controller_Action {

    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
           $this->_helper->redirector('login', 'user');
        }
        $this->_auth = Zend_Auth::getInstance()->getIdentity();        
        $this->_product = new Application_Model_Product;
        $this->_users = new Application_Model_User; 
        $this->_orders = new Application_Model_Order; 
        
    }

    public function indexAction() {
        
    }
    
    public function createAction() {
       
    }
    
    public function customerInfoAction() {
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            $this->view->user = $this->_users->getUserByEmail($email);  
        }
        
        if (($this->_getParam('id'))) {            
            $this->view->user = $this->_users->getUser($this->_getParam('id'));  
        }
        
        if(isset( $this->view->user)) {
          
                    
            /*if(!$this->view->user) {
               echo "na";
               $this->_helper->viewRenderer->setNoRender(TRUE);
            }*/
            $this->view->addresses = $this->_users->getAddresses($this->view->user['id']);
            $this->view->profiles = $this->_users->getPaymentProfiles($this->view->user['id']);
            
        } else {
             $this->_helper->redirector('create', 'order');
        }
        
        //$this->_helper->layout->disableLayout();
        
    }
    public function orderInfoAction() {
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            $this->view->user  = $this->_users->getUserByEmail($email);            
        }
        
        if (($this->_getParam('id'))) { 
            $this->view->user  = $this->_users->getUser($this->_getParam('id'));            
        }       
        
        if(isset( $this->view->user)) {
                             
            /*if(!$this->view->user) {
               echo "na";
               $this->_helper->viewRenderer->setNoRender(TRUE);
            }*/
            //check if there is existing incomplete internal order
            $this->view->order = $this->_orders->get_rep_order($this->view->user['id']);          
            
            $this->view->rep = $this->_users->getSalesRep($this->view->user['id']);
            $this->view->addresses = $this->_users->getAddresses($this->view->user['id']);
            
            $this->view->profiles = $this->_users->getPaymentProfiles($this->view->user['id']);
            
        } else {
             $this->_helper->redirector('create', 'order');
        }
    }
    
    public function editaddressAction() {      
            $address = $this->_users->getUserAddress($this->_getParam('id'));                     
            $form = new Application_Form_Address($address['country']);
            $form->setAction("/biz/order/editaddress/id/{$this->_getParam('id')}");
            $form->removeElement('submit');
            $form->setAttrib('id', 'address') ;
            $form->populate($address);
                        
            $this->view->form = $form;
                    
            if ($this->getRequest()->isPost()) {
                $form = new Application_Form_Address($_POST['country']);
                if ($form->isValid($_POST)) {                    
                    $_POST['action_time'] = date("Y-m-d H:i:s");                 
                    $_POST['address_id'] = $this->_getParam('id');                               
                    if ($this->_users->saveAddress ($_POST)) {                        
                        echo 'success';
                       // echo $_POST['firstname'].' '.$_POST['lastname'].','.$_POST['address1'].' '.$_POST['address2'].
                         //     ','.$_POST['city'].','.$_POST['state'].','.$_POST['zipcode'].','.$_POST['country'];
                    } else {
                        echo 'Something went terribly wrong';
                    }                      
                } else {                    
                   echo "Please enter all required fields";
                }
              $this->_helper->viewRenderer->setNoRender(TRUE);
              $this->_helper->layout()->disableLayout();  
           } 
           if ($this->getRequest()->isXmlHttpRequest()) {                                                         
               $this->_helper->layout()->disableLayout();   
                //
           }
    }
    public function addaddressAction() {
      
        $user = $this->_users->getUser($this->_getParam('id'));      
         
        $form = new Application_Form_Address($user['country']);
        $form->setAction("/biz/order/addaddress/id/{$this->_getParam('id')}");
        $form->removeElement('submit');
        $form->setAttrib('id', 'address');
        $form->populate($user);
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {      
           
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $data['user_id'] = $this->_getParam('id');                
                if ($addressId = $this->_users->saveAddress($data)) {
                    echo 'success';
                } else {
                    echo 'Something went terribly wrong';
                }
            } else {
                 echo "Please enter all required fields";
            }
            $this->_helper->viewRenderer->setNoRender(TRUE);
            $this->_helper->layout()->disableLayout();  
         }
        if ($this->getRequest()->isXmlHttpRequest()) {     
            $this->_helper->layout->disableLayout();
        }
    }
  
          
    public function shippingRateAction(){
     
        /*$items = array();
        $itemsTotal = 0;
        $this->view->id = $this->_getParam('id');
        */
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        $this->view->id = $this->_getParam('id');
        
        if (isset($_POST['userId'])) {
            $order = $this->_orders->get_rep_order($_POST['userId']);
            $items = array();
            if (!empty($order)) {
                $items = unserialize($order['items']);
            }
        }
     //   var_dump($items);
      //  die();
        
        //first standardize items
     /*   if (!empty($_POST['item'])) {
        foreach ($_POST['item'] as $key=>$name) {              
                if ($name) {
               
                    $product = $this->_product->getUserProductPrice($name,(int)$_POST['quantity'][$key],$_POST['userId']);
                  //  var_dump ($_POST['quantity']).'<br>';
                    if($product) {   
                         $items[$product['product_id']] = new stdClass;
                         $items[$product['product_id']]->productId = $product['product_id'];
                         $items[$product['product_id']]->volumn = $product['volumn'];
                         $items[$product['product_id']]->weight = $product['weight'];
                         $items[$product['product_id']]->qty = (int)$_POST['quantity'][$key];   
                                                
                       
                         $items[$product['product_id']]->name = $name;
                         $items[$product['product_id']]->price = $product['price'];
                        
                         $items[$product['product_id']]->lineCost = $product['price'] * $items[$product['product_id']]->qty;
                         $itemsTotal += $product['price'] * $items[$product['product_id']]->qty;                         
                    } else {
                         $return['error'] = "Can not find price for product $name. <br>";
                    }
                }                
            }
        }*/
        
        if (empty($items) && !is_numeric($this->_getParam('total'))) {          
            //no items, no previous rates
             $this->view->message = "Please specificy item(s) and quantity to be ordered.";             
        } elseif (is_numeric($this->_getParam('row'))){
                $address = $this->_orders->getAddressById($this->_getParam('id')); 
              
                $internalOrder = $this->_orders-> get_rep_order($address['user_id']);                
                
                //grap the rates from the database, 
                $this->view->rates = $this->_orders->get_order_shiprates($internalOrder['order_id']);    
                
                $index = (int) $this->_getParam('row');
                $this->view->selected = $this->view->rates[$index];                
                $this->view->itemsTotal = $this->_getParam('total');
                
                //update the database
                $data = array('shipping_service'=>$this->view->selected['service'], 
                    'shipping_rate'=>$this->view->selected['rate']);
                
                $data['tax_amount'] = 0;
                if ($internalOrder['tax_rate'] > 0) {
                     $this->view->taxRate = $internalOrder['tax_rate'];                     
                     $this->view->salesTax = round((($this->view->itemsTotal + $this->view->selected['rate']) * $this->view->taxRate), 2);                    
                     $data['tax_amount'] = $this->view->salesTax;                               
                }
                $this->view->total = $this->view->itemsTotal + $this->view->selected['rate'] +  $data['tax_amount'];
                $data['total'] = $this->view->total;            
                
                $this->_orders->save_internal_order($data, $address['user_id']);                
         } elseif (!empty($items)) {          
            
            //$items['subTotal'] = $itemsTotal;
            $this->view->itemsTotal = $items['subTotal'];
            
            
            if ($items['subTotal'] > 0) {
            
            //have some items, let's save it to the db
             $orderData = array ('contact_email' => $_POST['contact_email'],'contact_phone' => $_POST['contact_phone'],
                'submitted_by'=>$this->_auth->firstname. ' '. $this->_auth->lastname, 'user_id'=>(int)$_POST['userId'], 
                'date_modified' => date("Y-m-d H:i:s"), 'order_status'=>'incomplete', 'ip'=>$_SERVER['REMOTE_ADDR']
             );
            $this->_orders->save_internal_order($orderData, $_POST['userId']);
                       
            //API request        
            $data['package'] = $this->_orders->calculate_shipping_info($items); 
            $address = $this->_orders->getAddressById($this->_getParam('id'));  
            $data['shipTo']['Address'] = array('AddressLine' => $address['address1'].' '.$address['address2'],
                'City' => $address['city'],
                'StateProvinceCode' => $address['state'],
                'PostalCode' => $address['zipcode'],
                'CountryCode' => $address['country'],
              );
     
            $request = new Application_Service_Ups_Rate($data);
            $this->view->rates = $request->rates;                  
           
            if (is_array($this->view->rates)) {
                $this->view->selected = $this->view->rates[0]; 

                //have rates, save it to db
                $shippingAddress = array('shipping_firstname' => $address['firstname'],
                     'shipping_lastname'=>$address['lastname'],
                     'shipping_company'=>$address['company'],
                     'shipping_address1'=>$address['address1'],
                     'shipping_address2'=>$address['address2'],
                     'shipping_city'=>$address['city'],
                     'shipping_state'=>$address['state'],
                     'shipping_zipcode'=>$address['zipcode'],
                     'shipping_country'=>$address['country'],
                     'address_id'=> $this->_getParam('id'),
                     'shipping_service' => $this->view->selected['service'],
                     'shipping_rate' => $this->view->selected['rate'],
                     );

                    $shippingAddress['tax_rate'] = '';
                    $shippingAddress['tax_amount'] = 0;
                    $shippingAddress['tax_location_name'] = '';
                    $shippingAddress['tax_location_code'] = '';

                     if ($address['state'] == 'WA') {
                                  $address['zip'] = $address['zipcode'];
                                  $taxData = $this->_orders->get_wa_taxrate($address);
                                  if ($taxData['code'] == '3') {
                                      $this->view->taxRate = 0.084;
                                      //$this->view->salesTax = round((($itemsTotal + $this->view->selected['rate']) * $this->view->taxRate), 2);
                                      $this->view->tax_location_name = 'local';
                                      $this->view->tax_location_code = 'local';                       
                                  } else {
                                      $this->view->taxRate = floatval((string) $taxData['rate']);
                                      //$this->view->salesTax = round((($itemsTotal + $this->view->selected['rate']) * $this->view->taxRate), 2);
                                      $this->view->tax_location_name = (string) $taxData->rate['name'];
                                      $this->view->tax_location_code = (string) $taxData->rate['code'];
                                  }
                                   $this->view->salesTax = round((($items['subTotal'] + $this->view->selected['rate']) * $this->view->taxRate), 2);
                                   $shippingAddress['tax_rate'] = $this->view->taxRate;
                                   $shippingAddress['tax_amount'] = $this->view->salesTax;
                                   $shippingAddress['tax_location_name'] = $this->view->tax_location_name;
                                   $shippingAddress['tax_location_code'] = $this->view->tax_location_code;                          
                     }


                 $this->view->total = $items['subTotal'] + $shippingAddress['shipping_rate'] + $shippingAddress['tax_amount'];
                 $shippingAddress['total'] =   $this->view->total;  
                 $orderId = $this->_orders->save_internal_order($shippingAddress, $_POST['userId']);
                 //save the giant shipping rate array
                 $shipRates = array('shipping_rates'=> serialize($this->view->rates),'order_id' => $orderId);
                 $this->_orders->save_shiprates($shipRates);
                }//end if some rates
             else {
                    $this->view->message = $this->view->rates.' Please correct your shipping address.'; 
             } //don't have rates
            }//there are some items
        }//have items
        
       $this->_helper->layout->disableLayout();
    }
    
    public function getPriceAction() {        
       
         $price = $this->_product->getUserProductPrice($_POST['item'], $_POST['quantity'], $_POST['userId']);       
        // echo '<pre>';
         //var_dump($_POST);
         //var_dump($price);     
        
         if ($price &&  $_POST['quantity'] > 0) {
            $order = $this->_orders-> get_rep_order($_POST['userId']);
            $items = array();
            if (!empty($order)) {
                   $items = unserialize($order['items']);
            }            
            $product = $this->_product->getProductById($price['product_id']); 
            if (!isset($_POST['options'])) {
                
                $item = new Application_Model_Item($product,$_POST['quantity'], NULL);      
                $items[$product['product_id']] =  (object)array();  
                $items[$product['product_id']]->qty = $_POST['quantity']; 
                
                $items[(int)$price['product_id']]->name = $item->name;
                $items[(int)$price['product_id']]->price = $item->price;
                $items[(int)$price['product_id']]->volumn = $item->volumn;
                $items[(int)$price['product_id']]->weight = $item->weight;
                $items[(int)$price['product_id']]->lineCost = $item->lineCost;
                $items[(int)$price['product_id']]->productId = $item->productId;
                
            } else {
                $temp = preg_split('/-/', $_POST['options']);
                $options = array();
                foreach ($temp as $value) {
                    if (is_numeric($value)) {
                        $options[] = (int) $value;
                    }
                }                              
                // var_dump($this->_items[$product['product_id']]->options);
                if (!empty($items[(int)$price['product_id']])) { 
                     foreach ($items[(int)$price['product_id']]->options as $index => $productOptions) {
                        //try to find if any existing options is the same                      
                        $arrayDiff = array_diff($productOptions['values'], $options);
                        if (empty($arrayDiff)) {                        
                            break;
                        }
                    }
                }
                
                $itemQty = $items[(int)$price['product_id']]->qty - $items[(int)$price['product_id']]->options[$index]['qty'] + $_POST['quantity'];
                //only need to update the quantity
                $items[(int)$price['product_id']]->options[$index]['qty'] = $_POST['quantity'];
                
                //still need to recalculate                
                $item = new Application_Model_Item($product, $itemQty, NULL);
                $items[(int)$price['product_id']]->qty = $itemQty;
                $items[(int)$price['product_id']]->price = $item->price;
                $items[(int)$price['product_id']]->lineCost = $item->lineCost;
            }
            $items['subTotal'] = 0;
            foreach ($items as $item) {
                if (is_object($item)) {
                    $items['subTotal'] += $item->lineCost;
                }
            }
            
            $orderData = array ('contact_email' => $_POST['contact_email'],'contact_phone' => $_POST['contact_phone'],
                       'submitted_by'=>$this->_auth->firstname. ' '. $this->_auth->lastname, 'user_id'=>(int)$_POST['userId'], 'items'=> serialize($items),
                       'date_modified' => date("Y-m-d H:i:s"), 'order_status'=>'incomplete', 'ip'=>$_SERVER['REMOTE_ADDR']
                );
             if ($this->_orders->save_internal_order($orderData, $_POST['userId'])) {
                echo 'success';
             } else {
                echo 'Caught Errors';
             }
         
         } else {
             echo 'No price Found';
         }
              
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout->disableLayout();
    }
    
    public function processOptionsAction() {
        
       // var_dump($this->_getParam('optionQuantity'));
        //die();
        if ((!is_numeric($this->_getParam('optionQuantity')) || $this->_getParam('optionQuantity') <=0 )) {          
             echo 'Please enter a quantity.';                 
        } else {
            //first check if it's ordered
            $order = $this->_orders-> get_rep_order($_POST['userId']);
            $items = array();
            if (!empty($order)) {
                $items = unserialize($order['items']);
            }
            
            $product = $this->_product->getProductById((int)$_POST['optionProductId']);                      
            // var_dump($this->_items[$product['product_id']]->options);
            if (!empty($items[(int)$_POST['optionProductId']])) {  //item exist!
                foreach ($_POST['option'] as $key => $value) {
                    $currentOption[] = $value;
                }
                $sameOption = 0;
                foreach ($items[(int) $_POST['optionProductId']]->options as $index => $productOptions) {
                    //try to find if any existing options is the same                      
                    $arrayDiff = array_diff($productOptions['values'], $currentOption);
                    if (empty($arrayDiff)) {
                        $sameOption = 1;
                        break;
                    }
                }
                //var_dump($sameOption);
                //var_dump($index);
                                 
                if ($sameOption == 0) {         
                   //not the same
                    $itemOption = new Application_Model_Item($product,(int)$_POST['optionQuantity'], $_POST['option']);   
                    $items[$product['product_id']]->options[$index + 1] = $itemOption->options;
                } else {
                      //only need to update the qty for the option and the item             
                    $items[$product['product_id']]->options[$index]['qty'] = $items[$product['product_id']]->options[$index]['qty'] + $_POST['optionQuantity'];
                }  
                
                 $items[$product['product_id']]->qty = $items[$product['product_id']]->qty + (int)$_POST['optionQuantity'];
                 //need to recaulte
                 $item = new Application_Model_Item($product,$items[$product['product_id']]->qty, $_POST['option']); 
                 $items[$product['product_id']]->price = $item->price;       
                 $items[$product['product_id']]->lineCost = $item->lineCost;
             
                 
            } else {
                  $item = new Application_Model_Item($product, $_POST['optionQuantity'], $_POST['option']);           
                  $items[(int)$_POST['optionProductId']] = (object)array();
                  $items[(int)$_POST['optionProductId']]->qty = $item->qty;   
                  //if there is option, it's the first options for the product
                  $items[(int)$_POST['optionProductId']]->options[] = $item->options;

                  $items[(int)$_POST['optionProductId']]->name = $item->name;
                  $items[(int)$_POST['optionProductId']]->price = $item->price;
                  $items[(int)$_POST['optionProductId']]->volumn = $item->volumn;
                  $items[(int)$_POST['optionProductId']]->weight = $item->weight;
                  $items[(int)$_POST['optionProductId']]->lineCost = $item->lineCost;
                  $items[(int)$_POST['optionProductId']]->productId = $item->productId;
            }
            
            $items['subTotal'] = 0;
            foreach ($items as $item) {
                if (is_object($item)) {
                    $items['subTotal'] += $item->lineCost;
                }
            }
                           
            //have some items, let's save it to the db
            $orderData = array ('contact_email' => $_POST['contact_email'],'contact_phone' => $_POST['contact_phone'],
                'submitted_by'=>$this->_auth->firstname. ' '. $this->_auth->lastname, 'user_id'=>(int)$_POST['userId'], 'items'=> serialize($items),
                'date_modified' => date("Y-m-d H:i:s"), 'order_status'=>'incomplete', 'ip'=>$_SERVER['REMOTE_ADDR']
            );
            if ($this->_orders->save_internal_order($orderData, $_POST['userId'])) {
                echo 'success';
            } else {
                echo 'error';
            }

        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout->disableLayout();
    }
    
    public function processOptionsAction1() {
        
       // var_dump($this->_getParam('optionQuantity'));
        //die();
        if ((!is_numeric($this->_getParam('optionQuantity')) || $this->_getParam('optionQuantity') <=0 )) {          
             echo 'Please enter a quantity.';                 
        } else {
          
            //first find all items being ordered
            if (!empty($_POST['item'])) {
             
                foreach ($_POST['item'] as $key=>$name) {              
                   
                        if ($name && (int)$_POST['quantity'][$key] > 0) {
                            
                            $product = $this->_product->findProductName($name);
                           // $itemsTotal = 0;
                          //  var_dump($product);
                            if($product) {  
                               //  echo '<pre>';
           //var_dump($_POST['itemOptions']);                      
                                if($_POST['itemOptions']) {
                                       $itemQty = 0;
                                       foreach ($_POST['itemOptions'] as $key=> $values) {                                         
                                           $item = new Application_Model_Item($product[0], (int)$values['qty'],$values['value']);
                                           $items[$product[0]['product_id']]->options[$key] = $item->options;
                                           $itemQty +=  (int)$values['qty'];
                                       }
                                      
                                }
                                $items[$product[0]['product_id']]->qty = $itemQty;
                                $items[$product[0]['product_id']]->name = $item->name;
                                $items[$product[0]['product_id']]->price = $item->price;
                                $items[$product[0]['product_id']]->volumn = $item->volumn;
                                $items[$product[0]['product_id']]->weight = $item->weight;
                                $items[$product[0]['product_id']]->lineCost = $item->lineCost;
                                $items[$product[0]['product_id']]->productId = $item->productId;                            
                            }
                        } else if($name) { //just the name, check if it's in the option
                           $product = $this->_product->findProductName($name);                          
                          // $workingProductId[] = $product[0]['product_id'];
                        }
                       
                    }
            }//not empty item
           // echo '<pre>';
           // var_dump($items);
           // var_dump($_POST['option']);
             $product = $this->_product->getProductById((int)$_POST['optionProductId']);
                      
            // var_dump($this->_items[$product['product_id']]->options);
            if (!empty($items[(int)$_POST['optionProductId']])) {  //item exist!
                foreach ($_POST['option'] as $key => $value) {
                    $currentOption[] = $value;
                }
                $sameOption = 0;
                foreach ($items[(int) $_POST['optionProductId']]->options as $index => $productOptions) {
                    //try to find if any existing options is the same                      
                    $arrayDiff = array_diff($productOptions['values'], $currentOption);
                    if (empty($arrayDiff)) {
                        $sameOption = 1;
                        break;
                    }
                }
                //var_dump($sameOption);
                //var_dump($index);
                                 
                if ($sameOption == 0) {         
                   //not the same
                    $itemOption = new Application_Model_Item($product,(int)$_POST['optionQuantity'], $_POST['option']);   
                    $items[$product['product_id']]->options[$index + 1] = $itemOption->options;
                } else {
                      //only need to update the qty for the option and the item             
                    $items[$product['product_id']]->options[$index]['qty'] = $items[$product['product_id']]->options[$index]['qty'] + $_POST['optionQuantity'];
                }  
                
                 $items[$product['product_id']]->qty = $items[$product['product_id']]->qty + (int)$_POST['optionQuantity'];
                 //need to recaulte
                 $item = new Application_Model_Item($product,$items[$product['product_id']]->qty, $_POST['option']); 
                 $items[$product['product_id']]->price = $item->price;       
                 $items[$product['product_id']]->lineCost = $item->lineCost;
             
                 
            } else {
                  $item = new Application_Model_Item($product, $_POST['optionQuantity'], $_POST['option']);           
                  $items[(int)$_POST['optionProductId']] = (object)array();
                  $items[(int)$_POST['optionProductId']]->qty = $item->qty;   
                  //if there is option, it's the first options for the product
                  $items[(int)$_POST['optionProductId']]->options[] = $item->options;

                  $items[(int)$_POST['optionProductId']]->name = $item->name;
                  $items[(int)$_POST['optionProductId']]->price = $item->price;
                  $items[(int)$_POST['optionProductId']]->volumn = $item->volumn;
                  $items[(int)$_POST['optionProductId']]->weight = $item->weight;
                  $items[(int)$_POST['optionProductId']]->lineCost = $item->lineCost;
                  $items[(int)$_POST['optionProductId']]->productId = $item->productId;
            }
                    
            //have some items, let's save it to the db
            $orderData = array ('contact_email' => $_POST['contact_email'],'contact_phone' => $_POST['contact_phone'],
                'submitted_by'=>$this->_auth->firstname. ' '. $this->_auth->lastname, 'user_id'=>(int)$_POST['userId'], 'items'=> serialize($items),
                'date_modified' => date("Y-m-d H:i:s"), 'order_status'=>'incomplete', 'ip'=>$_SERVER['REMOTE_ADDR']
            );
            if ($this->_orders->save_internal_order($orderData, $_POST['userId'])) {
                echo 'success';
            } else {
                echo 'error';
            }

        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout->disableLayout();
    }
    
    public function getOptionsAction() {               
       
       /* $params = array();
        parse_str($_POST['orderForm'], $params);
        var_dump($params['item']);       
        */
        $product = $this->_product->findUserProductName($this->_getParam('userId'), $this->_getParam('item'));
       
        if (empty($product)) {
            echo 'error';
            $this->_helper->viewRenderer->setNoRender(TRUE);
        } else {   
            $this->view->productId = $product[0]['product_id'];  
            $this->view->options = $this->_product->getProductOptionsById($product[0]['product_id']);
            if (empty($this->view->options)) {
                echo 'nah';
                $this->_helper->viewRenderer->setNoRender(TRUE);
            }
        }
        
        //$product = $this->_product->findProductName($this->_getParam('item'));
        //var_dump($product);      
                      
        $this->_helper->layout->disableLayout();
    }
    public function removeItemAction() {
        //echo '<pre>';
        //var_dump($_POST);
        $order = $this->_orders-> get_rep_order($_POST['userId']);
        $items = array();
        if (!empty($order)) {
            $items = unserialize($order['items']);
        }

        $product = $this->_product->getProductById((int) $_POST['productId']);

        //have options
        if (isset($_POST['options'])) {
            $temp = preg_split('/-/', $_POST['options']);
            $options = array();
            foreach ($temp as $value) {
                if (is_numeric($value)) {
                    $options[] = (int)$value;
                }
            }                              
            // var_dump($this->_items[$product['product_id']]->options);
            if (!empty($items[(int) $_POST['productId']])) { 
                 foreach ($items[(int) $_POST['productId']]->options as $index => $productOptions) {
                    //try to find if any existing options is the same                      
                    $arrayDiff = array_diff($productOptions['values'], $options);
                    if (empty($arrayDiff)) {                        
                        break;
                    }
                }
                             
                $itemQty = $items[(int) $_POST['productId']]->qty - $items[(int) $_POST['productId']]->options[$index]['qty'];
                unset($items[(int) $_POST['productId']]->options[$index]);
                if ($itemQty == 0) {
                   unset($items[(int) $_POST['productId']]); 
                } else if ($itemQty >0) {
                    //still need to recalculate
                    $item = new Application_Model_Item($product,$itemQty, NULL);                       
                    $items[(int)$_POST['productId']]->qty = $itemQty;
                    $items[(int)$_POST['productId']]->price = $item->price;
                    $items[(int)$_POST['productId']]->lineCost = $item->lineCost;                        
                }
            }
        } else {
             unset($items[(int) $_POST['productId']]);
             
        }
        
        $items['subTotal'] = 0;
        foreach ($items as $item) {
            if (is_object($item)) {
                $items['subTotal'] += $item->lineCost;
            }
        }
        if ($items['subTotal'] == 0) {
           $items = array();
        }


        $orderData = array ('submitted_by'=>$this->_auth->firstname. ' '. $this->_auth->lastname, 'items'=> serialize($items),
                   'date_modified' => date("Y-m-d H:i:s"), 'ip'=>$_SERVER['REMOTE_ADDR'] );
        if ($this->_orders->save_internal_order($orderData, $_POST['userId'])) {
                echo 'success';
            } else {
                echo 'Caught Errors';
            }
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout->disableLayout();
    }
    
    public function submitAction() {
    
        $itemsTotal = 0; $return = array();
        $return['error'] = '';
        $items = array();
        if (isset($_POST['userId'])) {
            $order = $this->_orders->get_rep_order($_POST['userId']);            
            if (!empty($order)) {
                $items = unserialize($order['items']);
            }
        }
        
      /*  if (!empty($_POST['item'])) {
          
            foreach ($_POST['item'] as $key=>$name) {
              
                if ($name) {
                    
                    $product = $this->_product->getUserProductPrice($name,(int)$_POST['quantity'][$key],$_POST['userId']);
                  //  var_dump ($_POST['quantity']).'<br>';
                    if($product) {   
                         $items[$key] = new stdClass;
                         $items[$key]->productId = $product['product_id'];
                         $items[$key]->volumn = $product['volumn'];
                         $items[$key]->weight = $product['weight'];
                         $items[$key]->qty = (int)$_POST['quantity'][$key];   
                                                
                       
                         $items[$key]->name = $name;
                         $items[$key]->price = $product['price'];
                        
                         $items[$key]->lineCost = $product['price'] * $items[$key]->qty;
                         $itemsTotal += $product['price'] * $items[$key]->qty;
                         
                    } else {
                         $return['error'] = "Can not find price for product $name. <br>";
                    }
                }                
            }
            $items['subTotal'] = $itemsTotal;
        }*/
        if (empty($items)) {
            $return['error'] .= "Please specificy item(s) and quantity to be ordered.";
        }
       
        if ($return['error'] !='') {
            echo json_encode($return);
            $this->_helper->viewRenderer->setNoRender(TRUE);
            $this->_helper->layout->disableLayout();
            return;
        }        
        
        $address = $this->_orders->getAddressById($_POST['addressId']);   
        
        $data = array(
            'contact_email' => $_POST['contact_email'],
            'contact_phone'=>$_POST['contact_phone'],
            'user_id' => $_POST['userId'],
            'submitted_by' => $this->_auth->id,
            'shipping_firstname'=> $address['firstname'], 
            'shipping_lastname'=> $address['lastname'],
            'shipping_company'=> $address['company'],
            'shipping_address1' => $address['address1'],
            'shipping_address2' => $address['address2'],
            'shipping_city' => $address['city'],
            'shipping_state' => $address['state'],
            'shipping_zipcode' => $address['zipcode'],
            'shipping_country'=> $address['country'],
            'address_id' => $_POST['addressId'],
            'shipping_service' => isset($_POST['service'])?$_POST['service']:'Will Call', 
            'shipping_rate'=> isset($_POST['shippingRate'])?$_POST['shippingRate']:0,
            'tax_rate'=>isset($_POST['shippingRate'])?$_POST['shippingRate']:'',
            'tax_amount'=>isset($_POST['salesTax'])?$_POST['salesTax']:0,
            'tax_location_name'=> isset($_POST['tax_location_name'])?$_POST['tax_location_name']:'',
            'tax_location_code'=>isset($_POST['tax_location_code'])?$_POST['tax_location_code']:'',
            'comment'=> trim($_POST['comment'])
            //'order_status' => 'incomplete',
            //'items'=>  serialize($items)
        );
        
        $data['total'] = $items['subTotal']  + $data['shipping_rate'] + $data['tax_amount'];
        //check if there is incomplete orders
        $orderId = $this->_orders->check_order( $_POST['userId'], $this->_auth->id);            
        if ($orderId) {
            $this->_orders->update($data, $orderId);
        } else {
            $data['date_added'] =  date("Y-m-d H:i:s");
            $orderId = $this->_orders->save($data);
        }
      
     
        $success = 0;
        if ($_POST['payment'] == 'wire' || $_POST['payment'] == 'wu' || $_POST['payment'] == 'mg') {
           $order['payment_method'] = $_POST['payment'];   
           $order['date_modified'] = date("Y-m-d H:i:s");
           $order['order_status'] = 'onhold';
           $success = 1;
        } else {
        
            //capture the fund if CC
            $user =  $this->_users->getUser($_POST['userId']);
            $request = new Application_Service_AuthorizeNetCIM;

            $transaction = new AuthorizeNetTransaction;
            $transaction->amount =  $data['total'];
            $transaction->customerProfileId = $user['profile_id'];
            $transaction->customerPaymentProfileId = $_POST['payment'];
            $transaction->order->invoiceNumber = $orderId;

                
             foreach ($items as $item) {
               if(is_object($item)){
                $lineItem              = new AuthorizeNetLineItem;        
                $lineItem->itemId      = $item->productId;
                $lineItem->name        = substr($item->name, 0, 31); 
                $lineItem->description = substr($item->name, 0, 255); 
                $lineItem->quantity    = $item->qty;
                $lineItem->unitPrice   = $item->price;           
                $transaction->lineItems[] = $lineItem;            
               }
            }
            $response = $request->createCustomerProfileTransaction("AuthCapture", $transaction);                    
            $transactionResponse = explode(',', $response->getTransactionResponse()->response);

            //    if ($transactionResponse[0] == 1) {   
            $order = array('payment_status' => (int) $transactionResponse[0],
                'payment_response' => $transactionResponse[3],
                'payment_transactionId' => $transactionResponse[6],
                'date_modified' => date("Y-m-d H:i:s"),
                'payment_firstname' => $transactionResponse[13],
                'payment_lastname' => $transactionResponse[14],
                'payment_company' => $transactionResponse[15],
                'payment_address1' => $transactionResponse[16],
                'payment_city' => $transactionResponse[17],
                'payment_state' => $transactionResponse[18],
                'payment_zipcode' => $transactionResponse[19],
                'payment_country' => $transactionResponse[20],
                'payment_method' => 'CC'
            );
          
            if ($transactionResponse[0] == 1) { 
                $success = 1;
            } else {
                 $return['error'] .= $transactionResponse[3];               
            }
              $order['order_status'] = $data['total'] < 4000 ? 'processing' : 'onhold';
        }// end paid by CC
        if ($success == 1) {
           // $order['order_status'] = $data['total'] < 4000 ? 'processing' : 'pending';
            $this->_orders->update($order, $orderId);

            foreach ($items as $item) {
                if (is_object($item)) {
                    $data = array('order_id' => $orderId,
                        'product_id' => $item->productId,
                        'name' => $item->name,
                        'quantity' => $item->qty,
                        'price' => $item->price);
                    $this->_orders->save_item($data);
                }

                //save item options
                if (!empty($item->options)) {
                    foreach ($item->options as $option) {
                        $options = array('order_id' => $orderId,
                            'order_product_id' => $item->productId,
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
          
            $return['status'] = 'success';
        }        
        $return['orderId'] = $orderId;
        echo json_encode($return);   
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout->disableLayout();
    }
    public function invoiceAction() {
        $this->view->order = $this->_orders->get_order($this->_getParam('id'));
        $this->view->items = $this->_orders->get_invoice_items($this->_getParam('id'));
        $this->_helper->layout->disableLayout();
    }
    
    public function thankYouAction()
    {
        echo "Order Id:". $this->_getParam('id').'<br>';
    } 
    
}