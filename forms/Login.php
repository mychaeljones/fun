<?php
class Application_Form_Login extends Zend_Form 
{ 
    public function init()
    {
               
        
        $this->addElement(
            'text',
            'email',
            array(               
                'label'     => 'Email',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 32,
               'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter your email.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
        
        $this->addElement(
            'password',
            'password',
            array(               
                'label'     => 'Password',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 32,
               'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter a password.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
        
        
         
        
        
         $this->addDisplayGroup(
	            array('email', 'password', 'submit'), 'login',
	            array('legend' => 'Business Account Sign In')
	          ); 
         
          $this->addElement(
            'submit',
            'submit',
            array(               
                'label'     => 'Log In',
            	'ignore' => TRUE,
                'attribs'=>array('style' => 'margin-left:200px;float:left;')                
            )
        );
    }
	
    
}