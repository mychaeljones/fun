<?php

class ReportController extends Zend_Controller_Action {

    public function init() {
        if (Zend_Auth::getInstance()->getIdentity() && (Zend_Auth::getInstance()->getIdentity()->role =='admin' || Zend_Auth::getInstance()->getIdentity()->role =='marketing') ) {            
        } else {        
           $this->_helper->redirector('login', 'user');
        }
        $this->_users = new Application_Model_User;        
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        $this->_crms = new Application_Model_Crm;  
        $this->_reports = new Application_Model_Report;
        $this->_user = new Application_Model_User;
    }
    public function accountConversionAction() {
        $this->view->reps = $this->_users->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
            $this->view->conversions = $this->_reports->account_conversion($_POST['from'], $_POST['to'], $_POST['rep']);
            $this->view->accounts = $this->_reports->conversion_accounts($_POST['from'], $_POST['to'], $_POST['rep']);
        }
    }
    
    public function phoneConversionAction() {
        $this->view->reps = $this->_users->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
                       
            $this->view->conversions = $this->_reports->phone_conversion($_POST['from'], $_POST['to'], $_POST['rep']);
            $this->view->accounts = $this->_reports->phone_accounts($_POST['from'], $_POST['to'], $_POST['rep']);
        }
    }
        
    public function contactSourceAction() {        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
            $this->view->contacts = $this->_reports->user_source($_POST['from'], $_POST['to']);  
            $this->view->totalContacts = $this->_reports->total_contacts($_POST['from'], $_POST['to']);
        }
    }
    public function responseTimeAction() {  
        $this->view->reps = $this->_users->getSalesUsers();
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;     
          
            $this->view->contacts = $this->_reports->response_time($_POST['from'], $_POST['to'], $_POST['rep'], $_POST['status']);    
            $this->view->avgContacts = $this->_reports->avg_response_time($_POST['from'], $_POST['to'], $_POST['rep'], $_POST['status']);          
        }
    }
    public function allaccountsAction() {
         $this->view->reps = $this->_users->getSalesUsers();    
         $this->view->types = $this->_users->getAccountType();
         $this->view->businessTypes = $this->_users->getBusinessType();
         $this->view->soldBys = $this->_users->getSoldByOptions();
         $this->view->sources = $this->_users->getSource();
         $this->view->contactVias = $this->_users->getLeadSource();
         
         /*select country, count(country) as count from (SELECT country as country, name from user  left join country on country = country.iso_code_2 where name is null) a group by country*/
         if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;     
         //   echo '<pre>';
          //  var_dump($_POST);
            $this->view->accounts = $this->_reports->accounts($_POST);
            $this->view->groupBy = $_POST['groupBy'];
        }
    }
}
