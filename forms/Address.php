<?php
class Application_Form_Address extends Zend_Form 
{ 
       
    public function __construct($country)
    {
    //    var_dump( $this->country);
       // echo $country;
         parent::__construct();
         $users = new Application_Model_User;
         $this->addElement(
            'text',
            'firstname',
            array(
                'value'     => '',
                'label'     => 'First Name',
            	'size'	    => 20,
            	'required'  => true,
            	'maxlength' => 64,
            	'filters'   => array('StringTrim'),
                'validators'=>array(
                array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter your contact first name.'
                    )
                ))
            )
        );
        $this->addElement(
            'text',
            'lastname',
            array(
                'value'     => '',
                'label'     => 'Last Name',
            	'size'	    => 20,
            	'required'  => true,
            	'maxlength' => 64,
            	'filters'   => array('StringTrim'),
                'validators'=>array(
                array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter your contact last name.'
                    )
                ))
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
            )
        );         
       
        $this->addElement(
            'text',
            'zipcode',
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
                'value'     => 'US',  
                'label'     => 'Country',                        
            	'required'  => true,
            	'multiOptions' => $countryOptions,            	   
            )
        ); 
       
                
          
        $this->addElement(
            'text', 'phone', array(
            'value'=> '',
            'label' => 'Phone',
            'filters' => array('StringTrim'),
            'size' => 20,
            'maxLength' => 20
                )
        );
               
                 
         
          $this->addDisplayGroup(
	            array('number', 'month', 'year','firstname','lastname','address1', 'address2', 'city', 'zipcode', 'country', 'state', 'phone'), 'login',
	            array('legend' => 'Address Information')
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