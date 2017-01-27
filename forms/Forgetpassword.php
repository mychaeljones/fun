<?php
class Application_Form_Forgetpassword extends Zend_Form 
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
         $this->addDisplayGroup(
	            array('email', 'submit'), 'reset',
	            array('legend' => 'Reset Password')
	          ); 
       
       $this->addElement(
            'submit',
            'submit',
            array(               
                'label'     => 'Reset Password',
            	'ignore' => TRUE,
                'attribs'=>array('style' => 'margin-left:200px;float:left;')                
            )
        );
    }
	
    
}