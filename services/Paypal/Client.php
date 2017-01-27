<?php

class Application_Service_Paypal_Client extends Zend_Http_Client {

   // public $api_expresscheckout_uri = 'https://www.sandbox.paypal.com/webscr';
    
   private $_api_version = '84';
   private $_log_file; 
   // TODO: Originate these values from a secure source.
  //  private $_api_username = 'jing-facilitator_api1.beamingwhite.com'; // These differ depending if you're in live mode or test mode.
  //  private $_api_password = '1390409177';
  //  private $_api_signature = 'A0PnVs9fboc0ouiyrEKiXoq8-CH9AdNCSJA6TWDQRs-xyS7DXlS8JPKz';
    public $API_Endpoint;
    public $PAYPAL_URL;
    public $PAYPAL_DG_URL;
    public $SandboxFlag = true;
  
    public function __construct($uri = null, $options = null) {
      
      parent::__construct($uri, $options);
      
      
       $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
       $auth = $config->toArray();

       $this->setParameterGet('USER', $auth['paypalUserName']);
       $this->setParameterGet('PWD', $auth['paypalPassword']);
       $this->setParameterGet('SIGNATURE', $auth['paypalSignature']);
       $this->setParameterGet('VERSION', $this->_api_version);
       $this->_log_file = $auth['paypalLogPath'] . date("Ymd") . '.txt';
      
      if ($this->SandboxFlag == true)
      {
         $this->API_Endpoint    = 'https://api-3t.sandbox.paypal.com/nvp';
         $this->PAYPAL_URL      = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=';
         $this->PAYPAL_DG_URL   = 'https://www.sandbox.paypal.com/incontext?token=';
      }
      else
      {
         $this->API_Endpoint      = 'https://api-3t.paypal.com/nvp';
         $this->PAYPAL_URL      = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
         $this->PAYPAL_DG_URL   = 'https://www.paypal.com/incontext?token=';
      }
      
      $this->setUri($this->API_Endpoint);
   }
   
   /*
    * Request an authorization token.
    *
    * @param float $paymentAmount
    * @param string $returnURL
    * @param string $cancelURL
    * @param string $currencyID
    * @param string $paymentType Can be 'Authorization', 'Sale', or 'Order'. Default is 'Authorization'
    * @return Zend_Http_Response
    */
   public function ecSetExpressCheckout($paymentAmount, $returnURL, $cancelURL, $currencyID, $items, $paymentType = 'Authorization') {
      $this->setParameterGet('METHOD', 'SetExpressCheckout');
    
      $this->setParameterGet('RETURNURL', $returnURL);
      $this->setParameterGet('CANCELURL', $cancelURL);
      $this->setParameterGet('PAYMENTREQUEST_0_CURRENCYCODE', $currencyID);
      $this->setParameterGet('REQCONFIRMSHIPPING', 0);
  //    $this->setParameterGet('NOSHIPPING', 0);
    
       $this->setParameterGet('PAYMENTREQUEST_0_AMT',$paymentAmount);      
       $this->setParameterGet('PAYMENTREQUEST_0_PAYMENTACTION', $paymentType); // Can be 'Authorization', 'Sale', or 'Order'
     
       $request =  $this->request(Zend_Http_Client::GET);      
       file_put_contents($this->_log_file, "ecSetExpressCheckout Request: \n" . $this->getLastRequest() . "\n", FILE_APPEND);
       file_put_contents($this->_log_file, "ecSetExpressCheckout Response: \n" .$this->getLastResponse() . "\n", FILE_APPEND);
       return $request;
   }
   
   function ecDoExpressCheckout($token, $payer_id, $par, $currencyID, $payment_action = 'Sale') {
       
        $this->setParameterGet('METHOD', 'DoExpressCheckoutPayment');
        $this->setParameterGet('TOKEN', $token);
        $this->setParameterGet('PAYERID', $payer_id);
        $this->setParameterGet('PAYMENTACTION', $payment_action); // Can be 'Authorization', 'Sale', or 'Order'
        $this->setParameterGet('PAYMENTREQUEST_0_CURRENCYCODE', $currencyID);
           
       
     
      /*  $index = 0;
        foreach ($par['items'] as $item) {
            if (is_object($item)) {
                $this->setParameterGet('L_PAYMENTREQUEST_0_NAME' . $index, $item->name);
                $this->setParameterGet('L_PAYMENTREQUEST_0_AMT' . $index, $item->price);
                $this->setParameterGet('L_PAYMENTREQUEST_0_QTY' . $index, $item->qty);
                ++$index;
            }
        }*/
              
        $this->setParameterGet('PAYMENTREQUEST_0_ITEMAMT', $par['itemAmt']);
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPPINGAMT', $par['shippingAmt']);
        $this->setParameterGet('PAYMENTREQUEST_0_TAXAMT', $par['taxAmt']);
        $this->setParameterGet('PAYMENTREQUEST_0_AMT', $par['finalTotal']);
        $this->setParameterGet('PAYMENTREQUEST_0_INVNUM', $par['orderId']);  
       
       // echo '<pre>';
        //var_dump($par);
        //die();
        /*
        $this->setParameterGet('NOSHIPPING',1);      
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPTONAME', $par['shipName']);
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPTOSTREET', $par['address']['AddressLine']);
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPTOCITY', $par['address']['City']);
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPTOSTATE', $par['address']['StateProvinceCode']);
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPTOZIP', $par['address']['PostalCode']);
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE', $par['address']['CountryCode']);
        $this->setParameterGet('PAYMENTREQUEST_0_SHIPTOPHONENUM','');
      */
        $request = $this->request(Zend_Http_Client::GET);
        
        file_put_contents($this->_log_file, "ecDoExpressCheckout Request: \n" . $this->getLastRequest() . "\n", FILE_APPEND);
        file_put_contents($this->_log_file, "ecDoExpressCheckout Response: \n" .$this->getLastResponse() . "\n", FILE_APPEND);   
         
        return $request;
   }
   
   /*
    * Parse a Name-Value Pair response into an object.
    * @param string $response
    * @return object Returns an object representation of the response.
    */
   public static function parse($response) {
   
      $result = array();
      parse_str($response, $result);
   
      if (empty($result))
         return NULL;         
   
      return (object) $result;
   }


}

?>


