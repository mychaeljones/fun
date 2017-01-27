<?php

class Application_Model_Cart {
    # Object level variables

    protected $_db;
    protected $_items = array();
   
    protected $_subTotal = 0;
    protected $_total = 0;
    protected $_shipping = array();
    protected $_shippingCost = array();
    protected $_salesTax = array();
    protected $_session;
 
    
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        if (isset($this->getSessionNs()->items)) {
            $this->_items = $this->getSessionNs()->items;
        }
        if (isset($this->getSessionNs()->shipping)) {
            $this->_shipping = $this->getSessionNs()->shipping;
        }
        if (isset($this->getSessionNs()->shippingCost)) {
            $this->_shippingCost = $this->getSessionNs()->shippingCost;
        }
        if (isset($this->getSessionNs()->salesTax)) {
            $this->_salesTax = $this->getSessionNs()->salesTax;
        }
        $this->_order = new Application_Model_Order;
    }
    
    /*public function init() {
        $this->loadSession();
    }*/
    
    /* public function loadSession()
    {
        if (isset($this->getSessionNs()->items)) {
            $this->_items = $this->getSessionNs()->items;
        }
        if (isset($this->getSessionNs()->shipping)) {
            $this->setShippingCost($this->getSessionNs()->shipping);
        }
    }*/
     public function getSessionNs()
    {
        if (null === $this->_session) {           
            $this->_session = new Zend_Session_Namespace(__CLASS__);            
        }
        return $this->_session;
    }
    
    /*public function setSessionNs(Zend_Session_Namespace $ns)
    {
        $this->_session = $ns;
    }*/
   /* public function setShippingCost($cost = 6.95)
    {
        $this->_shipping = $cost;
        $this->CalculateTotals();
        $this->persist();
    }
    public function getSubTotal()
    {
        $this->CalculateTotals();
        return $this->_subTotal;
    }*/
    
    public function getSubtotal()
    {
        //$this->persist();
        $this->_items['subTotal'] = 0;
        foreach ($this->_items as $item) {
            //$sub = $sub + $item->getLineCost();  
            if (is_object($item)) {
                $this->_items['subTotal'] += $item->lineCost;
            }
        }
        //$this->persist();
        return $this->_items['subTotal'];
    }
    
    public function getItemCount(){
        $this->persist();
        $this->_items['cartItems'] = 0;
        //count total number of items
        foreach ($this->_items as $item) {
            if(is_object($item)) {       
                $this->_items['cartItems'] += $item->qty;
            }          
        }
        return $this->_items['cartItems'];
    }
    
    public function getCartItems() {
       // $this->getSubtotal();
        //$this->persist();
        return $this->_items;
    }
    
    public function getShipping() {        
        return $this->_shipping;
    }
   
    
    public function getShippingCost() {        
       // $this->persist();     
        //echo __LINE__;
        //var_dump($this->_shippingCost['selected']);
        if (empty($this->_shipping)) {
            return array();
        }
        return $this->_shippingCost;
    }   
    public function getSalesTax() {        
        return $this->_salesTax;
    } 
    
    public function saveCartItems() {
      
        //first check if there is a row
        $order_id = $this->_order->check_cart($this->_auth->id);
        //echo '<pre>';
        //var_dump($this->_items);
        
        $order_data = array('user_id' => $this->_auth->id, 'order_status'=>'incomplete', 'ip'=>$_SERVER["REMOTE_ADDR"],
            'items' => serialize($this->_items)
                );
        //udpate order table
        if ($order_id) {
            $order_data['date_modified'] = date("Y-m-d H:i:s");
            $this->_order->update($order_data, $order_id);
        } else {
            $order_data['date_added'] = date("Y-m-d H:i:s");
            $order_id = $this->_order->save($order_data);
        }
      //  echo $order_id;       
    }
    
    /*
     * User login again and there are previous saved items in the cart
     */
    public function loadPreviousItems()
    {
        //first get it out of db.
        if ($orderId = $this->_order->check_cart($this->_auth->id)) {
            $order = $this->_order->get_order($orderId);
            $this->_items = unserialize($order['items']); 
            $this->_shipping = array();
            $this->_shippingCost = array();
            $this->_salesTax = array();
            $this->_total = 0;
            $this->persist();
        }
    }
    /*
     * Use when reorder
     */
    public function loadOrderItems($orderId)
    {             
        
        $this->_items = $this->_order->get_order_items($orderId);        
        
        $this->_shipping = array();
        $this->_shippingCost = array();
        $this->_salesTax = array();
        $this->_total = 0;

        $this->persist();
    }
   
    public function getTotal()
    {     
       $this->persist();     
       $shippingCost = isset($this->_shippingCost['selected']['rate'])?$this->_shippingCost['selected']['rate']:0;        
       $salesTax = isset($this->_salesTax['amount'])?$this->_salesTax['amount']:0;
       $this->_total = $this->_items['subTotal'] + $shippingCost + $salesTax;
       
       return $this->_total;
    }
    
    public function persist()
    {
         //echo '<pre>';
         //echo __LINE__.'<br>';
         //echo microtime();
        //var_dump($this->_shippingCost['selected']);
        $this->getSubtotal();
        $this->getSessionNs()->items = $this->_items;
        $this->getSessionNs()->shipping = $this->_shipping;    
        $this->getSessionNs()->shippingCost = $this->_shippingCost;  
        $this->getSessionNs()->salesTax = $this->_salesTax;  
      
    }
    
   /* public function getShippingCost()
    {
        $this->CalculateTotals();
        return $this->_shipping;
    }*/

    /**
     * Adds or updates an item contained with the shopping cart
     *    
     * @param int $qty
     * @return Storefront_Resource_Cart_Item
     */
    public function addItem($product, $qty, $option = null)
    {
        if (0 > $qty) {
            return false;
        }

        if (0 == $qty) {
            $this->removeItem($product, $option);
            return false;
        }        
       // echo '<pre>';
        //it's there already, just update the quantity
        if(isset($this->_items[$product['product_id']])) {
            //update the total qty
            $this->_items[$product['product_id']]->qty = $this->_items[$product['product_id']]->qty + $qty;
            
            $sameOption = 0;
            if (!empty($option)) {
               // $index = 0;
                 //there should at least one option already, let's compare it
                foreach ($option as $key => $value ) {
                   $currentOption[] = $value;
                }
               // var_dump($this->_items[$product['product_id']]->options);
                foreach ($this->_items[$product['product_id']]->options as $index => $productOptions) {                   
                        //try to find if any existing options is the same                      
                        $arrayDiff = array_diff($productOptions['values'], $currentOption);                        
                        if (empty($arrayDiff)) {
                           $sameOption = 1;
                           break;
                        }  
                       // ++$index;
                }            
               
               //var_dump($index);
              // die(); 
               if ($sameOption == 0) { 
                   //build a new option, reserve the old ones
                    $itemOption = new Application_Model_Item($product,$qty, $option );   
                    $this->_items[$product['product_id']]->options[$index + 1] = $itemOption->options;                   
                    
               } else {
                   //only need to update the exiting one qty                   
                   $this->_items[$product['product_id']]->options[$index]['qty'] = $this->_items[$product['product_id']]->options[$index]['qty'] + $qty;                                       
               }
                //recalculate the price etc
                $item = new Application_Model_Item($product, $this->_items[$product['product_id']]->qty, $option );  
            }           
            
        } else {           
            $item = new Application_Model_Item($product, $qty, $option);           
            $this->_items[$product['product_id']] = (object)array();
            $this->_items[$product['product_id']]->qty = $item->qty;   
            //if there is option, it's the first options for the product
            if (!empty($option)) {
                $this->_items[$product['product_id']]->options[] = $item->options;
            }
        }      
        $this->_items[$product['product_id']]->name = $item->name;
        $this->_items[$product['product_id']]->price = $item->price;
        $this->_items[$product['product_id']]->volumn = $item->volumn;
        $this->_items[$product['product_id']]->weight = $item->weight;
        $this->_items[$product['product_id']]->lineCost = $item->lineCost;
        $this->_items[$product['product_id']]->productId = $item->productId;
               
        $this->persist();        
       // var_dump( $this->_items[$product['product_id']]);
        
  
    }
    
    public function emptyCart()
    {         
        unset($this->_items);
        unset($this->_shipping);
        unset($This->_shippingCost);
        unset($this->_salesTax);
        $this->persist();
    }
        
    public function removeItem($productId, $option = null) {
        if (is_int($productId)) {
            if (empty($option)) {
                unset($this->_items[$productId]);
            } else { //remove this option for an item              
                //find the index that holds the same option
                foreach ($option as $key => $value) {
                    $currentOption[] = $value;
                }
                // var_dump($this->_items[$product['product_id']]->options);
                foreach ($this->_items[$productId]->options as $index => $productOptions) {
                    //try to find if any existing options is the same                      
                    $arrayDiff = array_diff($productOptions['values'], $currentOption);
                    if (empty($arrayDiff)) {
                        break;
                    }
                    // ++$index;
                }

                //need to update the item count
                $this->_items[$productId]->qty = $this->_items[$productId]->qty - $this->_items[$productId]->options[$index]['qty'];
                if ($this->_items[$productId]->qty > 0) {
                    unset($this->_items[$productId]->options[$index]);
                    //recalculate the line cost
                    $this->_product = new Application_Model_Product;
                    $product = $this->_product->getProductById($productId);

                    $item = new Application_Model_Item($product, $this->_items[$productId]->qty, '');
                    $this->_items[$productId]->price = $item->getPrice();
                    $this->_items[$productId]->lineCost = $item->getLineCost();
                } else {
                    unset($this->_items[$productId]);
                }
            }
        }
        $this->persist();
    }
    
    public function updateItem($product, $qty, $option = null)
    {        
        if(isset($this->_items[$product['product_id']])) {
        
            if (!empty($option)) {
               // $index = 0;
                 //find the index that holds the same option
                if ($qty <= 0) {
                    $this->removeItem((int)$product['product_id'],$option );
                    return false;
                }                
                
                foreach ($option as $key => $value ) {
                   $currentOption[] = $value;
                }
               // var_dump($this->_items[$product['product_id']]->options);
                foreach ($this->_items[$product['product_id']]->options as $index => $productOptions) {                   
                        //try to find if any existing options is the same                      
                        $arrayDiff = array_diff($productOptions['values'], $currentOption);                        
                        if (empty($arrayDiff)) {                           
                           break;
                        }  
                       // ++$index;
                }
                //total item qty
                $this->_items[$product['product_id']]->qty =  $this->_items[$product['product_id']]->qty - $this->_items[$product['product_id']]->options[$index]['qty'] + $qty;
              
                //this option qty
                $this->_items[$product['product_id']]->options[$index]['qty'] = $qty;                                       
                   
                //recalculate the price etc
                $item = new Application_Model_Item($product, $this->_items[$product['product_id']]->qty, $option );  
            } else {
                if ($qty <= 0) {
                    $this->removeItem((int)$product['product_id'],'' );
                    return false;
                }   
                $this->_items[$product['product_id']]->qty = $qty;                 
                $item = new Application_Model_Item($product, $qty, $option);                 
            } 
            
            $this->_items[$product['product_id']]->price = $item->getPrice();
            $this->_items[$product['product_id']]->lineCost = $item->getLineCost();
            
        }
        
        /*        
        if(isset($this->_items[$product['product_id']])) {
            $this->_items[$product['product_id']]->qty = $quantity;
            $item = new Application_Model_Item($product, $quantity);
            $this->_items[$product['product_id']]->price = $item->getPrice();
            $this->_items[$product['product_id']]->lineCost = $item->getLineCost();
        } */      
        $this->persist();
    }
    
    public function setShipping($shippingAddress)
    {     
        //use ups api naming 
        $this->_shipping['Name'] = $shippingAddress['shipping_firstname'].' '.$shippingAddress['shipping_lastname'];
        $this->_shipping['Address'] = array('AddressLine' => $shippingAddress['shipping_address1'].' '.$shippingAddress['shipping_address2'],
                'City' => $shippingAddress['shipping_city'],
                'StateProvinceCode' => $shippingAddress['shipping_state'],
                'PostalCode' => $shippingAddress['shipping_zipcode'],
                'CountryCode' => $shippingAddress['shipping_country'],
            );
        $this->_shipping['address_id'] = $shippingAddress['address_id'];
        $this->_shipping['contact_phone'] = $shippingAddress['contact_phone'];
        $this->_shipping['contact_email'] = $shippingAddress['contact_email'];
        $this->persist();
    }
    
    public function unsetShipping()
    {        
        unset($this->_shipping);
        unset($this->_shippingCost);        
        unset($this->_salesTax);
        $this->persist();
    }
    
    public function setShippingCost($data, $address_id = null, $selected = null)
    {  
       
        $this->_shippingCost['ups'] = $data;
        $this->_shippingCost['address_id'] = $address_id;      
        $this->_shippingCost['selected'] = $selected;    
             
        $this->_setSalesTax();        
        $this->persist();          
    }
    
    private function _setSalesTax() {     
        $this->_salesTax = array();
        if ($this->_shipping['Address']['StateProvinceCode'] == 'WA') {
            $subTotal = $this->_shippingCost['selected']['rate'] + $this->_items['subTotal'];
            $data = array('address1' => $this->_shipping['Address']['AddressLine'],
                'address2' => '',
                'city' => $this->_shipping['Address']['City'],
                'zip' => $this->_shipping['Address']['PostalCode']);
            $taxData = $this->_order->get_wa_taxrate($data);
            if ($taxData['code'] == '3') {                
                $data['taxRate'] = 0.084;
                $data['amount'] = round($subTotal * 0.084, 2);
                $data['tax_location_name'] = 'local';
                $data['tax_location_code'] = 'local';
            } else {
                $data['taxRate'] = floatval((string) $taxData['rate']);
                $data['amount'] = round(($subTotal * $data['taxRate']), 2);
                $data['tax_location_name'] = (string) $taxData->rate['name'];
                $data['tax_location_code'] = (string) $taxData->rate['code'];
            }
            $this->_salesTax = $data;
        }
    }

}
