<?php

class TrainingController extends Zend_Controller_Action {

    public function init() {
        if (!Zend_Auth::getInstance()->getIdentity() ) {                
           $this->_helper->redirector('login', 'user');
        }
        $this->_users = new Application_Model_User;  
        $this->_support = new Application_Model_Support;  
        $this->_auth = Zend_Auth::getInstance()->getIdentity();        
    }
    public function indexAction() {
        
    }
    
    public function preExamAction() {
        
    }
    public function examAction() {
        
    }
    
    public function examResultsAction() {
        if ($this->getRequest()->isPost()) {           
            $answers = array(array(1=>'1'), // Question 1
                             array(1=>'1'),
                             array(0=>'1', 1=>'1', 4=>'1', 6=>'1', 7=>'1'),
                             array(0=>'1'),
                             array(3=>'1'), // Question 5
                             array(0=>'1'),
                             array(1=>'1'),
                             array(1=>'1'),
                             array(0=>'1'),
                             array(2=>'1'), // Question 10
                             array(0=>'1'),
                             array(2=>'1', 3=>'1'),
                             array(0=>'1',2=>'1', 3=>'1'),
                             array(1=>'1'),
                             array(2=>'1'), // Question 15
                             array(0=>'1', 1=>'0', 2=>'1', 3=>'0'),
                             array(1=>'1', 2=>'1'),
                             array(0=>'1', 2=>'1'),
                             array(0=>'1', 2=>'1'),
                             array(2=>'1') // Question 20
                
            );
            $score = 0; 
            //correct answers
            $goodAnswers = array();
            if (isset($_POST['q'])) {
                foreach ($answers as $key => $answer) {               
                    if(isset($_POST['q'][$key])) {                        
                        if ($answer == $_POST['q'][$key]) {
                            ++$score;
                            $goodAnswers[] = $key;
                        }
                    }
                }
            }
            $this->view->score = $score;
            $this->view->goodAnswers = $goodAnswers;
            /*echo "total correct:$score";
            
            echo 'Correct answers: ';
            foreach ($goodAnswers as $value) {
                echo "question " .($value + 1).',';
            }*/
            //find user rep
            $rep = $this->_users->getSalesRep ($this->_auth->id);
            //send mail
            $mail = new Zend_Mail();
            $name = $this->_auth->firstname . ' ' . $this->_auth->lastname;
            $message = "Dear $name, <br><br>";
            if ($score < 16) {
                $message .= "Unfortunately you have failed the exam.<br>";
            }else {
                $message .= "Congratulations you have passed the exam.<br>";
            }             
            $message .= "This is the exam result from Beamingwhite:  <br><br>
                    Total Score: $score out of 20. 
                    <br><br>
                    Regards, <br>
                    {$rep['name']}";                    

            $mail->setBodyHTML($message);
            $mail->setFrom($rep['email'], 'Beaming White');
            $mail->addTo($this->_auth->email, $name);
            $mail->addCc($rep['email'], $rep['name']);
            $mail->setSubject('Beaming White Exam Result');            
            $mail->send();
        }
    }
    
    public function classAction() {
        
    }
    
    public function part1LightsAction() {
    
    }
    
    public function part2GelsAction() {
        
    }
    
    public function part3LegalAction() {
        
    }
    
    public function part4SafetyAction() {
    
    }
    
    public function part5StainingAction() {
    
    }

    public function part6ExpectationsAction() {
    
    }
    
    public function part7HowTeethWhiteningWorksAction() {
    
    }
    
    public function part8DentalConditionsAction() {
    
    }
}
