<?php

class CrmController extends Zend_Controller_Action {

    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
           $this->_helper->redirector('login', 'user');
        }
        $this->_users = new Application_Model_User;        
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        $this->_crms = new Application_Model_Crm;  
        $this->_products = new Application_Model_Product;
    }
    
    public function createUserAction() {
        $country = $this->getRequest()->isPost()?$_POST['country']:'US';        
              
        $form = new Application_Form_Signup($country);
        $form->removeElement('password');
        $form->removeElement('confirm_password');
        $form->removeElement('parentAccountID');
        $form->removeElement('interest');
        $form->removeElement('captcha');
        
        $this->view->form = $form;
        $this->view->type = $this->_getParam('type');
        if ($this->getRequest()->isPost()) {
            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                
               // var_dump($_POST);
                /* if($data['password'] != $data['confirm_password']){
                  $error = "Password and confirm password don't match.";
                  $form->getElement('password')->addError($error);
                  return;
                  }*/
                  
                  if(is_null($data['source'])) {
                      $form->getElement('source')->addError('Please enter lead source.');
                      $hasError = 1;  
                      return;
                  }
                                   
                  if($data['source'] == 'Internet') {
                      $data['source_text'] = trim($_POST['source_internet']);
                  } elseif ($data['source'] == 'Referred') {
                      $data['source_text'] = trim($_POST['source_refer']);
                  } elseif ($data['source'] == 'Social Media') {
                      $data['source_text'] = trim($_POST['source_social']);
                  } elseif ($data['source'] == 'Tradeshow') {
                      $data['source_text'] = trim($_POST['source_tradeshow']);
                  } elseif ($data['source'] == 'Other') {
                      $data['source_text'] = trim($_POST['source_other']);
                  }
                  $data['type'] = ucwords($this->_getParam('type'));
                  $data['password'] = $this->_users->gen_password();
                  
                 // unset($data['confirm_password']);     
                //  $data['imported'] = 'Web Form';
                  $data['username'] = $data['businessname'];
                  $data['created_time'] = date("Y-m-d H:i:s");
                  $data['parent_user'] = $this->_auth->id;
                 // unset($data['parentAccountID']);
                  //var_dump($data);                
                  $insert = $this->_users->save($data);
                  
                if (strstr($insert, 'SQLSTATE[23000]')) {
                    $error = 'User name already taken. Please choose another one.';
                    $form->getElement('email')->addError($error);
                    return;
                }
                //user created successfully
                if ($data['type'] == 'Account') {
                     $conversion = array ('user_id'=> $insert, 'user_type' => 'Account', 
                         'lead_source' => $data['imported'], 'source'=> $data['source'],
                         'created_time' => $data['created_time'], 'parent_id' => $data['parent_user'] , 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname                             
                                        );
                     $this->_users->account_conversion($conversion);            
                }
                //
                //$this->_users->authenticate($data);
                //$this->_helper->redirector('register-success', 'user');
                $this->_redirect('/crm/customer/id/' . $insert);
            }
        }    
      
    }

    public function customerAction() {

        $this->view->reps = $this->_users->getSalesUsers(); 
        
       /* if(!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('signin', 'user');           
        }*/      
        
        if ($this->_getParam('prev') && $this->_getParam('type')) {          
            $user = $this->_users->getPreviousNext($this->_getParam('prev'), $this->_getParam('type'), 'prev');
        } elseif ($this->_getParam('next') && $this->_getParam('type')) {          
            $user = $this->_users->getPreviousNext($this->_getParam('next'), $this->_getParam('type'), 'next');
        } elseif ($this->_getParam('id') ){          
            $user = $this->_users->getUser($this->_getParam('id'));
        }
        //var_dump($user);
        if (!isset($user) || !$user) {
            $user = $this->_users->getLast();
        }
       
        $this->view->type = $user['type'];
        //if admin, you can view everyone, if not, you can only see your own and customer
        if ( $this->_auth->role != 'admin' && $user['type'] == 'Internal' && $this->_getParam('id') !=  $this->_auth->id ) {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('login', 'user');
        }        
        
        $this->view->customerName = $user['firstname'].' '.$user['lastname'];
        //$this->view->customerId = $this->_getParam('id');    
        $this->view->customerId = $user['id'];    
                
        $this->view->headTitle('Beaming White Business Account');
       
    }
    
    public function leadsAction() {        
        $total = $this->_users->getUsersTotal('Lead');
        $this->view->total = $total['total']; 
        $this->view->reps = $this->_users->getSalesUsers();  
        $this->view->potentials = $this->_users->getAccountPotential();
        $this->view->sources = $this->_users->getSource();
        unset($this->view->sources[""]);;
        $this->view->events = $this->_crms->getTodayEvents($this->_auth->id);
        
        if ($this->_getParam('repid')) {
             $this->view->rep = $this->_getParam('repid'); 
        }
        if ($this->_getParam('pid')) {
             $this->view->pid = $this->_getParam('pid'); 
        }
        if ($this->_getParam('source')) {
             $this->view->source = $this->_getParam('source'); 
        }
        if ($this->_getParam('lastAttempt')) {
             $this->view->lastAttempt = $this->_getParam('lastAttempt'); 
        }
    }
    public function getLeadsAction() {               
   
       $rep = $this->_getParam('repid');
       $potential = $this->_getParam('pid');
       $source = $this->_getParam('source');
       $lastAttempt = $this->_getParam('lastAttempt');
                   
       $page = !$this->_getParam('page')?1: $this->_getParam('page');        
       $rows = !$this->_getParam('rows')?1:$this->_getParam('rows');
     
       $offset = $rows * ($page - 1);
       $sort = !$this->_getParam('sort') ? 'created_time' : $this->_getParam('sort');
       $order = !$this->_getParam('order') ? 'DESC' : $this->_getParam('order');
    
       //echo $start; echo $end;        
        $result['rows'] = $this->_users->getUsersPage ('Lead', $sort, $order, $offset, $rows, $rep, $potential, $source, $lastAttempt);
       
        $total = $this->_users->getUsersTotal('Lead', $rep, $potential,$source, $lastAttempt);
        $result['total'] = $total['total'];
        
        echo json_encode($result);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function prospectAction() {        
        $total = $this->_users->getUsersTotal('Prospect');
        $this->view->total = $total['total']; 
        $this->view->reps = $this->_users->getSalesUsers();  
        $this->view->potentials = $this->_users->getAccountPotential();
        $this->view->sources = $this->_users->getSource();
        unset($this->view->sources[""]);
        $this->view->events = $this->_crms->getTodayEvents($this->_auth->id);
        
        if ($this->_getParam('repid')) {
             $this->view->rep = $this->_getParam('repid'); 
        }
        if ($this->_getParam('pid')) {
             $this->view->pid = $this->_getParam('pid'); 
        }
        if ($this->_getParam('source')) {
             $this->view->source = $this->_getParam('source'); 
        }
        if ($this->_getParam('lastAttempt')) {
             $this->view->lastAttempt = $this->_getParam('lastAttempt'); 
        }
        
    }
     public function getProspectAction() {       
         
       $rep = $this->_getParam('repid');
       $potential = $this->_getParam('pid');
       $source = $this->_getParam('source');
       $lastAttempt = $this->_getParam('lastAttempt');
                   
       $page = !$this->_getParam('page')?1: $this->_getParam('page');        
       $rows = !$this->_getParam('rows')?1:$this->_getParam('rows');
     
       $offset = $rows * ($page - 1);
       $sort = !$this->_getParam('sort') ? 'created_time' : $this->_getParam('sort');
       $order = !$this->_getParam('order') ? 'DESC' : $this->_getParam('order');
    
       //echo $start; echo $end;        
        $result['rows'] = $this->_users->getUsersPage ('Prospect', $sort, $order, $offset, $rows, $rep, $potential, $source, $lastAttempt);
       
        $total = $this->_users->getUsersTotal('Prospect', $rep, $potential,$source, $lastAttempt);
        $result['total'] = $total['total'];
        
        echo json_encode($result);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function deleteCustomerAction(){
        
        if(Zend_Auth::getInstance()->getIdentity()->role == 'admin') {
            if ($this->_users->deleteUser($_POST['id'])) {
                echo 'success';
            } else {
                echo 'Unable to delete';
            }
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();        
    }  
    public function accountsAction() {
        $total = $this->_users->getUsersTotal('Account');
        $this->view->total = $total['total']; 
        $this->view->reps = $this->_users->getSalesUsers();
        $this->view->potentials = $this->_users->getAccountPotential();
        $this->view->sources = $this->_users->getSource();
        unset($this->view->sources[""]);
        $this->view->events = $this->_crms->getTodayEvents($this->_auth->id);
        
        if ($this->_getParam('repid')) {
             $this->view->rep = $this->_getParam('repid'); 
        }  
        if ($this->_getParam('pid')) {
             $this->view->pid = $this->_getParam('pid'); 
        }
        if ($this->_getParam('source')) {
             $this->view->source = $this->_getParam('source'); 
        }
        if ($this->_getParam('lastAttempt')) {
             $this->view->lastAttempt = $this->_getParam('lastAttempt'); 
        }
    }
    public function getAccountsAction() {       
        
       $rep = $this->_getParam('repid'); 
       $potential = $this->_getParam('pid');
       $source = $this->_getParam('source');
       $lastAttempt = $this->_getParam('lastAttempt');
       $page = !$this->_getParam('page')?1: $this->_getParam('page');        
       $rows = !$this->_getParam('rows')?1:$this->_getParam('rows');
     
       $offset = $rows * ($page - 1);
       $sort = !$this->_getParam('sort') ? 'created_time' : $this->_getParam('sort');
       $order = !$this->_getParam('order') ? 'DESC' : $this->_getParam('order');
        
       // echo $start; echo $end;        
        $result['rows'] = $this->_users->getUsersPage ('Account', $sort, $order, $offset, $rows, $rep,$potential,$source,$lastAttempt);        
        $total = $this->_users->getUsersTotal('Account', $rep,$potential,$source,$lastAttempt);
        $result['total'] = $total['total'];
        echo json_encode($result);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function addressBookAction() {
        $this->view->billingAddress = $this->_users->getBillingAddress($this->_getParam('id'));
        $this->view->addresses = $this->_users->getAddresses($this->_getParam('id'));
        $this->view->userId = $this->_getParam('id');
        //echo '<pre>';
        //var_dump($this->view->addresses);
        $this->_helper->layout()->disableLayout();
    }    
    public function deleteAddressAction() {
        $this->_users->deleteAddress($this->_getParam('id'));
        $this->_helper->layout()->disableLayout();  
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
     public function paymentAction() {        
        $user = $this->_users->getUser($this->_getParam('id'));
        if ($user['payment_option'] != 'card') {
            $this->view->alert = "This Customer Payment Option is {$user['payment_option']}";
        }
        $this->view->profiles = $this->_users->getPaymentProfiles($this->_getParam('id'));       
        $this->view->userId = $this->_getParam('id');
        $this->_helper->layout->disableLayout();        
    }
     public function deleteCardAction() {
        //$this->_users->deleteAddress($this->_getParam('id'));
         //first check if the card belong to this user
        $profile = $this->_users->getPaymentProfileById($this->_getParam('id'));
        //delete from authnet
        $request = new Application_Service_AuthorizeNetCIM;
        $paymentProfile = $request->deleteCustomerPaymentProfile((int)$profile['profile_id'],(int)$profile['payment_profile_id']);
        //flag it in the database
        $this->_users->deletePaymentProfile((int)$profile['user_profile_id']);               
      
                  
        $this->_helper->layout()->disableLayout();  
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function addCardAction() {
        
    if (!$this->_auth) {
        $this->_helper->redirector('login'); 
    }
    //var_dump($_POST);
    
    
    $user = $this->_users->getUser($this->_getParam('id'));
   
    $form = new Application_Form_Card($user['country']);
    $form->removeElement('submit');
    $form->setAction("/biz/crm/add-card/{$this->_getParam('id')}");    
    $form->setAttrib('id', 'card') ;
    $form->populate($user);    
    $this->view->form = $form;
    
    
    $request = new Application_Service_AuthorizeNetCIM;
    
    //create user profile if not existing
    //to do: add CC user condition
   // var_dump($this->_auth->profile_id);
    if ($user['profile_id'] == 0 || is_null($user['profile_id'])) {        
        $customerProfile                  = (object)array();
        $customerProfile->merchantCustomerId= $user['id'];       
        $response = $request->createCustomerProfile($customerProfile); 
        if ($response->isOk()) {
            $user['profile_id'] = $response->getCustomerProfileId();           
        }
        //update      
        $data= array('profile_id' =>  $user['profile_id']);        
        $this->_users->editUser($data, $user['id']); 
    }    

    if ($this->getRequest()->isPost()) { 
       
            if ($form->isValid($_POST))  {
                                
                //create customer payment profile
                $data = $form->getValues();
                
                $cardType = $this->_users->getCardType($data['number']);
                // var_dump($cardType);
                //die();
                //Reject JCB and dinner
                if ($cardType == 'Diners Club' || $cardType == 'JCB') {
                    $error = "Sorry we don't accept Diners Club or JCB card.";
                    $form->getElement('number')->addError($error);                   
                    echo $error;
                    return;
                }
                
                //$customerProfile                  = (object)array();
                $customerProfile    = new stdClass();               
                $billTo = new stdClass();
                $payment = new stdClass();
                $payment->creditCard = new stdClass();
                
                $billTo->firstName = $data['firstName'];
                $billTo->lastName = $data['lastName'];
                $billTo->address = $data['address1'].''.$data['address2'];
                $billTo->city = $data['city'];
                $billTo->state = $data['state'];
                $billTo->zip = $data['zip'];
                $billTo->country = $data['country'];
                $billTo->phoneNumber = $data['phone'];
                $billTo->faxNumber = '123';                
                $customerProfile->billTo[] = $billTo; 
              
                $payment->creditCard->cardNumber = $data['number'];
                $payment->creditCard->expirationDate = $data['year'].'-'.$data['month'];                
                $customerProfile->payment[] = $payment;              
              
                $response = $request->createCustomerPaymentProfile($user['profile_id'], $customerProfile);  
                              
                if ($response->isOk()) {
                                                          
                    $this->customerPaymentProfileId = $response->getPaymentProfileId();                    
                   
                    //save it into the database
                    $profile = array('user_id' => $user['id'], 
                        'profile_id' => $user['profile_id'],
                        'type' => $cardType,
                        'payment_profile_id' => $this->customerPaymentProfileId,
                        'month' => $data['month'],
                        'year' => $data['year']
                        );      
                   
                   if ($this->_users->saveProfile ($profile)) {                       
                        echo 'success';
                        $this->_helper->viewRenderer->setNoRender(true);
                   }
                }               
            } else {
                
                 if ($this->getRequest()->isXmlHttpRequest()) {                  
                     $this->_helper->layout->disableLayout();
                     echo "Please Enter all required fields";   
                     
                     $this->_helper->viewRenderer->setNoRender(true);                     
                }               
            }
         }
       //$this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();                
    }
       
    public function orderHistoryAction()
    {        
       $this->view->userId = $this->_getParam('id');
       $this->_helper->layout->disableLayout();            
    }
     public function orderHistoryDataAction()
    {               
        $perPage = 10;
        $count = $this->_users->getOrderTotal($this->_getParam('id'));
        $this->view->totalPages = ceil($count['total']/$perPage);        
        $this->view->page = $this->getParam('page');        
        
        $from = ($this->getParam('page') - 1) *$perPage;
        
        $this->view->orders = $this->_users->getOrders($this->_getParam('id'), $from, $perPage);
        $this->_helper->layout->disableLayout();
    }
    
    public function productAction()
    {
        $this->view->userId = $this->_getParam('id');    
        $this->view->products = $this->_products->getAllProduct();
        
        if ($this->_getParam('id')) {
            $userProducts = $this->_products->user_product($this->_getParam('id'));             
            foreach ($userProducts as $product) {
                $userProduct[]= $product['product_id'];
            }
            $this->view->userProduct = isset($userProduct)?$userProduct:array();
        }
       
        if($this->getRequest()->isPost()) {   
            if(!empty($_POST['selectedProduct'])) {
                //first selected
                foreach ($_POST['selectedProduct'] as $productId) {                   
                    $pid = $this->_products->user_product_price($_POST['userId'], $productId);
                   // echo 'Added ' . $pid.'<br>';
                }
                $product = "'" . implode("','", $_POST['selectedProduct']) . "'";
                // echo $product;                 
                //need to remove those that's not selected
                 $this->_products->user_unselect_product($_POST['userId'], $product);                       
            } else {
                //remove everything
                $this->_products->delete_all_user_products($_POST['userId']);
            }
            echo 'Updated';
            $this->_helper->viewRenderer->setNoRender(true);
        }
        $this->_helper->layout->disableLayout();
    }
    
    public function priceAction() {
        $this->view->prices = $this->_products->getUserProduct($this->_getParam('id'));   
        $this->_helper->layout->disableLayout();
    }
    public function updatePriceAction()
    {       
        $result = $this->_products->updateProductPrice($_POST['product_price_id'], $_POST['price']);
        echo $result; 
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    /*public function getProductAction()
    {
        $products = $this->_products->getUserProduct($this->_getParam('id'));        
        echo json_encode( $products);       
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    public function updatePriceAction()
    {       
        $this->_products->updateProductPrice($_POST, $_POST['product_price_id']);
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    public function deletePriceAction()
    {
        $data['active'] = 0;
        $this->_products->updateProductPrice($data, $_POST['id']);
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    
    
    public function savePriceAction()
    {
        unset($_POST['isNewRecord']);
        $data = $_POST;
        $data['user_id'] = (int)$this->_getParam('id');
        
        $this->_products->insertProductPrice($data);
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
*/
  /*  public function accountsAction() {               
        
        if(!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('index', 'user');           
        }
        // get pending users
        $this->view->pendingUsers = $this->_users->getUsers(Zend_Auth::getInstance()->getIdentity()->id, 'Lead');
       
        // get representative users        
        $this->view->myUsers = $this->_users->getChildAccounts(Zend_Auth::getInstance()->getIdentity()->id, 'active');
        //get all active users
        $this->view->activeUsers = $this->_users->getUsers(Zend_Auth::getInstance()->getIdentity()->id, 'active');
              
    }*/
  
    public function notesAction() {
        if ($this->getRequest()->isPost()) {           
            $auth = Zend_Auth::getInstance()->getIdentity();
            if (trim($_POST['notes']) != '' && trim($_POST['notes']) != 'Add a note') {
                $data = array ('user_id' =>(int)$_POST['userId'],
                    'type' => 'note',
                    'notes' => trim($_POST['notes']),
                    'author' => $auth->firstname.' '.$auth->lastname
                    );      
                $this->_users->savenotes($data);               
                
                //if ajax call
                if ($this->getRequest()->isXmlHttpRequest()) {                    
                    echo '<br>'.date('m/d/y g:i a') . ' '. $data['author'].'<br>'.$data['notes'].'<br>';                    
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);                    
                }
            }
        }        
    }
    public function passwordAction() {
        if ($this->getRequest()->isPost()) {                        
             $tempPW = $this->_users->gen_password();                                
             $update = $this->_users->setPasswordByEmail(trim($_POST['email']), $tempPW);                
                if($update) {   
                    $user = $this->_users->getUserByEmail(trim($_POST['email']));
                    $reset = array('user_id' => $user['id'],
                            'email'=> $user['email'],
                            'requested_time' => date("Y-m-d H:i:s"),
                            'ip' => $_SERVER['REMOTE_ADDR']
                             );                    
                    //save to flag them to change
                    $this->_users->password_reset($reset);
                    $mail = new Zend_Mail();
                    $name = $user['firstname'].' '.$user['lastname'];
                    $message = "Dear $name,<br><br>
                    You have requested to have your password reset. <br><br>
                    Please use this temporary password to <a href='https://www.beamingwhite.com/biz/user/login'>login </a> to your account: $tempPW<br><br>
                    You will be prompted to change your password once you login using your temporary password. <br> 
                    <br><br>
                    Thank you, <br>
                    Beaming White <br>
                    1 (866) 944-8315";

                    //$message = "Email sent, Your temparary password is $tempPW. Please visit <a href = '/biz/user/password'> here </a>to reset your password";
                    $mail->setBodyHTML($message);
                    $mail->setFrom('customer.info@beamingwhite.com', 'beamingwhite.com');
                    $mail->addTo($user['email'], $name);
                    $mail->setSubject('Information regarding your account with Beaming White');
                    $mail->send();
                    
                    echo $tempPW;
                }
          
        }        
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true); 
    }
    public function leadAttemptAction() {
       if ($this->getRequest()->isPost()) {           
            $auth = Zend_Auth::getInstance()->getIdentity();
            if (trim($_POST['leadAttempt']) != '') {
                $data = array ('user_id' =>(int)$_POST['userId'],
                    'type' => 'attempt',
                    'notes' => trim($_POST['leadAttempt']),
                    'author' => $auth->firstname.' '.$auth->lastname
                    );      
                $this->_users->savenotes($data);               
                
                //if ajax call
                if ($this->getRequest()->isXmlHttpRequest()) {                    
                    echo '<br>'.date('m/d/y g:i a') . ' '. $data['author'].'<br>'.$data['notes'].'<br>';                    
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);                    
                }
            }
        } 
         $this->_helper->layout()->disableLayout();
         $this->_helper->viewRenderer->setNoRender(true); 
    }
        
    public function edituserAction()
    {       
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $id = $_POST['pk'];
        $data = array($_POST['name'] => $_POST['value']);
        $user = $this->_users->getUser($id);
        if(isset($data['type'])) {
            if (($user['type'] == 'Lead' || $user['type'] == 'Prospect') && $data['type'] == 'Account') {
                //track conversion
                $conversion = array ('user_id'=> $id, 'user_type' => $user['type'], 'source' => $user['source'],'lead_source' => $user['imported'],
                        'created_time' => $user['created_time'], 'parent_id' => $user['parent_user'], 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname
                      );
                $this->_users->account_conversion($conversion);
            }
        }
                        
        if ($_POST['name'] == 'country' || $_POST['name'] == 'billingcountry') {
            foreach ($this->_users->getRegions($_POST['value']) as $state) {
                 $this->view->states .= ' {value:"'.$state['code'].'", text: "'.$state['name'].'"},'; 
            }
            $return['currentStates'] = '['.$this->view->states.']';
        }
        
        //billing address is saved in a different table, Hell QB
        $billing = array('billingfirstname','billinglastname','billingcompany', 'billingaddress1', 'billingaddress2', 'billingcity', 'billingstate', 'billingcountry', 'billingzipcode');
        if (in_array($_POST['name'], $billing)) {
             $name = preg_split('/billing/', $_POST['name']);
             $data = array($name[1] => $_POST['value']);
             $update = $this->_users->saveBillingAddress($data, $id);             
        } elseif ($_POST['name'] == 'euTraining') {
            $update = $this->_users->setEuTraining($id, $_POST['value']);
        } else {            
            $update = $this->_users->editUser($data, $id);
        }
        
        if ($update) {           
             if (strstr($update, 'SQLSTATE[23000]')) {
                 $return['success'] = false; 
                 $return['message'] = 'Email existing';
             }else {
                 $return['success'] = true;
                 if($_POST['name'] == 'potential') {
                     $return['potential'] = $_POST['value'];  
                     $return['prev'] = $user['potential'];  
                 }
             }        
        } else {
            $return['success'] = false;
            $return['message'] = 'Update failed';
        }        
        echo json_encode($return); 
      
        
    }  
     
    public function viewAction() {
         $this->view->user = $this->_users->getUser($this->_getParam('id'));
         $this->view->euTraining = $this->_users->getEuTraining($this->_getParam('id'));
         $this->view->user['euTraining'] =  $this->view->euTraining?'1':'0';
                 
         $this->view->attempts = $this->_users->getNotes($this->_getParam('id'), 'attempt');
         $this->view->notes = $this->_users->getNotes($this->_getParam('id'), 'note');
         $this->view->followup = $this->_crms->getFollowup($this->_getParam('id'));
         
         $salesReps = $this->_users->getSalesUsers();      
         $this->view->userRep = $this->_users->getSalesRep($this->_getParam('id'));
         $this->view->auth = 0;
         if (($this->view->user['parent_user'] ==1) || Zend_Auth::getInstance()->getIdentity()->role =='admin') {
             $this->view->auth = 1;
         }
         $this->view->billingAddress = $this->_users->getBillingAddress($this->_getParam('id'));
        
         foreach ($this->_users->getLeadSource() as $key => $value) {
             $this->view->leadSource .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_users->getCountries() as $country) {
             $this->view->countries .= ' {value:"'.$country['iso_code_2'].'", text: "'.$country['name'].'"},'; 
         }
         //business address
         foreach ($this->_users->getRegions($this->view->user['country']) as $state) {
             $this->view->states .= ' {value:"'.$state['code'].'", text: "'.$state['name'].'"},'; 
         }
         //billing address
         foreach ($this->_users->getRegions($this->view->billingAddress['country']) as $state) {
             $this->view->billingStates .= ' {value:"'.$state['code'].'", text: "'.$state['name'].'"},'; 
         }
         
         foreach ($this->_users->getAccountType() as $key => $value) {
             $this->view->accountType .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_users->getAccountStatus() as $key => $value) {
             $this->view->accountStatus .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_users->getAccountPotential() as $key => $value) {
             $this->view->accountPotential .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_users->getBusinessType() as $key => $value) {             
             $this->view->businessType .= ' {value:"'.$key.'", text: "'.$value.'"},';              
         }
         foreach ($this->_users->getCustomerType() as $key => $value) {
             $this->view->customerType .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_users->getPaymentOptions() as $key => $value) {
             $this->view->paymentOptions .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_users->getSoldByOptions() as $key => $value) {
             $this->view->soldbyOptions .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
             
         $this->view->euTrainingOption = ' {value:"1", text: "Yes"}, {value:"0", text: "No"}'; 
         
         
         foreach ($salesReps as $rep) {
             $this->view->salesReps .= ' {value:"'.$rep['id'].'", text: "'.$rep['name'].'"},'; 
         }
         $this->_db = Zend_Registry::get('db');
         $countryCode = $this->_db->fetchRow("select country_id from country WHERE iso_code_2 =  '{$this->view->user['country']}'");
         if (!$countryCode && $this->view->user['country'] != '') {
             $this->view->countryName = $this->view->user['country'];
         }
         
         if ($this->_request->isXmlHttpRequest()){
            $this->_helper->layout->disableLayout();
         }
        
   }
   public function followupAction()
   {            
      $followupTime =  date('Y-m-d H:i', strtotime($_POST['followup']));    
      $customer = $this->_users->getUser($_POST['userId']);
      //only the rep can schedule the followup.
      if($this->_auth->id != $customer['parent_user'] && $this->_auth->role != 'admin') {
          echo 'You can only schedule the follow-up if you are the account rep.';
          exit();
      }            
      //first check if there is a previous followup
      $event = $this->_crms->getFollowup($_POST['userId']);
            
      if($event) {
        if(isset($_POST['followup']) && $_POST['followup'] == '') {
          //remove
            $this->_crms->deleteFollowup($_POST['userId']);
            echo 'Follow-up removed.';
        } else {
            $data = array('user_id' => $customer['parent_user'], 'start' => $followupTime, 
                'end' => date('Y-m-d H:i', strtotime('+15 minutes', strtotime($_POST['followup']))),
                'title' =>"Follow-up for {$customer['firstname']} {$customer['lastname']}", 
                'email_alert' => NULL,'popup_alert'=>NULL,  'update_time'=>date('Y-m-d H:i:s')
                );  
            $this->_crms->updateEvent($data, $event['event_id'], 'followUp');
            echo 'success';
        }
            
      } else {
        $data = array('user_id' => $customer['parent_user'], 'customer_id'=>$_POST['userId'], 
            'start' => $followupTime, 'end' => date('Y-m-d H:i', strtotime('+15 minutes', strtotime($_POST['followup']))),
            'all_day'=>0, 'title' =>"Follow-up for {$customer['firstname']} {$customer['lastname']}", 'update_time'=>date('Y-m-d H:i:s') );
        $this->_crms->createEvent($data);
        echo 'success';
      }
      
      $this->_helper->layout()->disableLayout();
      $this->_helper->viewRenderer->setNoRender(true);           
   }
     
   public function activityAction()
   {
       //get next 30 days events
       $this->view->events = $this->_crms->getUpcomingEvents($this->_auth->id);
       
   }
   public function createEventAction()
   {    
    $data = array('user_id' => $this->_auth->id,
                  'start' => trim($_POST['start']),
                  'end' =>trim($_POST['end']),
                  'title' => trim($_POST['title']), 
                  'description'=>trim($_POST['description']),
                  'update_time'=> date("Y-m-d H:i:s"),
                  'all_day' => $_POST['all_day'],
                  'public' => $_POST['public']);
    if ($_POST['all_day'] == 1) {
        $endParts = preg_split("/[\s]+/", $_POST['end']);
        $data['end'] = $endParts[0].' 23:59:59';
    }
    $error = '';
    
    if(isset($data['start']) && strtotime(trim($data['start'])) <= strtotime(date("Y-m-d H:i:s"))){
           $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and the time must be in the future '. '<br>';
    }
    if(isset($data['end']) && strtotime(trim($data['end'])) <= strtotime(trim($data['start']))){
           $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and end time must be later than start time '. '<br>';
    }    
    if ($error !='') {      
        echo "<div class='ui-widget'>
            <div class='ui-state-error ui-corner-all' style='padding: 0 .1em;'>
                <p>
                    <strong>$error</strong>
                </p>
            </div>
        </div>";
    } else {
        if ($this->_crms->createEvent($data)) {
            echo "success";
        }
    }
    
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender(TRUE);
        
   }   
   
   public function updateEventAction()
   {      
       $this->view->event = $this->_crms->getEvent($this->_getParam('id'));  
       
       if ($this->getRequest()->isPost()) {          
           $data = array('start' => trim($_POST['start']), 'end' => trim($_POST['end']), 
            'user_id'=>$this->_auth->id,           
            'update_time'=>date("Y-m-d H:i:s")
           );
           
           
           if(isset($_POST['public'])) {
               $data['public'] = $_POST['public'];
           }
           if(isset($_POST['description'])) {
               $data['description'] = trim($_POST['description']);
           }
           if(isset($_POST['title'])) {
               $data['title'] = trim($_POST['title']);
           }
           
           if(isset($_POST['all_day'])) {
               $data['all_day'] = $_POST['all_day'];
               if ($_POST['all_day'] == 1) {
                   $endParts = preg_split("/[\s]+/", $_POST['end']);
                   $data['end'] = $endParts[0].' 23:59:59';
               }
           }           
            $error = '';

            if(isset($data['start']) && strtotime(trim($data['start'])) < strtotime(date("Y-m-d H:i:s"))){
                   $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and the time must be in the future '. '<br>';
            }
            if(isset($data['end']) && strtotime(trim($data['end'])) < strtotime(trim($data['start']))){
                   $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and end time must be later than start time '. '<br>';
            }
            if (!$error) {
                
                $update = $this->_crms->updateEvent($data, $_POST['event_id']);
                if ($update == 'Permission Error'){              
                    $error .=  'You can not change the event unless you are the author. <br>';
                } elseif ($update == 1) {           
                    echo 'success';
                }
            }
            if ($error !='') {      
                echo "<div class='ui-widget'>
                <div class='ui-state-error ui-corner-all' style='padding: 0 .1em;'>
                    <p>
                        <strong>$error</strong>
                    </p>
                </div>
                 </div>";
             }
           
            $this->_helper->viewRenderer->setNoRender(TRUE);    
       }
       $this->_helper->layout->disableLayout();       
   }
   
   public function removeEventAction()
   {
     //  echo $_POST['event_id'];
       $data = array('user_id' => $this->_auth->id,
            'update_time' => date("Y-m-d H:i:s"), 'active' => 0
        );       
       $update = $this->_crms->updateEvent($data, $_POST['event_id']);
       if ($update == 'Permission Error') {
            $error = 'You can not remove the event unless you are the author. <br>';
             if ($error !='') {      
                echo "<div class='ui-widget'>
                <div class='ui-state-error ui-corner-all' style='padding: 0 .1em;'>
                    <p>
                        <strong>$error</strong>
                    </p>
                </div>
                 </div>";
             }
           
       } elseif ($update == 1) {
            echo 'success';
       }
       $this->_helper->viewRenderer->setNoRender(TRUE);
       $this->_helper->layout->disableLayout();
    }
     
   public function getActivityAction()
   {
       $year = date('Y');
       $month = date('m');
       $events = $this->_crms->getEvents($this->_auth->id);
       echo json_encode($events);
      // echo '[{"id":"1","start":"2014-02-28 12:00:00","end":"2014-02-28 13:00:00","allDay":false,"title":"barbecue"},{"id":"3","start":"2014-02-22 01:00:00","end":"2014-02-22 01:30:00","all_day":"0","title":"rest"},{"id":"4","start":"2014-02-22 07:30:00","end":"2014-02-22 08:00:00","all_day":"0","title":"get up"},{"id":"6","start":"2014-02-22 07:00:00","end":"2014-02-22 07:30:00","all_day":"0","title":"abc"},{"id":"7","start":"2014-02-22 08:00:00","end":"2014-02-22 08:30:00","all_day":"0","title":"xyz"},{"id":"8","start":"2014-03-06 00:00:00","end":"2014-03-06 23:59:59","all_day":"1","title":"another day"}]';
	/*echo json_encode(array(
	
		array(
			'id' => 111,
			'title' => "Event1",
			'start' => "$year-$month-10",
		//	'url' => "http://yahoo.com/"
		),
		
		array(
			'id' => 222,
			'title' => "Event2",
			'start' => "$year-$month-20",
			'end' => "$year-$month-22",
		//	'url' => "http://yahoo.com/"
		),
               array(
			'id' => 333,
			'title' => "Event3",
			'start' => "$year-$month-03 08:00:00",
                        'end'   =>   "$year-$month-03 10:00:00",
                        'allDay'=> false
		//	'url' => "http://yahoo.com/"
		)
	
	));*/
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);        
   }
   
   public function searchAction()
   {
      //  $this->_helper->layout->disableLayout();
   }
   
   public function searchResultAction()
   {       
       $this->view->users = $this->_crms->findUsers($this->_getParam('category'), $this->_getParam('query'));
       //var_dump($this->view->users);
       // $this->_helper->viewRenderer->setNoRender(TRUE);      
       //die();        
   }   
}
