<?php

class SupportController extends Zend_Controller_Action {

    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity() ) {                
           $this->_helper->redirector('login', 'user');
        }
        $this->_users = new Application_Model_User;  
         $this->_support = new Application_Model_Support;  
        $this->_auth = Zend_Auth::getInstance()->getIdentity();        
    }
    
    public function contactAction() {
        $user = $this->_users->getUser( $this->_auth->id); 
        $form = new Application_Form_Contact();
        $form->populate($user);
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $data['user_id'] = $this->_auth->id;  
                $data['status'] = 'open';
                unset($data['businessname']);
                
                if ($this->_support->saveMessage ($data)) {
                   $this->view->message = 'Your message has been sent, thank you for contacting us!';
                } else {
                    $this->view->error = 'Sorry there is an error on sending the message!';
                }
            }
         }
   }
}
