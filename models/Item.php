<?php

class Application_Model_Item {

    protected $_db;
    public $productId;
    public $name;
    public $price;
    public $weight;
    public $volumn;
   // public $taxable;
   // public $discountPercent;
    
    public $qty;

    /*public function __construct($product, $qty)
    {
        $this->productId           = (int) $product->productId;
        $this->name                 = $product->name;
        $this->price                 = (float) $product->getPrice(false,false);
        $this->taxable              = $product->taxable;
        $this->discountPercent  = (int) $product->discountPercent;
        $this->qty                    = (int) $qty;
    }*/
    
    public function __construct($product, $qty, $options=NULL, $userId = NULL)
    {
        $this->_db = Zend_Registry::get('db');
        $this->productId            = (int) $product['product_id'];
        $this->name                 = $product['name'];        
        $this->qty                  = (int) $qty;
        $this->volumn               = $product['volumn'];
        $this->weight               = $product['weight'];
        $this->userId               = Zend_Auth::getInstance()->getIdentity()->id;
        $this->options   = $this->getOptions($options);
        if ($userId) {
           $this->price = $this->getPrice($this->productId, $this->qty, $userId);
        } else {
           $this->price = $this->getPrice($this->productId, $this->qty, $this->userId);
        }
        $this->lineCost =  number_format($this->getLineCost(), 2, '.', '');       
       
    }


    public function getLineCost()
    {
       
        return round($this->price * $this->qty, 2);
    }           
    
    public function getPrice()
    {
      
       $sql = "SELECT price FROM product_price WHERE product_id = '$this->productId' AND user_id = $this->userId AND min <= $this->qty AND max >=$this->qty AND active = 1";
       $price = $this->_db->fetchRow($sql);
       if (!$price) {
           $price = $this->_db->fetchRow("SELECT price FROM product_price WHERE product_id = '$this->productId' AND user_id = 1 AND min <= $this->qty AND max >=$this->qty AND active = 1");
           //echo "SELECT price FROM product_price WHERE product_id = '$this->productId' AND user_id = 1 AND min <= $this->qty AND max >=$this->qty AND active = 1";
           if ($price) {
               return $price['price'];
           } else {
               //throw exception
           }
           
       } else {
           return $price['price'];
       }
        
    }
    public function getOptions($options) {
        $this->_product = new Application_Model_Product;
        if (!$options) return;
        //build three arrays, one to hold the values for comparision, one for display and another one for qty
        foreach ($options as $key => $value ) {
            $result['values'][] = $value;
        }             
   
        $result['display'] = '';
        $options = $this->_product-> getProductOptionsById($this->productId );
        //option select
        foreach ($options as $key => $values) {
            $result['display'] .= "$key: <br>
            <select name= '$key' >";                 
                foreach ($values as $index => $value) {
                    $selected = '';
                    if (in_array($index, $result['values'])) $selected = 'selected';
                    $result['display'] .=  "<option value='$index' $selected> $value </option>";
                }
            $result['display'] .= "</select><br>";
        }
        //
        $result['descriptions'] = '';
        foreach ($options as $key => $values) {
            $result['descriptions'] .= "$key: ";
            foreach ($values as $index => $value) {
                if (in_array($index, $result['values']))
                    $result['descriptions'] .= $value . '<br>';
            }
        }
                
        $result['qty'] = $this->qty;
        return $result;
    }
}