<?php
class Application_Form_Signup extends Zend_Form 
{ 
    
    public function __construct($country)
    {    
         parent::__construct();
         $users = new Application_Model_User;
        /*$this->addElement('text', 'age', array(
            'label'=>'Age',
            'maxlength'=>2,
            'class'=>'age',
            'required'=>true,
            'filters'=>array('StringTrim'),
            'validators'=>array(
                array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter your age.'
                    ),
                    'breakChainOnFailure'=>true
                ),
                array(
                    'validator'=>'Int',
                    'options'=>array(
                        'messages'=>'Age must be a number.'
                    ),
                    'breakChainOnFailure'=>true
                ),
                array(
                    'validator'=>'between',
                    'options'=>array(
                        'min'=>8,
                        'max'=>10,
                        'messages'=>array(
                            Zend_Validate_Between::NOT_BETWEEN => 'This is for %min% to %max% years old.'
                        )
                    )
                ),

            ),
            'decorators'=>array(
                'ViewHelper',
                'Errors',
                array(array('control'=>'HtmlTag'), array('tag'=>'div', 'class'=>'fieldcontrol')),
                array('Label', array('tag'=>'div', 'class'=>'age')),
                array(array('row'=>'HtmlTag'), array('tag' => 'div', 'class'=>'row')),
            ),
        ));*/
      
        $this->addElement(
            'text',
            'businessname',
            array(
                'value'     => '',
                'label'     => 'Business Name',
            	'size'	    => 20,
            	'required'  => true,
            	'maxlength' => 128,
            	'filters'   => array('StringTrim'),                
                'validators'=>array(
                array(
                    'validator'=>'NotEmpty',                   
                    'options'=>array(
                        'messages'=>'Please enter your business name.'
                    )
                )
            ))
        );
        
      
        $businessTypes = $users->getBusinessType();
        foreach ($businessTypes as $key=>$value) {                       
            $businessType[$key] = $value;
        }
        unset($businessType['Planet Beach']);
        $this->addElement(
            'select',
            'businesstype',
            array(               
                'label'        => 'Business Type',
            	'required'     => true,
            	'multiOptions' => $businessType               
            )
        );   
        
        $customerType = array('Business' => 'Business', 'Individual'=>'Individual');
        $this->addElement(
            'radio',
            'customertype',
            array(               
                'label'        => 'Customer Type',
            	'required'     => true,
            	'multiOptions' => $customerType
            )
        ); 
        
        $soldbyOptions = $users->getSoldByOptions();
        $soldbyOptions[""] = "";
        asort($soldbyOptions);
        $this->addElement(
            'select',
            'soldby',
            array(               
                'label'        => 'Sold By',
            	'required'     => true,
            	'multiOptions' => $soldbyOptions               
            )
        );
        
         $this->addElement(
            'text',
            'address1',
            array(
                'value'     => '',
                'label'     => 'Address #1',
            	'size'	    => 20,
            	'required'  => false,
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
            	'required'  => false,
            	'maxlength' => 128,
            	'filters'   => array('StringTrim')         
            )
        );
        
        $regions = $users->getRegions($country);
        foreach ($regions as $region) {                       
            $stateOptions[$region['code']] = $region['name'];
        }
      //  var_dump($stateOptions);
        
        $this->addElement(
            'select',
            'state',
            array(                
                'value'     => '',
                'label'     => 'State/Province',            
            	'required'  => true,
                'registerInArrayValidator' => false,
            	'multiOptions' => $stateOptions,            	   
            )
        );      
        $this->addElement(
            'text',
            'zip',
            array(              
                'label'      => 'Zip',            	
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'		 => 20,
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
            'text', 'businessphone', array(
            'label' => 'Phone',
            'filters' => array('StringTrim'),
            'size' => 20,
            'maxLength' => 20
                )
        );

      /*  $phone = new Application_Form_Element_Phone('phone');
        $phone->setLabel('Business Phone')
                ->addValidator('digits');
        $this->addElement($phone);*/
        
    
       
       $source = new Application_Form_Element_Source('source');
       $source->setLabel('');                
       $this->addElement($source);

      $this->addElement(
            'text', 'website', array(
            'value' => '',
            'label'=> 'Web Site',
            'size' => 20,
            'required' => false,
            'maxlength' => 128,
            'filters' => array('StringTrim')           
            )
       );
       
            
        $this->addElement(
            'textarea', 'interest', array(
            'value' => '',
            'label' => 'Please tell us what your interest is in teeth whitening or ask us any questions here: ',
            'size' => 20,
            'required' => true,            
            'filters' => array('StringTrim'),
            'validators'=>array(
                array(                    
                    'StringLength', false, array(4, 3000),
                    'options'=>array(
                        'messages'=>'Please enter your contact first name.'
                    )
                )),
            'attribs'=>array('cols' => '28', 'rows'=> '6')   
                )
        );

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
            'contactphone',
            array(
                'value'     => '',
                'label'     => 'Phone',
            	'size'	    => 20,
            	'required'  => false,
            	'maxlength' => 32,
            	'filters'   => array('StringTrim')         
            )
        );
        $this->addElement(
            'text',
            'email',
            array(               
                'label'     => 'Email',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 128,
               'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter an email address.',
                         'breakChainOnFailure'=>true
                    )
                ),
                   array(
                    'validator'=>'EmailAddress',
                    'options'=>array(
                        'messages'=>'Please enter a valid email address.'
                    )
                )
                   )             
            )
        );
        
        $this->addElement(
            'text',
            'contactphone2',
            array(
                'value'     => '',
                'label'     => 'Second Phone',
            	'size'	    => 20,
            	'required'  => false,
            	'maxlength' => 32,
            	'filters'   => array('StringTrim')         
            )
        );
        $this->addElement(
            'text',
            'email2',
            array(               
                'label'     => 'Second Email',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => false,
                'maxLength'  => 128,                
               'validators'=>array(
                
                   array(
                    'validator'=>'EmailAddress',
                    'options'=>array(
                        'messages'=>'Please enter a valid email address.'
                    )
                )
                   )     
                   
            )
        );
                     
        //$status = array('pending'=>'Pending', 'disabled'=>'Disabled', 'active'=>'Active');
        $status  = $users->getAccountStatus();
        
        $this->addElement(
            'select', 'status', array(
            'label' => 'Account Status',
            'filters' => array('StringTrim'),
           
            'required' => true,
            'multiOptions' => $status,
            'validators' => array(
                array(
                    'validator' => 'NotEmpty',
                    'options' => array(
                        'messages' => 'Please set user status.'
                    )
                )
            )
                )
        );
        
         $this->addElement(
            'password',
            'password',
            array(
                'value'     => '',
                'label'     => 'Password',
            	'size'	    => 20,
            	'required'  => true,
            	'maxlength' => 32,
            	'filters'   => array('StringTrim'),
                'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'StringLength', false, array(6),
                    'options'=>array(
                        'messages'=>'Please enter a password.',
                         'breakChainOnFailure'=>true
                    ))
                )
            )
        );
         
         $this->addElement(
            'password',
            'confirm_password',
            array(
                'value'     => '',
                'label'     => 'Confirm Password',
            	'size'	    => 20,
            	'required'  => true,
            	'maxlength' => 32,
            	'filters'   => array('StringTrim'),
                'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please confirm password.',
                         'breakChainOnFailure'=>true
                    ))
                )
            )
        );
        
               
        $users = new Application_Model_User;
        $salesUsers = $users->getSalesUsers();
        $salesRep = array('');
        
        foreach ($salesUsers as $salesUser) {
            //$salesRep[$salesUser["id"]] = $salesUser['firstname'].' '.$salesUser['lastname']; 
            $salesRep[$salesUser["id"]] = $salesUser['name'];
        }
        $this->addElement(
            'select', 'parentAccountID', array(
            'label' => 'Assign Representative',
            'filters' => array('StringTrim'),           
            'multiOptions' => $salesRep
            
            )
        );
        
        $leadSources = $users->getLeadSource();
        $this->addElement(
            'select', 'imported', array(
            'label' => 'Initial Contact Via',
            'required'  => true,
            'filters' => array('StringTrim'),           
            'multiOptions' => $leadSources            
            )
        );
       
        $this->addElement(
            'captcha', 'captcha', array(
            'label' => 'Please tell us you are a human',
            'required'  => true,
            'filters' => array('StringTrim'),           
             'captcha' => array(           
        'captcha' => 'Image',          
        'wordLen' => 6,          
        'timeout' => 300,  
        'width' => 250,
                'height' => 100,
                'imgUrl' => '/biz/data/capcha',
                'imgDir' => '../biz/data/capcha',
                'font' => '../biz/data/font/LiberationSans-Regular.ttf',
                'dotNoiseLevel'=> 5,
                'lineNoiseLevel'=> 5 
            )
        ));
        
        
               
        $this->addElement('hidden', 'id');
        
                    
   /*     $email = $this->createElement('text','email');
        $email->setLabel('Email: ')
                ->setRequired(true);
                
        $username = $this->createElement('text','username');
        $username->setLabel('Username: ')
                ->setRequired(true);
                
        $password = $this->createElement('password','password');
        $password->setLabel('Password: ')
                ->setRequired(true);
                
        $confirmPassword = $this->createElement('password','confirmPassword');
        $confirmPassword->setLabel('Confirm Password: ')
                ->setRequired(true);
                
        $register = $this->createElement('submit','register');
        $register->setLabel('Sign up')
                ->setIgnore(true);
        
         
                
        $this->addElements(array(
                        $firstname,
                        $lastname,
                        $email,
                        $username,
                        $password,
                        $confirmPassword,
                        $register
        ));*/
        
        
        
        $this->addDisplayGroup(
	            array('businessname', 'businesstype', 'customertype', 'soldby','address1', 'address2', 'city', 'zip',
                        'country', 'state', 'businessphone', 'interest', 'captcha', 'status', 'imported','id', 'parentAccountID'), 'business',
	            array('legend' => 'Business Info')
	          ); 
        $registerElements = array('firstname', 'lastname', 'contactphone', 'email', 'contactphone2', 'email2', 'password', 'confirm_password'); 
        $this->addDisplayGroup(
	            $registerElements, 'contact',
	            array('legend' => 'Main Contact Info')
	          ); 
          
        if (preg_match("/crm/i", $_SERVER['REQUEST_URI'])) {
            $this->addDisplayGroup(
	            array('source'), 'formsource',                                    
	            array('legend' => 'Lead Source *')
	          ); 
        } else {          
            $this->addDisplayGroup(
	            array('source'), 'formsource',                                    
	            array('legend' => 'How did you hear about us? *')
	          ); 
        }
        
          $this->addElement(
            'submit',
            'submit',
            array(               
                'label'     => 'Submit',
            	'ignore' => TRUE              
            )
        );
    }
	
    
}