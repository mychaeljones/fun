<?php

class UserController extends Zend_Controller_Action {

    public function init() {
        $this->_users = new Application_Model_User;
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        $this->_orders = new Application_Model_Order();
        $this->_crms = new Application_Model_Crm;
    }

    public function indexAction() {
        if(!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role !='customer') {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('login', 'user');           
        }
        $this->view->headTitle('Beaming White Business Account');
        //die();       
    }
 
    /*public function myaccountsAction(){
        
        if(!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->type =='customer') {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('index', 'user');           
        }
        // get pending users        
        $this->view->users = $this->_users->getChildAccounts(Zend_Auth::getInstance()->getIdentity()->id, 'active');
    }*/
    
    public function login2Action() {        

        $this->view->headTitle('Beamingn White Business Account Log In');
        $this->view->headLink()->appendStylesheet('/biz/public/bootstrap/css/bootstrap.min.css')
                 ->appendStylesheet('/biz/public/bootstrap/css/bootstrapValidator.min.css')
                 ->appendStylesheet('/biz/public/bootstrap/css/bootstrap-theme.css');     
        
        // $layout->getView()->headScript()->appendScript('/javascript/form.js', 'text/javascript');
        // $this->view->headScript()->appendFile('/path/to/file.js');

        if(Zend_Auth::getInstance()->hasIdentity()) {
             Zend_Auth::getInstance()->clearIdentity();
        }
      //  $form = new Application_Form_Login();
        //$this->view->form = $form;
        $this->view->flashMessage = $this->_helper->flashMessenger->getMessages();
	        
        if ($this->getRequest()->isPost()) {
           // if ($form->isValid($_POST)) {
                //$data = $form->getValues();
                $data['email'] = trim($_POST['email']);
                $data['password'] = trim($_POST['password']);
                $auth = $this->_users->authenticate($data);
                if ($auth) {                    
                    //check if user needs to reset their password                    
                    $check = $this->_users->check_reset(Zend_Auth::getInstance()->getIdentity()->id);                   
                    // We're authenticated! Redirect to the home page
                    if (Zend_Auth::getInstance()->getIdentity()->role =='customer') { 
                        $cart = new Application_Model_Cart;
                        $cart->loadPreviousItems();
                        if ($check == 0) {                           
                            $this->_helper->redirector('password', 'user');
                        }  
                        $this->_helper->redirector('index', 'user');                        
                    } else {
                        if ($check == 0) {                           
                            $this->_helper->redirector('password', 'user');
                        }
                        $this->_helper->redirector('leads', 'crm');
                    }
                } else {
                    $this->view->message = 'Invalid crediential, please try again.';
                }
            //}
        }
        
    }
       
