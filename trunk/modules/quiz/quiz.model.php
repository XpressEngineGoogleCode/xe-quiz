<?php 
	class quizModel extends quiz {
		function getSkinTemplatePath(){
		    $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_default';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            return $template_path;
		}
		// @brief Gets all questions for the current quiz  
		function getQuestions($args){
			// TODO Implement caching
			if(!$args->module_srl) return false;
			
			$output = executeQueryArray('quiz.getQuestions', $args);
			if(!$output) return false; 
			return $output->data;
		}

		// @brief Gets a certain question
		function getQuestion($args){
			// TODO Implement caching
			if(!$args->question_srl) return false;
			
			$output = executeQueryArray('quiz.getQuestion', $args);
			if(!$output) return false; 
			return $output->data[0];
		}

		function getUserIdentifier(){
            $member_srl = $this->getMemberSrl();

            if($member_srl == -1){
            	// Retrieve unique key from cookie
            	$key = $_COOKIE['unique_user_key'];
            	
            	if(!$key){
	            	// Generate unique key
	            	$key = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
	            	
	            	// Store key in cookie
	            	$number_of_days = 30 ;
					$date_of_expiry = time() + 60 * 60 * 24 * $number_of_days ;
	            	setCookie('unique_user_key', $key, $date_of_expiry);
            	}
            	return $key;
            }
            
            return $member_srl;
		}
		
		function getMemberSrl(){
			$logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl?$logged_info->member_srl:-1;

            return $member_srl;
		}
		
		// @brief Initializez info about the current memeber
		// If user is logged in: member_srl
		// Else: hash made from ipaddress and request header info
		// TODO De vazut daca asta e ultima varianta de functie - ceva e putred
		// Cred ca nu am dat check in la ultimele modificari de pe cubrid.org - de luat codul de acolo
		function addMemberInfo(){
			$logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl?$logged_info->member_srl:-1;

            // 1. User not logged in -> search just by Ipaddess hash
            if($member_srl == -1){
            	$key = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
            	$log_args->ipaddress = $key;
            }
			// 2. User logged in -> search just by Member_srl
			else {            
            	$log_args->member_srl = $member_srl;
            	$log_args->ipaddress = -1;
			}
            return $log_args;
		}
		
		// @brief Returns log the current quiz
		function getQuizLog($args){
			if(!$args->module_srl) return new Object(true, 'msg_invalid_request');
			
			$log_args->module_srl = $args->module_srl;
			$log_args->user_identifier = $this->getUserIdentifier();
			
			$output = executeQueryArray('quiz.getQuizLog', $log_args);
			if(!$output->data)
				return null;
			return $output->data[0];
		}
		
		/*
		 * @brief Inserts a new record in Quiz log table
		 * @param $args - data to be inserted
		 * 	$args->module_srl - Quiz unique identifier (mandatory)
		 *  $args->score - Total score obtained by user at this quiz 
		 */
		function insertQuizLog($args){
			if(!$args->module_srl) return new Object(true, 'msg_invalid_request');
			
			$log_args->module_srl = $args->module_srl;
			$log_args->member_srl = $this->getMemberSrl();
			$log_args->user_identifier = $this->getUserIdentifier();
			$log_args->score = $args->score;
			if($args->total_time) $log_args->total_time = $args->total_time;
			if($args->start_date) $log_args->start_date = $args->start_date;
			if($args->end_date) $log_args->end_date = $args->end_date;
			
			$oDB = &DB::getInstance();
            $oDB->begin();
            $output = executeQuery('quiz.insert_log', $log_args);
    		if(!$output->toBool()) {
                $oDB->rollback();
                return new Object(true, $output);
            }
            $oDB->commit();
            return new Object(0);
		}

		function updateQuizLog($args){
			if(!$args->module_srl) return new Object(true, 'msg_invalid_request');
			
			// Where clause
			$log_args->module_srl = $args->module_srl;
			$log_args->user_identifier = $this->getUserIdentifier();
			
			// Update clause
			if($args->score) $log_args->score = $args->score;
			if($args->total_time) $log_args->total_time = $args->total_time;
			if($args->start_date) $log_args->start_date = $args->start_date;
			if($args->end_date) $log_args->end_date = $args->end_date;
			
			
			$oDB = &DB::getInstance();
            $oDB->begin();
            $output = executeQuery('quiz.update_log', $log_args);
    		if(!$output->toBool()) {
                $oDB->rollback();
                return new Object(true, $output);
            }
            $oDB->commit();
            return new Object(0);			
		}
		/*
		 * @brief Inserts a new record in QuizQuestion log table
		 * @param $args - data to be inserted
		 * 	$args->module_srl - Quiz unique identifier (mandatory)
		 *  $args->question_srl - Question unique identifier (mandatory)
		 *  $args->start_date - Time when the user started answering the question 
		 */
		function insertQuizQuestionLog($args){
			if(!$args->module_srl) return new Object(true, 'msg_invalid_request');
			if(!$args->question_srl) return new Object(true, 'msg_invalid_request');
			
			$log_args->module_srl = $args->module_srl;
			$log_args->question_srl = $args->question_srl;
			$log_args->member_srl = $this->getMemberSrl();
			$log_args->user_identifier = $this->getUserIdentifier();
			
			if($args->start_date) $log_args->start_date = $args->start_date;
			if($args->is_active) $log_args->is_active = $args->is_active;
			if($args->answer) $log_args->answer = $args->answer;
			if($args->is_correct) $log_args->is_correct = $args->is_correct;
			if($args->weight) $log_args->weight = $args->weight;
			
			$attempts = $this->getQuizQuestionLogCount($log_args);
			$log_args->attempt = $attempts + 1;
			
			$oDB = &DB::getInstance();
            $oDB->begin();
            $output = executeQuery('quiz.insert_question_log', $log_args);
    		if(!$output->toBool()) {
                $oDB->rollback();
                return new Object(true, $output);
            }
            $oDB->commit();
            return new Object(0);
		}

		function updateQuizQuestionLog($args){
			if(!$args->question_srl) return new Object(true, 'msg_invalid_request');
			
			// Initializing variables for the Where clause
			$log_args->question_srl = $args->question_srl;
			$log_args->user_identifier = $this->getUserIdentifier();
			if($args->where_is_active) $log_args->where_is_active = $args->where_is_active;
			else $log_args->where_is_active = 'Y';
			
			// Initializing variables for the Update clause
			if($args->end_date) $log_args->end_date = $args->end_date;
			if($args->answer) $log_args->answer = $args->answer;
			if($args->is_active) $log_args->is_active = $args->is_active;
			if($args->is_correct) $log_args->is_correct = $args->is_correct;
			if($args->weight) $log_args->weight = $args->weight;
		
    		$oDB = &DB::getInstance();
            $oDB->begin();    		
                       
    		$output = executeQuery('quiz.update_question_log', $log_args);
    		
    	    if(!$output->toBool()) {
                $oDB->rollback();
                return new Object(true, $output);
            }
            $oDB->commit();
			
            return new Object(0);
		}
		
		// @brief Return log for a certain question
		function getQuestionLog($args){
			if(!$args->question_srl) return false;

			$log_args->question_srl = $args->question_srl;
			$log_args->user_identifier = $this->getUserIdentifier();
			if($args->is_active) $log_args->is_active = $args->is_active;
			else $log_args->is_active = 'Y';
			
			$output = executeQueryArray('quiz.getQuizQuestionLog', $log_args);
			if(!$output->data) return null;
			return $output->data[0];
		}
		
		// @brief Returs the log for all the questions in the current quiz
		function getQuestionsLog($args){
			if(!$args->module_srl) return false;
			
			$log_args->module_srl = $args->module_srl;
			$log_args->user_identifier = $this->getUserIdentifier();
			if($args->is_active) $log_args->is_active = $args->is_active;
			else $log_args->is_active = 'Y';
			
			$output = executeQueryArray('quiz.getQuizQuestionsLog', $log_args); 
			if(!$output) return false;
			
			return $output->data;
		}		
		
		function getQuizQuestionLogCount($args){
			if(!$args->question_srl) return false;
			
			$log_args->question_srl = $args->question_srl;
			$log_args->user_identifier = $this->getUserIdentifier();
			
			$output = executeQueryArray('quiz.getQuestionLogCount', $log_args); 
			if(!$output) return false;

			return $output->data[0]->count;			
		}

		function get_query_result($sql)
		{
			$host_ip = "localhost";
			$host_port = 33000;
			$db_name = "demodb";
			$db_user = "guest";
			$db_pwd  = "1234";
			
			$field_delim = ",";
			$row_delim = "|";
			
			$cubrid_con = @cubrid_connect($host_ip, $host_port, $db_name,$db_user,$db_pwd);
			
			$ret_val = NULL;
		 
			if (!$cubrid_con) {
				return NULL;
			}
			$result = @cubrid_execute($cubrid_con, $sql);
			
			if ($result) {
					$ret_val = $row_delim;
					// Get the number of columns in the result set created by the SQL query.
					$num_fields = cubrid_num_cols($result);
		 
					// Get the results from the result set.
					while ($row = cubrid_fetch($result)) {
						for ($i = 0; $i < $num_fields; $i++) {
							$ret_val .= $row[$i];
							if ($i < ($num_fields-1))
								$ret_val .= $field_delim;
						}
						$ret_val .= $row_delim;
					}
				}
				
			cubrid_disconnect($cubrid_con);
			
			return $ret_val;
		}		
		
		// TODO See if we change this algorithm
		/*
		 * For multiple answer questions, each question will consist of an array of answers.
		 * Each answer will contain ->correct_value and ->user_value (if checked)
		 */
		function isCorrectAnswer($question, $user_answer = ''){
			if($question->is_multiple_choice == 'Y'){
				$is_correct = 'Y';
				foreach($question->answers as $answer){
    				if($answer->correct_value == "N" && $answer->user_value == "Y") $is_correct = 'N';
    				if($answer->correct_value == "Y" && !$answer->user_value) $is_correct = 'N';
    			}
    			return $is_correct;
			}
			else{
				if($question->execute_answer == 'Y'){
					try {
						$scriptResult = $this->get_query_result($user_answer);
					}
					catch(Exception $e){
						return 'N';
					}
					if($scriptResult == null) return 'N';
					
					$user_answer = $scriptResult;
				}
				if(strcasecmp(trim($question->answer), trim($user_answer)) == 0){
					return 'Y';
				}
				return 'N';					
			}
		}
		
		// TODO See if we change this algorithm
		function getAnswerWeight($module_srl, $question, $question_log, $is_correct){
			if($is_correct == 'N') return 0;
    		// Only give points if quiz is active (and start/end date are defined)
    		$givePoints = false;
    		$oModuleModel = &getModel('module');
    		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
    		if($module_info->start_date && $module_info->end_date){
	    		$start = strtotime($module_info->start_date);
	    		$end = strtotime($module_info->end_date);
	    		$now = strtotime(date('Ymd'));
	    		if(($now - $start >= 0) && ($end - now >= 0)){
	    			$givePoints = true;
	    		}
    		}
    		else $givePoints = true;
    		
    		/*
    		if($givePoints){
    			// Decrease score if there are previous attempts    			
    			if($question_log)
    				$count = $question_log->attempt;
    			else $count = 1;
    			return $question->weight / $count;
    		}
    		*/
    		return $question->weight;
    		return 0;			
		}
		
		function getDateDiff($date1, $date2){
			return abs(strtotime($date2) - strtotime($date1));
		}
		function getFormattedDateDiff($date1, $date2){
			$diff = $this->getDateDiff($date1, $date2);
			return $this->getFormattedTime($diff);
		}
		function getFormattedTime($diff){
			$years = floor($diff / (365*60*60*24));
			$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
			$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
			$hours   = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60)); 
			$minutes  = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60); 
			$seconds = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60)); 
						
			$result = '';
			if($years != 0)
				$result .= sprintf("%d years", $years);
			if($months != 0)
				$result .= ($result != '' ? ', ' : '').sprintf("%d months", $months);
			if($days != 0)
				$result .= ($result != '' ? ', ' : '').sprintf("%d days", $days);
			if($hours != 0)
				$result .= ($result != '' ? ', ' : '').sprintf("%d hours", $hours);
			if($minutes != 0)
				$result .= ($result != '' ? ', ' : '').sprintf("%d minutes", $minutes);
			if($seconds != 0)
				$result .= ($result != '' ? ', ' : '').sprintf("%d seconds", $seconds);
			return $result;						
		}

		// @brief Returs the view for a question and log
		function getQuestionHTML($question, $log, $use_question_activation_date = null, $use_timing = null, $quiz_log = null){
			if(!$use_question_activation_date && !$quiz_log){
				$template_file = 'question.form';
				$class = 'form';
			}
			else if(!$use_question_activation_date && $quiz_log){
			
			}
			
			if($use_question_activation_date == 'Y'){
				$activation_date = strtotime($question->activation_date);
				$today = strtotime(date("Y-m-d"));
			}
			
			// Question is not avaible yet
			if($use_question_activation_date == 'Y' && ($today - $activation_date < 0)) {
					$template_file = 'question.inactive';
					$class = 'inactive';
			}
			
			// Question already answered and correct
			elseif(($log && $log->is_correct == 'Y')){
				$duration = $this->getFormattedDateDiff($log->start_date, $log->end_date);
				Context::set('duration', $duration);
				$template_file = 'question.correct';
				$class = 'correct';
			}
			//  Question already answered and incorrect
			elseif($log && $log->is_correct == 'N'){
				if($use_timing == 'Y'){
					$template_file = 'question.incorrect.timed';
					$class = 'incorrect timed';
				}
				else
					$template_file = 'question.incorrect.untimed';
					$class = 'incorrect untimed';
			}
			// Old type of quiz - case when we don't have question log and have to use quiz log
			elseif(!$use_question_activation_date && $quiz_log){
				$template_file = 'question.correct';	
				$class = 'uncollapsed';			
			}
			// Question was started but not finished or timing is not used 
			elseif(($log && !$log->end_date && $use_timing == 'Y') || (!$log && !$use_timing && $use_question_activation_date == 'Y') || (!$use_question_activation_date && !$quiz_log)){
				$template_file = 'question.form';
				$class = 'form';
			}			
			// Question was not started and timing is used
			elseif((!$log && $use_timing == 'Y')){
				$template_file = 'question.teaser';
				$class = 'teaser';	
			}
			Context::set('class', $class);
			// Retrieve template file path
			$template_path = $this->getSkinTemplatePath();
			$oTemplate = &TemplateHandler::getInstance();			
            return $oTemplate->compile($template_path, $template_file);
		}

		function getLatestActiveQuestion($module_srl){
			if(!$module_srl) return new Object(true, 'msg_invalid_request');
			
			$args->module_srl = $module_srl;
			$questions = $this->getQuestions($args);
			
			if($questions)
				$activeQuestion = $questions[0];
			else return null;
						
			if(!$this->isActive($activeQuestion)) // Question is inactive
				return null;
				
			$q_count = count($questions);
			
			for($i = 1; $i < $q_count; $i++){
				if(!$this->isActive($questions[$i])) {
					break;
				}
				else 
					$activeQuestion = $questions[$i];
			}
			
			return $activeQuestion;
		
		}
		
		function isActive($question){
			$activation_date = strtotime($question->activation_date);
			$today = strtotime(date("Y-m-d"));			
			if($today - $activation_date < 0) // Question is inactive
				return false;
			return true;							
		}
		
		/*
		 * @brief Returs a list of all the questions that are visible
		 * */
		function getActiveQuestions($module_srl){
			if(!$module_srl) return new Object(true, 'msg_invalid_request');
			
			$args->module_srl = $module_srl;
			$questions = $this->getQuestions($args);
			
			if(!$questions)
				return null;

			foreach($questions as $question){
				if($this->isActive($question))
					$activeQuestions[] = $question;
			}
						
			return $activeQuestions;
		
		}		
		

		function insertSubscriber($args){
			$email = $args->email_address;
			if(!$email) return new Object(true, 'msg_invalid_request');
			
		 	if($this->isSubscribed($email)){
 				return new Object(true, 'You are already subscribed!');
 			}			
 			
			$s_args->email_address = $email;
			if($args->member_srl)
				$s_args->member_srl = $args->member_srl;
				
			$oDB = &DB::getInstance();
            $oDB->begin();
            $output = executeQuery('quiz.insert_subscriber', $s_args);
    		if(!$output->toBool()) {
                $oDB->rollback();
                return new Object(true, 'msg_error_occured');
            }
            $oDB->commit();
            return new Object(0);
		}
		
		function deleteSubscriber($args){
			$email = $args->email;
			if(!$email) return new Object(true, 'msg_invalid_request');
			
			$s_args->email = $email;
							
			$oDB = &DB::getInstance();
            $oDB->begin();
            $output = executeQuery('quiz.delete_subscriber', $s_args);
    		if(!$output->toBool()) {
                $oDB->rollback();
                return new Object(true, $output);
            }
            $oDB->commit();
            return new Object(0);
		}		
		
		function getAllSubscribers(){			
			$output = executeQueryArray('quiz.getSubscribers');
			if(!$output) return false; 
			return $output->data;			
		}
		
		function sendEmailToAllSubscribers($args){
			if(!$args->sender_name || !$args->sender_email || !$args->title || !$args->content)
				return new Object(true, 'msg_invalid_request');
				
			$email->sender_name = $args->sender_name;
			$email->sender_email = $args->sender_email;
			$email->title = $args->title;
			$email->content = $args->content;
			// TODO Append unsubscribe link
			$email->content_type = 'html'; 

			
            $oMail = new Mail();
            $oMail->setTitle($email->title);
            $oMail->setContent($email->content);
            $oMail->setSender($email->sender_name, $email->sender_email);

            $mailsSent = 0;
            
            $subscribers = $this->getAllSubscribers();
			if($subscribers){
				foreach($subscribers as $subscriber){
					$oMail->setReceiptor(null, $subscriber->email_address);
				    $oMail->send();
 				    $mailsSent++;
				}				
			}     
			return new Object(0, sprintf(Context::getLang('msg_send_success'), $mailsSent));					
		}
	
		function isSubscribed($email){
			if(!$email) return new Object(true, 'msg_invalid_request');
			
			$args->email_address = $email;
							
			$oDB = &DB::getInstance();
            $output = executeQuery('quiz.getSubscriber', $args);
    		if(!$output->toBool()) {
                return new Object(true, $output);
            }
            if($output->data) return true;
            return false;						
		}
	}
?>