<?php

class IndexController extends Zend_Controller_Action {

    public function indexAction() {
        $this->view->headTitle('Business Index Page');
         $this->_helper->redirector('login', 'user');
    }
}