    public function loginAction() {

        /*if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {            
        } else {
           $this->_redirect('https://beamingwhite.com/biz/user/login'); 
        }*/       
        if(Zend_Auth::getInstance()->hasIdentity()) {
             Zend_Auth::getInstance()->clearIdentity();
        }
        $form = new Application_Form_Login();
        $this->view->form = $form;
        $this->view->flashMessage = $this->_helper->flashMessenger->getMessages();
	        
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $auth = $this->_users->authenticate($data);
                if ($auth) {                    
                    //check if user needs to reset their password                    
                    $check = $this->_users->check_reset(Zend_Auth::getInstance()->getIdentity()->id);                   
                    // We're authenticated! Redirect to the home page
                    $authentacation = Zend_Auth::getInstance()->getIdentity();
                    if ($authentacation->role =='customer') { 
                        $cart = new Application_Model_Cart;
                        $cart->loadPreviousItems();
                        if ($check == 0) {                           
                            $this->_helper->redirector('password', 'user');
                        } 
                        //01/23/2015 only allow EU training to log in even if 'active' user know the password
                        if($this->_users->getEuTraining(Zend_Auth::getInstance()->getIdentity()->id)) {
                            $this->_helper->redirector('index', 'training');
                        } else {
                             Zend_Auth::getInstance()->clearIdentity();
                             $this->view->message = "You'll be able to login in the near future.";
                        }                        
                    } else {
                      /* if ($check == 0) {                           
                            $this->_helper->redirector('password', 'user');
                       }
                       if($authentacation->role == 'sales') {
                           $this->_helper->redirector->gotoUrl("/crm/leads?repid={$authentacation->id}&pid=");
                       } else {
                           $this->_helper->redirector('leads', 'crm');
                       }*/
                        $this->_helper->redirector->gotoUrl("http://beamingwhite.mx/user/login");
                    }
                } else {
                    $this->view->message = 'Invalid crediential, please try again.';
                }
            }
        }
    }
  
    public function edituserAction()
    {  
        if(!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role !='customer') {         
            return;       
        }
        $id = $_POST['pk'];
        $data = array($_POST['name'] => $_POST['value']);
        $update = $this->_users->editUser($data, $id);
      //  $update = $this->_db->update('user', $data, $this->_db->quoteInto("id = ?", $id));
        if ($update) {           
             if (strstr($update, 'SQLSTATE[23000]')) {
                 $return['success'] = false; 
                 $return['message'] = 'Email existing';
             }else {
                 $return['success'] = true;
             }            
        } else {
            $return['success'] = false;
            $return['message'] = 'Update failed';
        }        
        echo json_encode($return); 
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
    }
        

  /*  public function editAction() { 
                
        $form = new Application_Form_Signup();
        $form->removeElement('confirm_password');        
        $form->password->renderPassword = true; 
        
        if ($this->_getParam('id')) {
            $user = $this->_users->getUser($this->_getParam('id'));
            $user['parentAccountID'] = $this->_users->getSalesRep($this->_getParam('id'));
            $user['parentAccountID'] =  $user['parentAccountID']['parent_user'];
        } elseif($_POST['id']) {
            $user = $this->_users->getUser($form->getValue('id'));
            $user['parentAccountID'] = $this->_users->getSalesRep($this->_getParam('id'));
            $user['parentAccountID'] =  $user['parentAccountID']['parent_user'];
        } else {
            $this->_helper->redirector('index', 'user');
        }
       // var_dump($user);
        
       
        $form->populate($user);
       
        $form->addDisplayGroup(array('firstname', 'lastname', 'contactphone', 'email', 'status', 'parentAccountID', 'password'), 'contact', array('legend' => 'Business Registration Contact Information'));

        $this->view->form = $form;
        $this->view->userId = $this->_getParam('id');
        if ($this->getRequest()->isPost()) {
            if (!$form->isValid($_POST)) {
                # Invalid form submit -- try again
                $this->view->message  = 'invalid data';
                $this->view->form = $form;
                return;
            }
            try {

                unset($_POST['submit']);
              
                $updateUser = $this->_users->editUser($_POST, $_POST['id']);
               
                if (strstr($updateUser, 'SQLSTATE[23000]')) {
                    $error = 'The user already exists in our system, please update the existing one.';
                    $form->getElement('title')->addError($error);
                    return;
                }
                if($updateUser == 1) {
                    $this->view->message = 'Account updated successfully!';
                    //$this->_helper->getHelper('Redirector')->gotoUrl("user/edit/id/{$this->_getParam('id')}");
                   // return;
                }                
            } catch (Exception $e) {
                $this->view->message = $e->getMessage();
            }
        }
    }*/
    public function profileAction() {        
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }
    }
    
    public function myaccountAction() {
        if($auth = Zend_Auth::getInstance()->getIdentity() ) { 
            
            $user = $this->_users->getUser($auth->id);  
          
            if ($user['source'] == 'Referred') $user['source_refer'] = $user['source_text'];
            
            
            $form = new Application_Form_Signup($user['country']);            
            $form->removeElement('parentAccountID');      
            $form->removeElement('status');
            $form->removeElement('confirm_password');
            $form->password->renderPassword = true; 
            
            $form->populate($user);       
            $this->view->form = $form;
            if ($this->getRequest()->isPost()) {
            if (!$form->isValid($_POST)) {
                # Invalid form submit -- try again
                $this->view->message  = 'invalid data';
                $this->view->form = $form;
                return;
            }
            try {
                 if($_POST['source'] == 'Internet') {
                      $_POST['source_text'] = trim($_POST['source_internet']); 
                  } elseif ($_POST['source'] == 'Referred') {
                      $_POST['source_text'] = trim($_POST['source_refer']);                      
                  } elseif ($_POST['source'] == 'Tradeshow') {
                      $_POST['source_text'] = trim($_POST['source_tradeshow']);                      
                  } elseif ($_POST['source'] == 'Other') {
                      $_POST['source_text'] = trim($_POST['source_other']);                        
                  }
                  unset($_POST['source_internet']);
                  unset($_POST['source_refer']);
                  unset($_POST['source_tradeshow']);
                  unset($_POST['source_other']);
                  unset($_POST['submit']);
              
                $updateUser = $this->_users->editUser($_POST, $_POST['id']);
               
                if (strstr($updateUser, 'SQLSTATE[23000]')) {
                    $error = 'The user already exists in our system.';
                    $form->getElement('email')->addError($error);
                    return;
                }
                if($updateUser == 1) {
                    $this->view->message = 'Account updated successfully!';
                    //$this->_helper->getHelper('Redirector')->gotoUrl("user/edit/id/{$this->_getParam('id')}");
                   // return;
                }                
            } catch (Exception $e) {
                $this->view->message = $e->getMessage();
            }
            }//if post
            
        } else {
            $this->_helper->redirector('login', 'user');
        }
       
    }
    public function deletePaymentAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }

        //first check if the card belong to this user
        $profile = $this->_users->getPaymentProfile($this->_auth->id, $this->_getParam('id'));
        if (empty($profile))
            $this->_helper->redirector('wallet');      
        
        //delete from authnet
        $request = new Application_Service_AuthorizeNetCIM;
        $paymentProfile = $request->deleteCustomerPaymentProfile((int)$profile['profile_id'],(int)$profile['payment_profile_id']);
        //flag it in the database
        $this->_users->deletePaymentProfile((int)$profile['user_profile_id']);               
        $this->_helper->redirector('wallet');
    }
   /* public function deletecardAction() {
         if (!$this->_auth) {
            $this->_helper->redirector('login');
        }

        //first check if the card belong to this user
        $check = $this->_users->getCard($this->_auth->id, $this->_getParam('id'));
        if (empty($check))
            $this->_helper->redirector('wallet');        
        $this->_users->deleteCard($this->_getParam('id'));
        $this->_helper->redirector('wallet');         
    }*/
    
    public function editPaymentProfileAction()
    {
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }     
        if ($this->_getParam('from') == 'crm') {
              //$form->setAction("/biz/user/edit-payment-profile/id/{$this->_getParam('id')}/from/crm");
            $profile = $this->_users->getPaymentProfileById($this->_getParam('id'));
        } else {
            $profile = $this->_users->getPaymentProfile($this->_auth->id, $this->_getParam('id'));
        }
        if (empty($profile))
            $this->_helper->redirector('wallet');
                
        $request = new Application_Service_AuthorizeNetCIM;
        $paymentProfile = $request->getCustomerPaymentProfile($profile['profile_id'], $profile['payment_profile_id']);           
     
        //$number = $paymentProfile->xml->paymentProfile->payment->creditCard;
        $card['number'] = $paymentProfile->xml->paymentProfile->payment->creditCard->cardNumber;
        $card['month'] = $profile['month'];
        $card['year'] = $profile['year'];
        
        $bill = $paymentProfile->xml->paymentProfile->billTo;
        $card['firstName'] = $bill->firstName;
        $card['lastName'] = $bill->lastName;
        $card['address1'] = $bill->address;
        $card['city'] = $bill->city;
        $card['state'] = $bill->state;
        $card['country'] = $bill->country;
        $card['zip'] = $bill->zip;
        $card['phone'] = $bill->phoneNumber;
        
        $form = new Application_Form_Card($card['country']);
        if ($this->_getParam('from') == 'crm') {
              $form->setAction("/biz/user/edit-payment-profile/id/{$this->_getParam('id')}/from/crm");
              $form->removeElement('submit');
              $form->setAttrib('id', 'card') ;
                //$profile = $this->_users->getPaymentProfileById($this->_getParam('id'));
        }
        $form->populate($card);
        $form->getElement('number')->setAttrib('readonly', 'readonly')->setValidators(array());
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $customerProfile                    = new stdClass();
                    
                $billTo ->firstName = $data['firstName'];
                $billTo ->lastName = $data['lastName'];
                $billTo ->address = $data['address1'].''.$data['address2'];
                $billTo ->city = $data['city'];
                $billTo ->state = $data['state'];
                $billTo ->zip = $data['zip'];
                $billTo ->country = $data['country'];
                $billTo->phoneNumber = $data['phone'];
                $billTo->faxNumber = '123';                
                $customerProfile->billTo[] = $billTo; 
                
                $payment->creditCard->cardNumber = $data['number'];
                $payment->creditCard->expirationDate = $data['year'].'-'.$data['month'];
                $customerProfile->payment[] = $payment; 
           
               // $response = $request->updateCustomerPaymentProfile($this->_auth->profile_id, $this->_getParam('id'), $customerProfile, 'none'); 
                $response = $request->updateCustomerPaymentProfile($profile['profile_id'], $profile['payment_profile_id'], $customerProfile, 'none'); 
                
               // var_dump($response);
                if ($response->isOk()) {                    
                    $this->customerPaymentProfileId = $response->getPaymentProfileId();           
                    //$user_profile['payment_profile_id'] = $this->_getParam('id');
                    $user_profile['payment_profile_id'] = $profile['payment_profile_id'];
                    $user_profile['month'] = $data['month'];
                    $user_profile['year'] = $data['year'];
                    if ($this->_users->saveProfile($user_profile)) {
                        $this->_helper->redirector('wallet');
                    }
                    if ($this->getRequest()->isXmlHttpRequest()) {
                        //$this->_helper->layout()->disableLayout();
                        $this->_helper->viewRenderer->setNoRender(true);
                    }
                }
            } else {
                if ($this->getRequest()->isXmlHttpRequest()) {
                     echo "Please Enter all required fields";
                     $this->_helper->viewRenderer->setNoRender(true);
                }
            }
        }
         if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout()->disableLayout();
            //$this->_helper->viewRenderer->setNoRender(true);
        }
    }            
    
    /*public function editcardAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }

        //first check if the card belong to this user
        $check = $this->_users->getCard($this->_auth->id, $this->_getParam('id'));
        if (empty($check))
            $this->_helper->redirector('wallet');
        //all check out, render the form
        $form = new Application_Form_Card();
        $card = $this->_users->getCard($this->_auth->id, $this->_getParam('id'));
        $form->populate($card);
        $this->view->form = $form;
           if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $card = array ( 'number' => $data['number'],
                   'user_id' => $this->_auth->id,
                   'month' => $data['month'],
                   'year' => $data['year'],
                   'name' => $data['name'],
                   'address1' => $data['address1'],
                   'address2' => $data['address2'],
                   'city'=>$data['city'],
                   'state' => $data['state'], 
                   'zipcode' => $data['zipcode'],
                   'country' => $data['country'],
                   'phone' => $data['phone'],
                   'active' => 1,
                    'action_time' => date("Y-m-d H:i:s")
                 );
                if ($this->_users->saveCard ($card)) {
                   $this->_helper->redirector('wallet');
                }              
            }
         }
    }*/
    
    public function walletAction() {
        
    if (!$this->_auth) {
        $this->_helper->redirector('login'); 
    }   
    $user = $this->_users->getUser($this->_auth->id);
    $form = new Application_Form_Card($user['country']);
    $form->setAction("/biz/user/wallet");
    
    $form->populate($user);
    $this->view->form = $form;
    $request = new Application_Service_AuthorizeNetCIM;
    
    //create user profile if not existing
    //to do: add CC user condition
    //var_dump($this->_auth->profile_id);
    if ($this->_auth->profile_id == 0 || is_null($this->_auth->profile_id) || $this->_auth->profile_id == '') {        
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
    //get customer payment profile, call from user wallet    
    if ($this->_getParam('from') == '') {
        $this->view->profiles = $this->_users->getPaymentProfiles($this->_auth->id);
    } else {
         $form->setAction("/biz/user/wallet/from/checkout");
    }
    $profiles = $request->getCustomerProfile($this->_auth->profile_id);
     
    if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST))  {
                //create customer payment profile
                $data = $form->getValues();
                $customerProfile    = new stdClass();               
                $billTo = new stdClass();
                $payment = new stdClass();
                $payment->creditCard = new stdClass();                
                
                $billTo ->firstName = $data['firstName'];
                $billTo ->lastName = $data['lastName'];
                $billTo ->address = $data['address1'].''.$data['address2'];
                $billTo ->city = $data['city'];
                $billTo ->state = $data['state'];
                $billTo ->zip = $data['zip'];
                $billTo ->country = $data['country'];
                $billTo->phoneNumber = $data['phone'];
                $billTo->faxNumber = '999';                
                $customerProfile->billTo[] = $billTo; 
              
                $payment->creditCard->cardNumber = $data['number'];
                $payment->creditCard->expirationDate = $data['year'].'-'.$data['month'];                
                $customerProfile->payment[] = $payment;              
                
                $cardType = $this->_users->getCardType($data['number']);
                
                //Reject JCB and dinner
                if($cardType == 'Diners Club' || $cardType == 'JCB' ) {
                      $error = "Sorry we don't accept Diners Club or JCB card.";
                      $form->getElement('number')->addError($error);
                      $this->view->dataError = 1; 
                      return;    
                }
                
                $response = $request->createCustomerPaymentProfile($this->_auth->profile_id, $customerProfile);       
               // var_dump($response);
                if ($response->isOk()) {
                    $this->customerPaymentProfileId = $response->getPaymentProfileId();
                    //save it into the database
                    $profile = array('user_id' => $this->_auth->id, 
                        'type' => $cardType,
                        'profile_id' => $this->_auth->profile_id,
                        'payment_profile_id' => $this->customerPaymentProfileId,
                        'month' => $data['month'],
                        'year' => $data['year']
                        );                    
                   if ($this->_users->saveProfile ($profile)) {                       
                        if ($this->_getParam('from') == 'checkout') {    
                              $this->_helper->redirector('billing', 'checkout');
                        } else {
                               $this->_helper->redirector('wallet');
                        }
                   }
                }               
            } else {
                $this->view->dataError = 1;                
            }
         }
        if ($this->getRequest()->isXmlHttpRequest()) {     
            $this->_helper->layout->disableLayout();
        }
        
    }
    public function allprofilesAction()
    {    
      if (!$this->_auth) {
          $this->_helper->redirector('login');
      }  
      $request = new Application_Service_AuthorizeNetCIM;
      $responses = $request->getCustomerProfileIds();        
   
      foreach ($responses->xml->ids->numericString as $id) {
         echo $id.'<br>';
         $request->deleteCustomerProfile($id);
      }
      $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function orderHistoryAction()
    {        
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }            
    }
    
    public function orderHistoryDataAction()
    {      
       if (!$this->_auth) {
            return;
        } 
        $perPage = 10;
        $count = $this->_users->getOrderTotal($this->_auth->id);
        $this->view->totalPages = ceil($count['total']/$perPage);        
        $this->view->page = $this->getParam('page');        
        
        $from = ($this->getParam('page') - 1) *$perPage;
        $to = 10;        
        $this->view->orders = $this->_users->getOrders($this->_auth->id, $from, $perPage);
        $this->_helper->layout->disableLayout();
    }
    
    
    /*public function walletAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login'); 
        }        
        $this->view->wallets = $this->_users->getCards($this->_auth->id);
        $form = new Application_Form_Card();
        $user = $this->_users->getUser($this->_auth->id);
     //   $user['name'] = $user['firstname'].' '.$user['lastname'];
        $form->populate($user);
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $card = array ( 'number' => $data['number'],
                   'user_id' => $this->_auth->id,
                   'month' => $data['month'],
                   'year' => $data['year'],
                   'name' => $data['name'],
                   'address1' => $data['address1'],
                   'address2' => $data['address2'],
                   'city'=>$data['city'],
                   'state' => $data['state'], 
                   'zipcode' => $data['zipcode'],
                   'country' => $data['country'],
                   'phone' => $data['phone'],
                   'active' => 1
                 );
                if ($this->_users->saveCard ($card)) {
                   $this->_helper->redirector('wallet');
                }              
            }
         }
    }*/

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        session_destroy();
        $this->_helper->redirector('login', 'user'); // back to login page
    }
    
       
    public function registerAction() {

        if($_SERVER['REQUEST_URI'] != '/biz/user/register') {
            $this->_helper->redirector('register', 'user');
        }
        $this->view->headTitle('Register for a Beaming White Busiiness Account');
        Zend_Auth::getInstance()->clearIdentity();
        
        $country = $this->getRequest()->isPost()?$_POST['country']:'US';        
            
        $form = new Application_Form_Signup($country);
        $form->removeElement('status');
        $form->removeElement('parentAccountID');
        $form->removeElement('imported');
        $form->removeElement('soldby');
        
        $this->view->form = $form;
       
        if ($this->getRequest()->isPost() && $_SERVER['REQUEST_URI'] == '/biz/user/register') {
           /* $mail = new Zend_Mail();
            
            $message = 'Refer:'.isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:''.'<br>'.
                    'IP:'.isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:''.'<br>';
                  
            $mail->setBodyHTML($message.'<br>'.serialize($_POST));
            $mail->setFrom('jing@beamingwhite.com', 'beamingwhite.com');		
            $mail->addTo('jing@beamingwhite.com');                       
            $mail->setSubject('POST VALUES::Beaming White Business Registration');                   
            $mail->send();*/
                        
            if ($form->isValid($_POST) && $_POST['website'] == '') {                
                $data = $form->getValues();                              
                unset($data['captcha']);
                $hasError = 0;
                
                 if($data['password'] != $data['confirm_password']){
                    $error = "Password and confirm password don't match.";
                    $form->getElement('password')->addError($error);
                    $hasError = 1;                    
                 }
                 
                  /*if(($data['contactphone'] !='' && $data['contactphone'] == $data['contactphone2']) || $data['email'] == $data['email2']) {                       
                        $mail->setBodyHTML($message.'<br>'.serialize($data));
                        $mail->setFrom('jing@beamingwhite.com', 'beamingwhite.com');		
                        $mail->addTo('jing@beamingwhite.com');                       
                        $mail->setSubject('Spam Possible::Beaming White Business Registration');                   
                        $mail->send();
                        return;
                  } */
                 
                  if(is_null($data['source'])) {
                      $form->getElement('source')->addError('Please tell us how you hear about us.');
                      $hasError = 1;                      
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
                
                   //check password strength
                  $uppercase = preg_match('@[A-Z]@', $data['password']);
                  $lowercase = preg_match('@[a-z]@', $data['password']);
                  $number    = preg_match('@[0-9]@', $data['password']);

                  if(!$uppercase || !$lowercase || !$number || strlen($data['password']) < 8) {
                      $error = "Password must be a minimum of 8 characters, contains one number, one lower and upper case letter";
                      $form->getElement('password')->addError($error);
                      $hasError = 1;
                  }
                  
                  if($hasError == 1) {
                      return;
                  }
                  
                  unset($data['confirm_password']);     
                  $data['imported'] = 'Registration';
                  $data['username'] = $data['businessname'];
                  $data['created_time'] = date("Y-m-d H:i:s");
                  $data['firstname'] = ucwords(strtolower($data['firstname']));
                  $data['lastname']  = ucwords(strtolower($data['lastname']));
                  $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                  
                $insert = $this->_users->save($data);
               
                if (strstr($insert, 'SQLSTATE[23000]')) {
                    $error = 'User name already taken. Please choose another one.';
                    $form->getElement('email')->addError($error);
                    return;
                }
                 $mail = new Zend_Mail();
                
                 $country = $this->_users->getCountryName($data['country']);
                 $state = $this->_users->getZoneName($data['state'], $data['country']);  
                         
                 $message = "A new business registration form has been submitted from http://www.beamingwhite.com/biz/user/register: <br><br> 
                    First Name: {$data['firstname']} <br>
                    Last Name: {$data['lastname']}<br>     
                    I am interested in: Business Products<br>
                    Phone: {$data['contactphone']} <br>
                    Email: {$data['email']}<br>                    
                    Business Type: {$data['businesstype']}<br>
                    Business Locaiton: {$data['city']} {$state['name']}, {$country['name']} <br>  
                    How you hear about us: {$data['source']} <br>
                    Comment: {$data['interest']} <br>
                    IP Address: {$_SERVER['REMOTE_ADDR']}<br>
                    
                    <br>
                    Thank you, <br>
                    Beaming White CRM";
                                        
                    $contactName = $data['firstname']. ' '.$data['lastname'];
                    $mail->setBodyHTML($message);
                    $mail->setFrom($data['email'], $contactName);		
                    $mail->addTo('contact@beamingwhite.com');
                    $mail->addBcc('jing@beamingwhite.com');
                    $mail->setSubject('Teeth Whitening Info from Beaming White');                   
                    $mail->send();                
                    $this->_helper->redirector('register-success', 'user');
            }
        }
    }
    
    public function registerSuccessAction() {
     
    }
    
   public function forgetPasswordAction()
   {
        $form = new Application_Form_Forgetpassword();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $tempPW = $this->_users->gen_password();                                
                $update = $this->_users->setPasswordByEmail($data['email'], $tempPW);
                
                if($update) {   
                    $user = $this->_users->getUserByEmail(trim($data['email']));
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
                   // $mail->send($transport);
                    $mail->send();	               
                    echo "<br><br>We've sent an email to {$user['email']}. Please follow the link it contains to create your new password.";
                    //echo $message;
                } else {
                    echo "Email not found in our system.";
                }
            }
         }
   }
   
   public function passwordAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }
        $form = new Application_Form_Password();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                if($data['password'] != $data['confirm_password']){
                    $error = "Password and confirm password don't match.";
                    $form->getElement('password')->addError($error);
                    return;
                }
               $check = $this->_users->checkPassword($this->_auth->id, $data['old_password']);
               if (!$check) {
                   $error = "Old password invalid, please re-enter.";
                    $form->getElement('old_password')->addError($error);
                    return;
               } 
               //all check out
              // $update = $this->_user->resetPassword($check["id"], $data['password']);
              // var_dump($check);               
               if($this->_users->resetPassword($check["id"], $data['password'])) {
                   //mark as reset
                    $this->_users->update_reset($this->_auth->id);
                   
                    $this->_helper->flashMessenger->addMessage("Password reset successfully.");
                    $this->_helper->redirector('login', 'user');
               }
            }
        }
   }
   
   public function addressAction(){
        if (!$this->_auth) {
            $this->_helper->redirector('login'); 
        }        
        $this->view->address = $this->_users->getAddresses($this->_auth->id);                   
        $this->view->userId = $this->_auth->id;
       /* $user = $this->_users->getUser($this->_auth->id);         
        $form = new Application_Form_Address($user['country']); 
        $form->populate($user);
       
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $data['user_id'] = $this->_auth->id;                
                if ($this->_users->saveAddress ($data)) {
                   $this->_helper->redirector('address');
                }              
            }
         }*/
   }
   
   public function addAddressAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login'); 
        }
        $form = new Application_Form_Address($this->_auth->country);
        $form->setAction("/biz/user/add-address");
        $user = $this->_users->getUser($this->_auth->id);   
        $form->removeElement('submit');
        $form->setAttrib('id', 'address') ;        
        $form->populate($user);
        $this->view->form = $form;
        if ($this->_getParam('from') == 'checkout') {
            $form->setAction("/biz/user/add-address/from/checkout");
        }
        if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $data['user_id'] = $this->_auth->id;                                
                 if ($this->_users->saveAddress($data)) {
                    if ($this->_getParam('from') == 'checkout') {
                        $this->_helper->redirector('shipping', 'checkout');
                    } else {
                       echo "success";
                       $this->_helper->viewRenderer->setNoRender(TRUE);
                    }
                }
            } else {                    
                echo "Please enter all required fields";
                $this->_helper->viewRenderer->setNoRender(TRUE);
            }
         }
        if ($this->getRequest()->isXmlHttpRequest()) {     
            $this->_helper->layout->disableLayout();
        }
   }
   
    public function deleteaddressAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }

        //first check if the card belong to this user
        $check = $this->_users->getAddress($this->_auth->id, $this->_getParam('id'));
        if (empty($check))
            $this->_helper->redirector('address');        
        $this->_users->deleteAddress($this->_getParam('id'));
        $this->_helper->redirector('address');         
    }
    
    public function ajaxGetRegionsAction() {
        $regions = $this->_users->getRegions($this->_getParam('country'));
        foreach ($regions as $region) {      
             echo  "<option value='{$region['code']}'>{$region['name']}</option>";                     
        }
        $this->_helper->layout()->disableLayout();  
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function editaddressAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }
        
        //first check if the card belong to this user
        $check = $this->_users->getAddress($this->_auth->id, $this->_getParam('id'));
        if (empty($check) && $this->_auth->role == 'customer') {
            $this->_helper->redirector('address');
        }
        //all check out, render the form
        $address = $this->_users->getAddress($this->_auth->id, $this->_getParam('id'));
        $form = new Application_Form_Address($address['country']);
        $form->removeElement('submit');
        if ($this->_getParam('from') == 'checkout') {
            $form->setAction("/biz/user/editaddress/id/{$this->_getParam('id')}/from/checkout");
        }

        //$form->removeElement('submit');
        $form->setAttrib('id', 'address') ;
            
        $form->populate($address);
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $_POST['action_time'] = date("Y-m-d H:i:s");
                $_POST['user_id'] = $this->_auth->id;
                $_POST['address_id'] = $this->_getParam('id');
                unset($_POST['submit']);
               
                if ($this->_users->saveAddress($_POST)) {
                    if ($this->_getParam('from') == 'checkout') {
                        $this->_helper->redirector('shipping', 'checkout');
                    } else {
                       echo "success";
                       $this->_helper->viewRenderer->setNoRender(TRUE);
                    }
                }
            } else {                    
                echo "Please enter all required fields";
                $this->_helper->viewRenderer->setNoRender(TRUE);
            }
        }
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout()->disableLayout();
        }
    }
    
    public function invoiceAction() {
        if (!$this->_auth) {
            $this->_helper->redirector('login');
        }
        $this->view->order = $this->_orders->get_order($this->_getParam('id'));
        $this->view->items = $this->_orders->get_invoice_items($this->_getParam('id'));  
        $this->view->print = 0;
        if (($this->_getParam('print')) !='') {
            $this->view->print = 1;
            $this->_helper->layout()->disableLayout();  
        }
    }
    
     public function pingAction() {
       if (!Zend_Auth::getInstance()->getIdentity()) {
           echo "logout";
           exit();
       }
       $events = $this->_crms->getAlertEvents($this->_auth->id);
       if ($events) {
           foreach ($events as $event) {
                echo "{$event['title']}\x0A";                
                $this->_crms->updateEvent(array('popup_alert' => date('Y-m-d H:i:s')), $event['id'], 'followup');
           }
       }
       $this->_helper->layout->disableLayout();
       $this->_helper->viewRenderer->setNoRender(TRUE);
  }
