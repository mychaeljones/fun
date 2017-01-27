<?php
class Application_Form_Card extends Zend_Form 
{ 
   public function __construct($country)
   {    
        parent::__construct();
        $users = new Application_Model_User;
         
        $this->addElement(
            'text',
            'number',
            array(               
                'label'     => 'Card Number',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 16,
               'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',                    
                    'options'=>array(
                        'messages'=>'Please enter your card number.',
                         'breakChainOnFailure'=>true
                    )
                ),
                    array(
                  'validator'=>'CreditCard',
                    'options'=>array(
                        'messages'=>'Please enter a valid card number.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
        
              
        $this->addElement(
            'text',
            'firstName',
            array(       
                'value'     => '',
                'label'     => 'First Name',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 50,
                'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter name on card.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
        
        $this->addElement(
            'text',
            'lastName',
            array(               
                'label'     => 'Last Name',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 50,
                'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter name on card.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
        
      $month = array('01' => '01', '02'=>'02', '03'=>'03', '04' => '04', '05'=>'05', '06'=>'06',  
            '07' => '07', '08'=>'08', '09'=>'09', '10' => '10', '11'=>'11', '12'=>'12');
         
      $this->addElement(
            'select',
            'month',
            array(               
                'label'        => 'Expires',
            	'required'     => true,
                'value' =>  date("m"),
            	'multiOptions' => $month               
            )
        );
       
        for ($i = 0; $i < 20; ++$i) {       
            $year[date("Y") + $i] = date("Y") + $i;
        }
       
        $this->addElement(
            'select',
            'year',
            array(                              
            	'required'     => true,
            	'multiOptions' => $year,
                'value' =>  date("Y")                    
            )
        ); 
        
         
         $this->addElement(
            'text',
            'address1',
            array(
                'value'     => '',
                'label'     => 'Address #1',
            	'size'	    => 20,
            	'required'  => true,
            	'maxlength' => 128,
            	'filters'   => array('StringTrim')         
            )
        );
         $this->addElement(
            'text',
            'address2',
            array(
                'value'     => '',
                'label'     => 'Address #2',
            	'size'	    => 20,
            	'required'  => false,
            	'maxlength' => 128,
            	'filters'   => array('StringTrim')         
            )
        );
         $this->addElement(
            'text',
            'city',
            array(
                'value'     => '',
                'label'     => 'City',
            	'size'	    => 20,
            	'required'  => true,
            	'maxlength' => 128,
            	'filters'   => array('StringTrim')         
            )
        );
        
        $regions = $users->getRegions($country);
        if (empty($regions)) {
            $regions = $users->getRegions('US');
        }
        foreach ($regions as $region) {                       
            $stateOptions[$region['code']] = $region['name'];
        }
      
        $this->addElement(
            'select',
            'state',
            array(                
                'value'     => '',
                'label'     => 'State',            
            	'required'  => true,
                'registerInArrayValidator' => false,
            	'multiOptions' => $stateOptions,      
                'attribs'=>array('width' => '200px;')                
            )
        );      
        
        $this->addElement(
            'text',
            'zip',
            array(              
                'label'      => 'Zip',            	
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'       => 20,
                'required'   => true,
           	'maxLength'  => 10
            )
        ); 
      /*  $zipcode = $this->getElement('zip');        
        $zipcode->addValidator('regex', false, array('/^([0-9]{5})(-[0-9]{4})?$/i',
        'messages' => 'Please Enter a valid zip'));
        */
        $countries = $users->getCountries();
        foreach ($countries as $country) {                       
            $countryOptions[$country['iso_code_2']] = $country['name'];
        }
               
        $this->addElement(
            'select',
            'country',
            array(    
                'value'     => '',  
                'label'     => 'Country',                        
            	'required'  => true,
            	'multiOptions' => $countryOptions,     
                'attribs'=>array('width' => '200px;')              
            )
        ); 
       
                  
        $this->addElement(
            'text', 'phone', array(
            'label' => 'Phone',
            'filters' => array('StringTrim'),
            'size' => 20,
            'maxLength' => 20
                )
        );
                
         $this->addDisplayGroup(
	            array('number', 'month', 'year','firstName','lastName', 'address1', 'address2', 'city', 'zip', 'country', 'state', 'phone', 'submit'), 'card',
	            array('legend' => 'Enter a credit card')
	          ); 
         
          $this->addElement(
            'submit',
            'submit',
            array(               
                'label'     => 'Submit',
            	'ignore' => TRUE,
                'attribs'=>array('style' => 'margin-left:200px;float:left;')                
            )
        );
    }
	
    
}