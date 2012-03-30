<?php
    /**
     * @class  
     * @author 
     * @brief 
     **/

    class quizController extends quiz {
        function init() {
        }
        
    	function procQuiz(){
    		$args = Context::getRequestVars();
    		
    		// Retrieve all answers for this quiz
    		$args->module_srl = Context::get('module_srl');
    		// Retrieve possible answers for multiple choice questions
			$output = executeQueryArray('quiz.getAnswers', $args);
    		
    		// Prepare data for evaluating score
			// Arrange possible answers conveniently
    		foreach ($output->data as $answer){
    			$a[$answer->question_srl][$answer->answer_srl]->correct_value = $answer->is_correct;
    		}
    		
			// Arrange user input conveniently 
    		foreach($args as $question => $answers){
    			if(strpos($question, "item_") === 0){
    				$temp = explode("_", $question);
    				$question_srl = $temp[1];
    				$temp = explode("|", $answers);
    				foreach($temp as $answer_srl)
					{
    					if(is_numeric($answer_srl))
						{
    						$a[$question_srl][$answer_srl]->user_value = "Y";		
    					}
						else // If this is not a multiple choice question
						{
							$a[$question_srl][$question_srl]->user_value = $answer_srl;		
						}
    				}
    			}
    		}
    		
    		// Rate questions
    		$oQuizModel = &getModel('quiz');
    		$score = 0;
    		foreach ($a as $question_srl => $question_answers){
    			$args->question_srl = $question_srl;
    			$question = $oQuizModel->getQuestion($args);
    			$question->answers = $question_answers;
    			$is_correct = $oQuizModel->isCorrectAnswer($question);
    			// TODO Retrieve question log
    			if($is_correct == 'Y'){    				
    				$q_score = $oQuizModel->getAnswerWeight($args->module_srl, $question, null, $is_correct);
    			}
    			else $q_score = $oQuizModel->getAnswerWeight($args->module_srl, null, null, $is_correct);
    			$score += $q_score;
    			$results[$question_srl]->weight = $q_score;
    			$results[$question_srl]->is_correct = $is_correct;
    		}
    		
    		// Insert quiz log
            $log_args->module_srl = Context::get('module_srl');                       
            $log_args->score = $score;
            
            $oQuizModel = &getModel('quiz');
            $output = $oQuizModel->insertQuizLog($log_args);
            if($output->getError())
            	return $output->getMessage();
            
            // For each question, insert question log
            foreach($a as $question_srl => $question_answers){
            	$q_args->question_srl = $question_srl;
            	$q_args->module_srl = $args->module_srl;
            	$answer_list = '';
				if($question_answers[$question_srl]) // Question is open answer
				{
					$answer_list = $question_answers[$question_srl]->user_value;
				}
				else // Question is multiple choice
				{
					foreach($question_answers as $answer_srl => $answer)
					{
						if($answer->user_value == 'Y') $answer_list += $answer_srl + ",";
					}
				}
            	$q_args->answer = $answer_list;
            	$q_args->is_correct = $results[$question_srl]->is_correct;
            	$q_args->weight = $results[$question_srl]->weight;
            	
            	$output = $oQuizModel->insertQuizQuestionLog($q_args);
            	if($output->getError())
            		return new Object(true, $output->getMessage());
            }
            	
            $this->setMessage('success_registed');
    	}
    
    	function procDeleteQuizLog(){
    		$args = Context::getRequestVars();
       		if(!$args->module_srl)
       			return new Object(-1, 'msg_invalid_request');
    		
       		$oQuizModel = &getModel('quiz');
       		$log_args->user_identifier = $oQuizModel->getUserIdentifier();
       		$log_args->module_srl = $args->module_srl;
       			
            $oDB = &DB::getInstance();
            $oDB->begin();
			
            $output = executeQuery('quiz.delete_quiz_log', $log_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
                       
            $oDB->commit();

            $this->setMessage('success_registed');
    	}
    
    	function procStartQuestion(){
    		$question_srl = Context::get('question_srl');
			if(!$question_srl)
       			return new Object(-1, 'msg_invalid_request');

            // Send question_srl in response
    		$this->add('question_srl',$question_srl);
    		       			
            // If is not allowed to see this quiz, redirect to please_login page
        	if(!$this->grant->take_quiz){
        		$this->add('login_url', getUrl('act', 'dispQuizQuestion', 'question_srl', $question_srl));
        		return new Object();
        	}        	       		
       		
    		// Insert record in Questions Log with the start date
    		$args->module_srl = Context::get('module_srl');
    		$args->question_srl = $question_srl;
    		$args->start_date = date("YmdHis");

            $oQuizModel = &getModel('quiz');
            $output = $oQuizModel->insertQuizQuestionLog($args);
            if($output->getError())
            	return $output->getMessage();            
    		
            // Insert record in quiz log (if not exists)
            $oQuizModel = &getModel('quiz');
            $log_args->module_srl = Context::get('module_srl');                       
            $quiz_log = $oQuizModel->getQuizLog($log_args);
            if(!$quiz_log){
	            $output = $oQuizModel->insertQuizLog($log_args);
	            if($output->getError())
	            	return $output->getMessage();            
            }
    		
    		$this->setMessage('success_registed');
    	}
       
    	function procQuestion(){
    		$question_srl = Context::get('question_srl');
    		$module_srl = Context::get('module_srl');
			if(!$question_srl || !$module_srl)
       			return new Object(-1, 'msg_invalid_request');    		
    		$answer = Context::get('answer');
    		
    		// Retrieve question and log data for current question/quiz
    		$oQuizModel = &getModel('quiz');
    		$args->question_srl = $question_srl;
    		$args->module_srl = $module_srl;
    		$question_log = $oQuizModel->getQuestionLog($args);
    		
    		if($question_log && $question_log->is_correct == 'Y')
    			return new Object(-1, 'msg_invalid_request');
    			
    		$question = $oQuizModel->getQuestion($args);
			$quiz_log = $oQuizModel->getQuizLog($args);    		
    		
			// Evaluate user answer
    		$args->is_correct = $oQuizModel->isCorrectAnswer($question, $answer);    		
    		$args->weight = $oQuizModel->getAnswerWeight($module_srl, $question, $question_log, $args->is_correct);   		
    		$args->answer = $answer;
    		   		
    		// If log exists, update End date and Answer <=> quiz uses timing
    		if($question_log){
    			// Update record in Questions Log with the start date
    			$args->end_date = date("YmdHis");
	            $args->where_is_active = 'Y';
	        	$question_duration = $oQuizModel->getDateDiff($question_log->start_date, $args->end_date);
	        	    
    			$output = $oQuizModel->updateQuizQuestionLog($args);
    			if($output->getError())
    				return $output->getMessage();   

    			// Update Quiz log with new score and duration
				$args->score = $quiz_log->score + $args->weight;
				if($question_duration && $args->is_correct == 'Y')
					$args->total_time = $quiz_log->total_time + $question_duration;
				else 
					$args->total_time = $quiz_log->total_time;
				$args->start_date = $quiz_log->start_date;
				$args->end_date = $quiz_log->end_date;
				//unset($args->end_date);
    			$output = $oQuizModel->updateQuizLog($args);
    			if($output->getError())
    				return $output->getMessage();        			
    		}
    		else {// quiz doesn't use timing
    			// Insert record in Questions Log
    			$output = $oQuizModel->insertQuizQuestionLog($args);
    			if($output->getError())
    				return $output->getMessage();  

    			// Insert/Update Quiz log
				$args->score = $args->weight;
				if($quiz_log){
					$args->score += $quiz_log->score;
					$output = $oQuizModel->updateQuizLog($args);
				}
				else
					$output = $oQuizModel->insertQuizLog($args);
    			if($output->getError())
    				return $output->getMessage();	    			
    		}
    		    		
			$this->setMessage('success_registed');    		
    	}
    
    	function procRetakeQuestion(){
    		$question_srl = Context::get('question_srl');
    		$module_srl = Context::get('module_srl');
			if(!$question_srl || !$module_srl)
       			return new Object(-1, 'msg_invalid_request');

       		// Inactivate last attempt
       		$oQuizModel = &getModel('quiz');
       		$args->question_srl = $question_srl;
       		$args->is_active = 'N';
       		     		
    		$output = $oQuizModel->updateQuizQuestionLog($args);
    		if($output->getError())
    			return $output->getMessage();    		
       	
    		// Start question again
 			$this->procStartQuestion();
    	}
    
    	function procMemberInsert(){
    		// Insert member
    		$oMemberController = &getController('member');
    		$userid = Context::get('user_id');
    		Context::set('nick_name', $userid, true);
    		Context::set('user_name', $userid, true);
    		$oMemberController->procMemberInsert();

    		// If the user wants to be notified when new questions become available, add him to the subscribers table    		
    		if(Context::get('subscribe_to_questions')){
				$logged_info = Context::get('logged_info');
				if($logged_info->member_srl)
					$args->member_srl = $logged_info->member_srl;
            	    			
    			$args->email_address = Context::get('email_address');
    			
    			$oQuizModel = &getModel('quiz');
    			$output = $oQuizModel->insertSubscriber($args);
	 			if(!$output->toBool())
	 				return $output;    			
    		}
    		
    		// Redirect user to the page where he came from
    		$referer = $_SERVER["HTTP_REFERER"];
    		if($referer){ 
    			$source_url = str_replace('&act=dispMemberSignUpForm', ' ', $referer);
    			$this->add('redirect_to', trim($source_url));
    		}
    	}
    
 		function procQuizMemberSubscribe(){
 			$email = Context::get('email_address');
 			if(!$email)
 				return new Object(true, 'msg_invalid_request');
 				
 			$oQuizModel = &getModel('quiz');
 			$args->email_address = $email;
 			
 			$logged_info = Context::get('logged_info');
 			if($logged_info->member_srl)
 				$args->member_srl = $logged_info->member_srl;
 				
 			$output = $oQuizModel->insertSubscriber($args);
 			if(!$output->toBool())
 				return $output;
 				
 			$this->setMessage('success_registed');
 		}
    }
?>