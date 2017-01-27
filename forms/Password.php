<?php
class Application_Form_Password extends Zend_Form 
{ 
    public function init()
    {
                        
        $this->addElement(
            'password',
            'old_password',
            array(               
                'label'     => 'Old/Temporary Password',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 32,
               'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter old/temporary password.',
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
                'label'     => 'New Password',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 32,
               'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter a new password.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
         
        $this->addElement(
            'password',
            'confirm_password',
            array(               
                'label'     => 'Confirm New Password',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 20,
                'required'  => true,
                'maxLength'  => 32,
               'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please confirm new password.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
        
        
         $this->addDisplayGroup(
	            array('old_password', 'password', 'confirm_password'), 'login',
	            array('legend' => 'Change Password')
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