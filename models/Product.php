<?php

class Application_Model_Product {
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
    }
    
    public function getCategories() {
        $results = $this->_db->fetchAll("SELECT * from category WHERE status = '1' order by name");
        return $results; 
    }

    public function getCategoryProducts($categoryId) {
        $sql = "select product.*, category.name as categoryName, category.description as categoryDescription from product left join category on product.category_id = category.category_id
            WHERE product.category_id = '$categoryId' AND product.status = '1' order by product.name";          
        $results = $this->_db->fetchAll($sql);
        return $results; 
    }
    
    public function getProductDetail($productId) {
        /*$result = $this->_db->fetchAll("SELECT * FROM product_price left join product ON product_price.product_id = product.product_id   
         WHERE product_price.user_id = '$userId' AND product.product_id = '$productId'");
        if ($result) {
            return $result;
        } else {
            return $this->_db->fetchAll("SELECT * FROM product_price left join product ON product_price.product_id = product.product_id   
         WHERE product.product_id = '$productId' AND product_price.user_id = '1' ");*/
           return $this->_db->fetchRow("SELECT * FROM product WHERE product.product_id = '$productId'"); 
        
    }
    
    public function getUserProductPrices($prouctId, $userId) {
        return $this->_db->fetchAll("SELECT * FROM product_price left join product ON product_price.product_id = product.product_id   
         WHERE product.product_id = '$prouctId' AND product_price.user_id = '$userId' ");
    }
    /*public function getProductByName ($name, $quantity, $userId = 1) {
       
        $result = $this->_db->fetchRow("SELECT product_price.* FROM product_price left join product ON product_price.product_id = product.product_id   
         WHERE product_price.user_id = '$userId' AND product.name = '$name' AND min <= $quantity AND max >=$quantity AND active = 1");
        if ($result) {
            return $result;
        } else {
            $result = $this->_db->fetchRow("SELECT product_price.* FROM product_price left join product ON product_price.product_id = product.product_id   
         WHERE product_price.user_id = '1' AND product.name = '$name' AND min <= $quantity AND max >=$quantity AND active = 1");            
           return $result;
        }
    } */   
                            
    public function getUserProductPrice ($name, $quantity, $userId) {       
        $result = $this->_db->fetchRow("SELECT product_price.*, product.volumn, product.weight
            FROM product_price left join product ON product_price.product_id = product.product_id   
        WHERE product_price.user_id = '$userId' AND product.name = '$name' AND min <= $quantity AND max >=$quantity AND active = 1");      
        return $result;         
    }
    
    public function getProductById($productId) {
        $result = $this->_db->fetchRow("SELECT * FROM product WHERE product_id = '$productId'");
        return $result;
    }
    public function findProductName($queryString) {
        $result = $this->_db->fetchAll("SELECT * FROM product WHERE name like '$queryString%'");
        return $result;
    }
    public function findUserProductName($userId, $queryString) {
        $result = $this->_db->fetchAll("SELECT distinct product_price.product_id, product.name from product_price 
            left join product on product_price.product_id = product.product_id 
            where product_price.user_id  = '$userId' and product.name like '$queryString%'");
        return $result;
    }
    public function getProductOptionsById($productId) {
        $result = $this->_db->fetchAll("SELECT product_option.product_id, option.name, option_value.value, product_option.option_id, product_option.product_option_id FROM product_option 
left join `option` on option.option_id = product_option.option_id
left join option_value on option_value.option_value_id = product_option.option_value_id
where product_option.product_id = '$productId'");
        if ($result) {
            $options = array();
            foreach ($result as $data) {
                $options[$data['name']][$data['product_option_id']] = $data['value'];
            }
            return $options;
        }
        return FALSE;
    }
    
    public function getProductOptions($name) {
        $result = $this->_db->fetchAll("SELECT product_option.product_id, option.name, option_value.value, product_option.option_id, product_option.product_option_id FROM product_option 
left join `option` on option.option_id = product_option.option_id
left join option_value on option_value.option_value_id = product_option.option_value_id
where product_option.product_id  in (SELECT product_id from product where name = '$name')");
        if ($result) {
            $options = array();
            foreach ($result as $data) {
                $options[$data['name']][$data['product_option_id']] = $data['value'];
            }
            return $options;
        }
        return FALSE;
    }
    public function getAdminProduct() {
        $result = $this->_db->fetchAll("SELECT product.*, category.name as categoryName FROM product left join category on product.category_id = category.category_id 
            ORDER BY product.name");
        return $result;
    }
    public function getAllProduct() {
        $result = $this->_db->query("SELECT product.*, category.name as categoryName FROM product left join category on product.category_id = category.category_id 
            ORDER BY categoryName, product.name");
        return $result;
    }    
    public function updateProduct($data, $id) {
        try {                    
           $update = $this->_db->update('product', $data, $this->_db->quoteInto("product_id = ?", $id));           
            return $update;
        } catch (Exception $e) {
           // echo $e->getMessage();
            return $e->getMessage();
        }        
        return null;
    }
     public function updateCategory($data, $id) {
        try {                    
           $update = $this->_db->update('category', $data, $this->_db->quoteInto("category_id = ?", $id));           
            return $update;
        } catch (Exception $e) {
           // echo $e->getMessage();
            return $e->getMessage();
        }        
        return null;
    }  
     public function save_category($data) {
       try {           
            $this->_db->insert('category', $data);             
            return $this->_db->lastInsertId();
        } catch (Exception $e) {   
            return $e->getMessage();
        }
    }
   /* public function updateProductPrice($data, $id) {
        try {
           //check if the product name exists
           if (isset($data['name'])) {
                $data['name'] = ucwords(trim($data['name']));
                if ($product = $this->_db->fetchRow("SELECT product_id FROM product WHERE name = '{$data['name']}'")) {
                    $data['product_id'] = $product['product_id'];
                } else {
                    unset($data['product_id']);
                }
                unset($data['name']);
            }
            unset($data['product_price_id']);
            $update = $this->_db->update('product_price', $data, $this->_db->quoteInto("product_price_id = ?", $id));
            return $update;
        } catch (Exception $e) {
           // echo $e->getMessage();
            return $e->getMessage();
        }        
        return null;
    } */
    public function updateProductPrice($productPriceId, $price) {
        //first need to check if it's above the min price        
        $result = $this->_db->fetchRow("SELECT pp.min_price, pp.price FROM `product_price` pp  
            left join product_price ppp on ppp.product_id = pp.product_id and ppp.min = ppp.min and ppp.max = pp.max and ppp.user_id = 1
            WHERE pp.product_price_id = '$productPriceId'");
        if (floatval($price) < floatval($result['min_price'])) {
            return 2;
        }
        if (floatval($price) == floatval($result['price'])) {
            return 3;
        }
        $data = array('price' => $price,'date_modified'=> date("Y-m-d H:i:s"));
        $update = $this->_db->update('product_price', $data, $this->_db->quoteInto("product_price_id = ?", $productPriceId));
        return $update;
    }
    
    public function insertProductPrice($data) {
        try {
           //check if the product name exists
           $data['name'] = ucwords(trim($data['name']));        
           if ($product = $this->_db->fetchRow("SELECT product_id FROM product WHERE name = '{$data['name']}'")) {                
               $data['product_id'] = $product['product_id'];               
           } else {
               return null;
           }
           unset($data['name']);
           unset($data['product_price_id']);    
           
           $insert = $this->_db->insert('product_price', $data);           
           return $insert;
        } catch (Exception $e) {
           // echo $e->getMessage();
            return $e->getMessage();
        }        
        return null;
    }
    //first check if userid, product id pair in the price table, if not then grap the price matrix and insert
    public function user_product_price($userId, $productId) {
        //check exists
        $check = $this->_db->fetchAll("SELECT product_price_id FROM product_price WHERE user_id = '$userId' AND product_id = '$productId' AND active = 1");
        if(!$check) {
           // echo "Insert";
          //grap the generic rows
          $products = $this->_db->fetchAll("SELECT * from product_price WHERE product_id = '$productId' AND user_id = 1 and active = 1");
          foreach ($products as $product) {
              $data = array('user_id' => $userId,'product_id' => $productId, 
                  'min' => $product['min'], 'max' => $product['max'],'price'=> $product['price'],
                  'unit' => $product['unit'], 'min_price' =>$product['min_price'], 'unit_description'=>$product['unit_description'], 'date_modified'=> date("Y-m-d H:i:s"));
               $insert = $this->_db->insert('product_price', $data); 
               /*if ($insert == 1) {
                   echo $product['product_id'];
               }*/
          }
        } 
    }
    
    public function user_unselect_product($userId, $products) {
        if($this->_db->query("DELETE FROM product_price WHERE user_id = '$userId' AND active = 1 and product_id not in ($products) ")) {
            return TRUE;
        }
    }
    public function delete_all_user_products($userId) {
        if($this->_db->query("DELETE FROM product_price WHERE user_id = '$userId' AND active = 1 ")) {
            return TRUE;
        } 
    }
    
    public function user_product($userId) {
        return $this->_db->fetchAll("SELECT distinct product_id FROM product_price WHERE user_id = '$userId' AND active = 1");
    }
    
    public function save_product($data) {
       try {           
            $insert = $this->_db->insert('product', $data);     
            $productId = $this->_db->lastInsertId();          
            return $productId;                       
        } catch (Exception $e) {   
            return $e->getMessage();
        }  
    }
    public function getUserProduct($userId = '') {
        $product = $this->_db->fetchAll("SELECT pp.product_price_id,pp.product_id, ppp.price as standardPrice, pp.min, pp.max, pp.price, pp.min_price, pp.unit, pp.unit_description, p.name
            FROM `product_price` pp  left join product p on  pp.product_id = p.product_id 
left join product_price ppp on ppp.product_id = pp.product_id and ppp.min = ppp.min and ppp.max = pp.max and ppp.user_id = 1
            WHERE pp.user_id = '$userId' and pp.active = 1
");
        return $product;
    }
           
}