/*  public function importAction() {
      
       //drop table user;
       //drop table account_user;
       //drop table user_notes;
     
       
       $this->_db = Zend_Registry::get('db');
      $userNotes = $this->_db->fetchALL("SELECT user_id, author, title, type, notes, enter_time FROM user_notes_temp2 WHERE user_id < 11365");
        $index = 0;
        foreach ($userNotes as $notes) {
            if ($notes) {
                $this->_db->insert('user_notes', $notes);
                ++$index;
            }
        }
        echo $index;

        die();
      $this->_db = Zend_Registry::get('db');
      $users = $this->_db->fetchAll("SELECT * from user_temp");
      echo '<pre>';
      foreach ($users as $user) {          
          $oldUserId = $user['id'];
          unset($user['id']); 
          //var_dump($user);
          $insert = $this->_db->insert('user', $user);     
          $userId = $this->_db->lastInsertId();
          $accountUser = $this->_db->fetchRow("SELECT parent_user, assign_time FROM account_user_temp WHERE user = '$oldUserId'");          
          if($accountUser) {              
            $rep = $accountUser;
            $rep['user'] = $userId;
            $this->_db->insert('account_user', $rep);
          }
          $userNotes = $this->_db->fetchRow("SELECT user_id, author, title, type, notes, enter_time FROM user_notes_temp WHERE user_id = '$oldUserId'");
          $notes = $userNotes; 
          if($userNotes) {                       
              $notes['user_id'] = $userId;
              $this->_db->insert('user_notes', $notes);              
          } else { //existing user
             // $this->_db->insert('user_notes', $notes);
          }        
      }
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender(TRUE);
  }
    
    public function fixUserAction() {
        $this->_db = Zend_Registry::get('db');
        $users = $this->_db->fetchAll("SELECT id, firstname, lastname from user");
        foreach($users as $user) {
            //echo $user['firstname'];
            //echo ' '.$user['lastname'];  echo '<br>';          
            $data['firstname'] = ucwords(strtolower($user['firstname']));
            $data['lastname'] = ucwords(strtolower($user['lastname']));
            
            $update = $this->_db->update('user', $data, $this->_db->quoteInto("id = ?", $user['id']));       
            echo "update: $update -- id: {$user['id']} <br>";
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function fixAccountAction() {
        $row = 1;$index = 1;
        if (($handle = fopen("../biz/data/Accounts1.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                            
                if (isset($data[24]) && $data[24] != '' && $data[26] == '') {
                   $user['firstname'] = '';
                   $user['lastname'] = '';
                   if($data[3] != '') {
                    //$name = preg_split("/[\s,]+/", $data[3]);
                    $name =  preg_split('/\s+/', $data[3], 2);
                    //echo '<pre>';
                    //var_dump($name);
                        $user['firstname'] = $name[0];
                        if (!empty($name[1])) {
                            $user['lastname'] = $name[1];
                        }
                     
                    $this->_users->fixName($data[24], $user);
                    ++$index;
                    //echo $data[3].'---'.$data[24].'----'. $user['firstname'].' '.$user['lastname'].'<br>';
                   }
                   
                }
               
            }
            fclose($handle);
        }
        echo "total: $index";
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
   
    public function importAccountAction() {
        $row = 1;
        if (($handle = fopen("../biz/data/Accounts1.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                $user = array(
                  //  'rep' => $data[1],
                    'resale_number' => $data[0],
                    'created_time' => date("Y-m-d H:i:s", strtotime($data[19])),
                    'businessname' => $data[3],
                    'contactphone' => $data[4],                    
                    'website' => $data[8],
                    'source' => $data[21],
                    'email' => $data[24],
                    'contactphone2' => $data[28],
                    'email2'=>$data[31],
                    'address1'=>$data[41],
                    'city'=>$data[43],
                    'state'=>$data[45],
                    'zip'=>$data[47],
                    'country'=>$data[49],
                    'description'=>$data[54],
                    'imported' => 'Zoho',
                    'imported_text'=>$data[27],
                    'businesstype'=>$data[32],
                     'password' => 'init$0#'
                   // 'name'=>$data[18]
                );
                if ($data[26] != '') {
                    $name = preg_split("/[\s,]+/", $data[26]);
                }
                $user['firstname'] = $name[0];
                $user['lastname'] = $name[1];
                $user['type'] = $data[11] == 'Customer'?'Account':'Prospect';
                
                if($data['1'] == 'zcrm_143776000000033029') $user['parent_user'] = 2;                
                elseif($data['1'] == 'zcrm_143776000000466008') $user['parent_user'] = 6;
                elseif($data['1'] == 'zcrm_143776000003212001') $user['parent_user'] = 3;
                elseif($data['1'] == 'zcrm_143776000003768003') $user['parent_user'] = 4;
                elseif($data['1'] == 'zcrm_143776000003573107') $user['parent_user'] = 5;
                elseif($data['1'] == 'zcrm_143776000000466012') $user['parent_user'] = 12;
                else $user['parent_user'] = 1;
         
                $userId = $this->_users->save($user);                
                
                
                //important => note
                if (trim($data[25]) !='' && $userId != 0) {
                    $notes = array('user_id' => $userId,
                            'author' => 'Zoho',
                            'type' => 'note',
                            'notes' => 'Important: '.$data[25]);
                    $this->_users->savenotes($notes);
                }
                if (trim($data[55]) !='' && $userId != 0) {
                    $notes = array('user_id' => $userId,
                            'author' => 'Zoho',
                            'type'   => 'note',
                            'notes'  => 'Last Contact: '.$data[55]);
                    $this->_users->savenotes($notes);
                }
                //shipping address
               
                if ($userId != 0 && trim($data[42]) !='' && trim($data[44]) !='' && trim($data[46]) !='' && trim($data[48]) !='' &&  trim($data[50]) !='') {
                    $address = array('user_id' => $userId, 
                            'firstname'=> $user['firstname'], 
                            'lastname' => $user['lastname'],
                            'company' => $user['businessname'],
                            'address1' => $data[42], 
                            'city' => $data[44],
                            'state' => $data[46],
                            'zipcode' => $data[48], 
                            'country' => $data[50], 
                            'phone' => $user['contactphone'],
                            'preference' => 1);
                    $this->_users->saveAddress($address);
                }
                //billing address
                 if ($userId != 0 && trim($data[41]) !='' && trim($data[43]) !='' && trim($data[45]) !='' && trim($data[47]) !='' &&  trim($data[49]) !='') {
                    $address = array('user_id' => $userId, 
                            'firstname'=> $user['firstname'], 
                            'lastname' => $user['lastname'],
                            'company' => $user['businessname'],
                            'address1' => $data[41], 
                            'city' => $data[43],
                            'state' => $data[45],
                            'zipcode' => $data[47], 
                            'country' => $data[49], 
                            'preference' => 1);
                    $this->_users->saveZohoBillingAddress($address);
                }
                
            }
            fclose($handle);
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function importNotesAction() {
        $row = 1;
        if (($handle = fopen("../biz/data/Notes1.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                $user = array(
                  //  'rep' => $data[1],
                    'zoho_id' => $data[4],                                                      
                    'title' => trim($data[2]),
                    'notes' => trim($data[3]),
                    'created_time' =>date('Y-m-d H:i:s', strtotime($data[7]))                                                 
                );
                
                
                if($data['5'] == 'zcrm_143776000000033029') $user['author'] = 'Tyler Samson';                
                elseif($data['5'] == 'zcrm_143776000000466008') $user['author'] = 'Luis Lajous';
                elseif($data['5'] == 'zcrm_143776000003212001') $user['author'] = 'Brian Radke';
                elseif($data['5'] == 'zcrm_143776000003768003') $user['author'] = 'David Carter';
                elseif($data['5'] == 'zcrm_143776000003573107') $user['author'] = 'Kellie Pool';
                elseif($data['5'] == 'zcrm_143776000000466012') $user['author'] = 'Loli Rodriguez';
                else $user['author'] = 'Zoho';
                      
                $this->_users->saveZohoNotes($user);               
               
            }
            fclose($handle);
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function importZohoNotesAction() {
        $result = $this->_users->getZohoNotes();
        foreach ($result as $data) {
            if ($data['id']) {
                $notes = array('author' => $data['author'], 'title' => $data['title'], 'notes'=>$data['notes'], 'user_id' => $data['id'],
                        'enter_time' => $data['created_time'], 'type' => 'note');
           
                $this->_users->savenotes($notes); 
            }
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function importLeadAction() {
        $row = 1;
        if (($handle = fopen("../biz/data/Leads1.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                $user = array(
                  //  'rep' => $data[1],
                    'resale_number' => $data[0],
                    'created_time' => date("Y-m-d H:i:s", strtotime($data[18])),
                    'businessname' => $data[2],
                    'contactphone' => $data[7],
                    'type' => 'Lead',
                    'website' => $data[10],
                    'source' => $data[27],
                    'email' => $data[6],
                    'contactphone2' => $data[31],
                    'email2'=>$data[25],
                    'address1'=>$data[37],
                    'city'=>$data[38],
                    'state'=>$data[39],
                    'zip'=>$data[40],
                    'country'=>$data[41],
                  //  'description'=>$data[32],
                    'imported' => 'Zoho',
                    'firstname' => $data[3],
                    'lastname'=> $data[4]
                   // 'name'=>$data[18]
                );
                
                
                if($data['1'] == 'zcrm_143776000000033029') $user['parent_user'] = 2;                
                elseif($data['1'] == 'zcrm_143776000000466008') $user['parent_user'] = 6;
                elseif($data['1'] == 'zcrm_143776000003212001') $user['parent_user'] = 3;
                elseif($data['1'] == 'zcrm_143776000003768003') $user['parent_user'] = 4;
                elseif($data['1'] == 'zcrm_143776000003573107') $user['parent_user'] = 5;
                elseif($data['1'] == 'zcrm_143776000000466012') $user['parent_user'] = 12;
                else $user['parent_user'] = 1;
          
                $userId = $this->_users->save($user);
                if (trim($data[42]) !='' && $userId != 0) {
                    $notes = array('user_id' => $userId,
                            'author' => 'Zoho',
                            'type' => 'note',
                            'notes' => $data[42]);
                    $this->_users->savenotes($notes);
                }
            }
            fclose($handle);
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
     public function importTsAction() {
        $row = 1;
        if (($handle = fopen("../biz/data/ODC.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                $user = array(
                  //  'rep' => $data[1],                    
                    'created_time' => date("Y-m-d H:i:s"),
                    'businessname' => $data[8],
                    'contactphone' => $data[4],
                    'fax'=> $data[5],
                    'type' => 'Lead',
                    'website' => $data[7],
                    'source' => 'Tradeshow',
                    'source_text' => 'ODC 2014',
                    'email' => $data[6],
                    'contactphone' => $data[4],
                   
                    'address1'=>$data[9],
                    'city'=>$data[10],
                    'state'=>$data[11],
                    
                    'country'=>'US',
                  //  'description'=>$data[32],
                    'imported' => 'Other',
                    'firstname' => $data[1],
                    'lastname'=> $data[2]
                   // 'name'=>$data[18]
                );
                
               $user['parent_user'] = 13;
        
                $userId = $this->_users->save($user);
             
                if (trim($data[16]) !='' && $userId != 0) {
                    $notes = array('user_id' => $userId,
                            'author' => 'Erika Rosa',
                            'type' => 'note',
                            'notes' => $data[16]);
                    $this->_users->savenotes($notes);
                }
                
            }
            fclose($handle);
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function fixCountryAction()
    {
         $this->_db = Zend_Registry::get('db');
         $countries = $this->_db->fetchAll("select distinct country as name, country.iso_code_2 from user left join country on user.country = country.name WHERE length(user.country) > 2");
         if ($countries) {
             foreach ($countries as $country) {
                 //echo $country['name'].'<br>';
                 if ($country['iso_code_2']) {
                     echo "UPDATE user set country = '{$country['iso_code_2']}' WHERE country = '{$country['name']}'; <br>";
                 }
             }
         }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    */
   public function importDentalAction() {
        $row = 1;
        if (($handle = fopen("../biz/data/sapidental20150922.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                $user = array(
                  //  'rep' => $data[1],                    
                    'created_time' => date("Y-m-d H:i:s"),
                    'businessname' => trim($data[0]),
                    'contactphone' => trim($data[6]),
                    'contactphone2' => trim($data[7]),
                    'fax'=> trim($data[8]),
                    'type' => 'Lead',
                    'website' => trim($data[10]),
                    'source' => 'Outside Sales',                                      
                    'address1'=> trim($data[2]),
                    'city'=> trim($data[3]),
                    'state'=> trim($data[4]),
                    'zip' => trim($data[5]),
                    'country'=>'US',                    
                    'soldby'=>'Sapient Dental',
                    'imported' => 'Cold Call',  
                    'password' => 'init$0#',
                    'firstname'=> 'firstname',
                    'lastname'=>'lastname',
                    
                );
                
                $user['parent_user'] = 12661;
                
                $user['email'] = trim($data[11]);
                //email field empty, take the account name
                if (trim($data[11]) == ''){
                    $user['email'] = trim($data[1]);
                }
                //check if it exists
              /*  if ($user['tempEmail'] == '') {
                    echo "empty email";
                }*/
                if($this->_users->getUserByEmail($user['email'])) {                    
                    $user['email'] = $user['email']. $row;
                    echo "$row user exists {$user['email']} <br>";    
                } 
                   
                $userId = $this->_users->save($user);
                if (trim($data[9]) != '' && $userId != 0) {
                    $notes = array('user_id' => $userId,
                        'author' => 'Will Perkins',
                        'type' => 'note',
                        'notes' => $data[9]);
                    $this->_users->savenotes($notes);
                }
            }
            echo "Total imported: $row <br>";
            fclose($handle);
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }

}

