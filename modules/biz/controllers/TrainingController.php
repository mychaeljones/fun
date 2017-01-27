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
           
                     
            $goodAnswers = array();   $result = array(); $officialAnswer = array();         
            if (isset($_POST['q'])) {
                foreach ($answers as $key => $answer) { 
                    //get correct answer
                    $correctAnswer = '';
                    foreach($answer as $index=> $value) {
                        if ($key == 3 || $key == 8 || $key == 10) {
                            $correctAnswer .= 'No';
                        } elseif ($key == 15) {
                            $correctAnswer = 'Right Wrong Right Wrong';
                        } 
                        else {
                            $correctAnswer .= ($index + 1) .' ';
                        }
                    }
                    if(isset($_POST['q'][$key])) {
                        $yourAnswer = '';  
                        $yourAnswer15 = '';
                        if (isset($_POST['q'][15])) {
                            for($i = 0; $i < 4; ++$i ) {
                                if (isset($_POST['q'][15][$i])) {
                                    $yourAnswer15 .= $_POST['q'][15][$i] == '1'?'Right ':'Wrong ';
                                } else {
                                    $yourAnswer15 .=  "Missing ";
                                }
                            }
                        }                               
                        foreach($_POST['q'][$key] as $index=> $value) {
                            if ($key == 3 || $key == 8 || $key == 10 ) {
                                $yourAnswer .= $value == '1'?'No':'Yes';
                            } elseif ($key == 15) {                                                                         
                                $yourAnswer .= $yourAnswer15;
                                break;
                            } else {
                                $yourAnswer .= ($index + 1) .' ';
                            }
                        }                   
                        if ($answer == $_POST['q'][$key]) {
                            ++$score;
                            //$goodAnswers[] = $key;                                                                                    
                            $result[$key] = "<br><br>Quesstion ". ($key + 1) . ": Correct<br>Your Answer: $correctAnswer<br>Score: 1";                           
                            $officialAnswer[$key]= "<br>Correct Answer: $correctAnswer";
                        } else {                            
                            $extra = 0; $partial='Wrong';
                            //if only one answer, no need to compare
                            if(count($answer) > 1 && $key != 15) {
                                $extra = 0.5;
                                foreach ($_POST['q'][$key] as $index=> $value) {                                
                                    //if the index is not in the array, means at least one wrong answers                                                             
                                    if(!in_array($index, array_keys($answer))) {
                                        $extra = 0;
                                        break;
                                    }                                
                                }
                                if($extra > 0) {
                                    $partial = 'Partially Correct';
                                    $score = $score + $extra;
                                } 
                            }//end multiple answers
                            $result[$key] = "<br><br>Quesstion ". ($key + 1) . ": $partial"                          
                            . "<br>Your Answer: $yourAnswer <br>Score: $extra";
                            $officialAnswer[$key] = "<br>Correct Answer: $correctAnswer";
                        }
                    } else {
                        $result[$key]= "<br><br>Quesstion ". ($key + 1) . ": Wrong".
                         "<br>Your answer: <br>Score: 0";
                        $officialAnswer[$key] = "<br>Correct Answer: $correctAnswer";
                    }
                }
            }
            $this->view->score = $score;
            $this->view->goodAnswers = $goodAnswers;
     
            $this->_users->saveExam(array('score'=>$score, 'result' => serialize($_POST['q']), 'exam_time'=>date("Y-m-d H:i:s")), $this->_auth->id);
            //find user rep
            $rep = $this->_users->getSalesRep($this->_auth->id);
            //send mail
            $mail = new Zend_Mail();
            $name = $this->_auth->firstname . ' ' . $this->_auth->lastname;
            $message = "Dear $name, <br><br>";
            if ($score < 16) {
                $message .= "Unfortunately you have failed the exam.<br>";
            }else {
                $message .= "Congratulations you have passed the exam.<br>";
            }             
            $message .= "Here is the exam result:  <br><br>                    
                    <strong>Final Score:$score out of 20 </strong>";
            
            for($i = 0; $i < sizeof($result); ++ $i) {
                    $message .= $result[$i];
                    if($score >= 16) {
                        $message .= $officialAnswer[$i];
                    }
            }
            
            $message .= "<br><br>
                    Regards, <br>
                    {$rep['name']}<br>
                    Beaming White
                    ";                    

            $mail->setBodyHTML($message);
            $mail->setFrom($rep['email'], 'Beaming White');
            $mail->addTo($this->_auth->email, $name);
            $mail->addCc($rep['email'], $rep['name']);
            $mail->setSubject('Beaming White Exam Result');            
            //echo $message;
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
