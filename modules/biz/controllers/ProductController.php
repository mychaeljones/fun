<?php

class ProductController extends Zend_Controller_Action {

    public function init() {        
        
        if (!Zend_Auth::getInstance()->getIdentity()) {
           $this->_helper->redirector('login', 'user');
        }
        $this->_product = new Application_Model_Product;
        $this->_auth = Zend_Auth::getInstance()->getIdentity();      
    }
        
    public function indexAction() {
        $this->view->headTitle('Business Center Product Categories');
        $this->view->categories = $this->_product->getCategories();
    }
    
    public function detailAction() {
       $this->view->headTitle('Product Detail');         
       //$this->view->headScript()->appendScript('/biz/public/js/jquery/jquery.zoom.js','text/javascript');
       //$this->view->headScript()->appendFile('/biz/public/js/jquery/jquery.zoom.js');
        
        $this->view->product = $this->_product->getProductDetail($this->_getParam('id'));
        $this->view->options = $this->_product->getProductOptionsById($this->_getParam('id'));
        $this->view->prices = $this->_product->getUserProductPrices($this->_getParam('id'), $this->_auth->id);
        /*$this->view->categories = $this->_product->getCategories();*/
    }
    
    public function categoryDetailAction() {
       $this->view->headTitle('Category Detail');
       $this->view->products = $this->_product->getCategoryProducts($this->_getParam('id'));
       //$this->view->categories = $this->_product->getCategories();
    }
    
    public function autosuggestAction() {        
        $suggestions = $this->_product->findProductName($this->_getParam('queryString'));
        $options = array();

        if ($suggestions) {
            foreach ($suggestions as $suggestion) {
                $options[] = $suggestion['name'];
            }
        }
        echo json_encode($options);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function userAutosuggestAction() {             
        $suggestions = $this->_product->findUserProductName((int)$_POST['user_id'], $this->_getParam('queryString'));
        $options = array();

        if ($suggestions) {
            foreach ($suggestions as $suggestion) {
                $options[] = $suggestion['name'];
            }
        }
        echo json_encode($options);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function viewAction() {
        $this->view->products = $this->_product->getAllProduct();        
    }
    
    public function adminAction() {
        
    }
    public function getProductAction() {
        $products = $this->_product->getAdminProduct();
       // var_dump($products);
        echo json_encode($products);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function editAction() {
        if ($this->getRequest()->isPost()) {
           // $this->view->product = $this->_product->getProductById($this->getRequest()->getParam('product_id')); 
            $data['category_id'] = $this->getRequest()->getParam('category_id');
            $data['description'] = $this->getRequest()->getParam('description');           
            $update = $this->_product->updateProduct($data, $this->getRequest()->getParam('product_id'));
            if (strstr($update, 'SQLSTATE[23000]')) {
                 $this->view->message = 'This Product Name is already existing.';
            } else {
                $this->view->message = "Update successfully.";
            }
        }
        $this->view->product = $this->_product->getProductById($this->_getParam('id'));  
        $this->view->categories = $this->_product->getCategories();   
    }    
    //just to change length height ect.
    public function updateProductAction() {
        //var_dump($_POST);
        $data = array ('name'=> trim($_POST['name']), 'length' => trim($_POST['length']), 'width' => trim($_POST['width']),
            'height'=>trim($_POST['height']),'weight'=>trim($_POST['weight']), 'status'=>trim($_POST['status']),
            'volumn'=>trim($_POST['volumn']));
        $update = $this->_product->updateProduct($data, $this->getRequest()->getParam('product_id'));
        if (strstr($update, 'SQLSTATE[23000]')) {
            $data['isError'] = true;
            $data['msg'] = 'This Product Name is already existing.';                 
        } else {
            $data['product_id'] = $this->getRequest()->getParam('product_id');
        }     
        echo json_encode($data);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function saveProductAction() {
        $data = array ('name'=> trim($_POST['name']), 'length' => trim($_POST['length']), 'width' => trim($_POST['width']),
            'height'=>trim($_POST['height']),'weight'=>trim($_POST['weight']), 'status'=>trim($_POST['status']),
            'volumn'=>trim($_POST['volumn']));
        $save = $this->_product->save_product($data);
        if (strstr($save, 'SQLSTATE[23000]')) {
            $data['isError'] = true;
            $data['msg'] = 'This Product Name is already existing.';                 
        } else {
            $data['product_id'] = $save;
        }     
        echo json_encode($data);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function categoryAction() {
        
    }
    public function getCategoryAction() {
        $categories = $this->_product->getCategories();
       // var_dump($products);
        echo json_encode($categories);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
     public function updateCategoryAction() {
        //var_dump($_POST);
        $data = array ('name'=> trim($_POST['name']), 'description' => trim($_POST['description']), 'image' => trim($_POST['image']),
            'status'=>trim($_POST['status']), 'date_modified' => date("Y-m-d H:i:s")
           );
        $update = $this->_product->updateCategory($data, $this->getRequest()->getParam('category_id'));
        if (strstr($update, 'SQLSTATE[23000]')) {
            $data['isError'] = true;
            $data['msg'] = 'This category name is already existing.';                 
        } else {
            $data['category_id'] = $this->getRequest()->getParam('category_id');
        }     
        echo json_encode($data);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
     public function saveCategoryAction() {
          $data = array ('name'=> trim($_POST['name']), 'description' => trim($_POST['description']), 'image' => trim($_POST['image']),
            'status'=>trim($_POST['status']), 'date_added' => date("Y-m-d H:i:s")
           );
        $save = $this->_product->save_category($data);
        if (strstr($save, 'SQLSTATE[23000]')) {
            $data['isError'] = true;
            $data['msg'] = 'This category name is already existing.';                 
        } else {
            $data['category_id'] = $save;
        }     
        echo json_encode($data);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function rightbarAction() {
         $this->view->categories = $this->_product->getCategories();
    }
    
    
}