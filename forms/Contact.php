<?php
class Application_Form_Contact extends Zend_Form 
{ 
    public function init()
    {
               
        
        $this->addElement(
            'text',
            'name',
            array(               
                'label'     => 'Name',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 32,
                'required'  => true,
                'maxLength'  => 64,
                'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter your name.',
                         'breakChainOnFailure'=>true
                    )
                )
                 
                   )             
            )
        );
                
        $this->addElement('text','businessname',array('label' => 'Account Name','size' => 32,'attribs' => array('readonly' => 'true')));
         $this->addElement(
            'text',
            'contactEmail',
            array(               
                'label'     => 'Email',
            	'filters'    => array('StringTrim'),                 	            	             	
            	'size'     => 32,
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
         
        $topics = array(''=>'', 'marketing'=> 'marketing', 'order' => 'order', 'payment'=>'payment', 
            'product' =>'product','training'=>'training', 
            'other'=>'other');        
        $this->addElement(
            'select',
            'subject',
            array(                
                'value'     => '',
                'label'     => 'Subject',            
            	'required'  => true,
                'registerInArrayValidator' => false,
            	'multiOptions' => $topics,   
                'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please choose a subject.',
                         'breakChainOnFailure'=>true
                    )
                )                 
                   )
            )
        );
        $this->addElement(
            'textarea',
            'message',
            array(                
                'value'     => '',
                'label'     => 'Message',            
            	'required'  => true,
                'registerInArrayValidator' => false,
                'validators'=>array(
                 array(
                    'validator'=>'NotEmpty',
                    'options'=>array(
                        'messages'=>'Please enter a message.',
                         'breakChainOnFailure'=>true
                    )
                )                 
                   ),
            	'attribs' => array('cols' => '60', 'rows'=>6)         	   
            )
        );
        
        $this->addElement(
            'submit',
            'submit',
            array(               
                'label'     => 'Submit',
            	'ignore' => TRUE
                        
            )
        );
        
         $this->addDisplayGroup(
	            array('name', 'businessname','contactEmail','subject', 'message','submit'), 'contactus',
	            array('legend' => 'Contact Us')
	          ); 
         
         
    }
	
    
}