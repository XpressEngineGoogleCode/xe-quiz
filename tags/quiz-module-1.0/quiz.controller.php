<?php
/**
* @class QuizController
* @developer Corina Udrescu (xe_dev@arnia.ro)
* @brief Class for handling form submission and such (add/edit/delete entities)
*/
class QuizController extends Quiz
{
	
	/**
	 * @brief Rate user answers on an entire quiz and save score and log
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return Object 
	 */
	function procQuiz() 
	{
		$module_srl = Context::get('module_srl');
		
		// Retrieve user's answers
		$user_answers = $this->getUserAnswers();
		
		// Retrieve questions and their possible correct answers
		$oQuizModel = &getModel('quiz');
		$args->module_srl = $module_srl;
		$questions = $oQuizModel->getQuestions($args);
		
		// Prepare quiz info for scoring
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		
		$quiz = new QuizInfo($module_info->start_date, $module_info->end_date);
		$score = 0;
		
		// Rate questions
		foreach($questions as $question) 
		{
			$question_srl = $question->getQuestionSrl();
			$user_value = $user_answers[$question_srl];
			$is_correct = $question->checkAnswer($user_value);
			$question_score = $question->getScore($user_value, $quiz);
			$score += $question_score;
			$results[$question_srl]->weight = $question_score;
			$results[$question_srl]->is_correct = $is_correct ? 'Y' : 'N';
		}
		
		// Insert quiz log
		$log_args->module_srl = Context::get('module_srl');
		$log_args->score = $score;
		$output = $oQuizModel->insertQuizLog($log_args);
		if($output->getError()) 
		{
			return $output->getMessage();
		}
		
		// Insert questions log
		foreach($user_answers as $user_answer) 
		{
			$question_srl = $user_answer->getQuestionSrl();
			$q_args->question_srl = $question_srl;
			$q_args->module_srl = $module_srl;
			$q_args->answer = $user_answer->toString();
			$q_args->is_correct = $results[$question_srl]->is_correct;
			$q_args->weight = $results[$question_srl]->weight;
			$output = $oQuizModel->insertQuizQuestionLog($q_args);
			if($output->getError()) 
			{
				return new Object(TRUE, $output->getMessage());
			}
		}
		$this->setMessage('success_registed');
	}
	
	/**
	 * @brief Deletes log for a quiz instance
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return Object 
	 */
	function procDeleteQuizLog() 
	{
		$args = Context::getRequestVars();
		if(!$args->module_srl) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		$oQuizModel = &getModel('quiz');
		$log_args->user_identifier = $oQuizModel->getUserIdentifier();
		$log_args->module_srl = $args->module_srl;
		
		$oDB = & DB::getInstance();
		$oDB->begin();
		$output = executeQuery('quiz.delete_quiz_log', $log_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return $output;
		}
		
		$log_args->where_is_active = 'Y';
		$log_args->is_active = 'N';
		$output = executeQuery('quiz.update_question_log', $log_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return $output;
		}
		$oDB->commit();
		
		$this->setMessage('success_registed');
	}
	
	/**
	 * @brief Start timing a question - insert almost empty question log to mark start_time; 
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object 
	 */
	function procStartQuestion() 
	{
		$question_srl = Context::get('question_srl');
		if(!$question_srl) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		// Send question_srl in response
		$this->add('question_srl', $question_srl);
		
		// If is not allowed to see this quiz, redirect to please_login page
		if(!$this->grant->take_quiz) 
		{
			$this->add('login_url', getUrl('act', 'dispQuizQuestion', 'question_srl', $question_srl));
			return new Object();
		}
		
		// Insert record in Questions Log with the start date
		$args->module_srl = Context::get('module_srl');
		$args->question_srl = $question_srl;
		$args->start_date = date("YmdHis");
		$oQuizModel = & getModel('quiz');
		$output = $oQuizModel->insertQuizQuestionLog($args);
		if($output->getError()) 
		{
			return $output->getMessage();
		}
		
		// Insert record in quiz log (if not exists)
		$oQuizModel = & getModel('quiz');
		$log_args->module_srl = Context::get('module_srl');
		$quiz_log = $oQuizModel->getQuizLog($log_args);
		if(!$quiz_log) 
		{
			$output = $oQuizModel->insertQuizLog($log_args);
			if($output->getError()) 
			{
				return $output->getMessage();
			}
		}
		$this->setMessage('success_registed');
	}
	
	/**
	 * @brief Rate a question and save score and log
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object 
	 */
	function procQuestion() 
	{
		$question_srl = Context::get('question_srl');
		$module_srl = Context::get('module_srl');
		if(!$question_srl || !$module_srl) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		$user_answers = $this->getUserAnswers();
		$answer = $user_answers[$question_srl];
		
		// Retrieve question and log data for current question/quiz
		$oQuizModel = & getModel('quiz');
		$args->question_srl = $question_srl;
		$args->module_srl = $module_srl;
		$question_log = $oQuizModel->getQuestionLog($args);
		
		// If user already answered the question correctly, he can't take it again
		if($question_log && $question_log->is_correct == 'Y') 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		$question = $oQuizModel->getQuestion($args);
		$quiz_log = $oQuizModel->getQuizLog($args);
		$quiz = new QuizInfo($this->module_info->start_date, $this->module_info->end_date);
		// Evaluate user answer
		$args->is_correct = $question->checkAnswer($answer) ? 'Y' : 'N';
		$args->weight = $question->getScore($answer, $quiz);
		$args->answer = $answer->toString();
		
		// If log exists, update End date and Answer <=> quiz uses timing
		if($question_log) 
		{
			// Update record in Questions Log with the start date
			$args->end_date = date("YmdHis");
			$args->where_is_active = 'Y';
			$output = $oQuizModel->updateQuizQuestionLog($args);
			if($output->getError()) 
			{
				return $output->getMessage();
			}
			
			// Update Quiz log with new score and duration
			$question_duration = $oQuizModel->getDateDiff($question_log->start_date, $args->end_date);
			$args->score = $quiz_log->score + $args->weight;
			if($question_duration && $args->is_correct == 'Y') 
			{
				$args->total_time = $quiz_log->total_time + $question_duration;
			}
			else 
			{
				$args->total_time = $quiz_log->total_time;
			}
			$args->start_date = $quiz_log->start_date;
			$args->end_date = $quiz_log->end_date;

			$output = $oQuizModel->updateQuizLog($args);
			if($output->getError()) 
			{
				return $output->getMessage();
			}
		}
		else 
		{ // quiz doesn't use timing
			// Insert record in Questions Log
			$output = $oQuizModel->insertQuizQuestionLog($args);
			if($output->getError()) 
			{
				return $output->getMessage();
			}
			// Insert/Update Quiz log
			$args->score = $args->weight;
			if($quiz_log) 
			{
				$args->score += $quiz_log->score;
				$output = $oQuizModel->updateQuizLog($args);
			}
			else 
			{
				$output = $oQuizModel->insertQuizLog($args);
			}
			if($output->getError()) 
			{
				return $output->getMessage();
			}
		}
		$this->setMessage('success_registed');
	}
	
	/**
	 * @brief Inactivates question log, allowing user to retake a question
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object 
	 */
	function procRetakeQuestion() 
	{
		$question_srl = Context::get('question_srl');
		$module_srl = Context::get('module_srl');
		if(!$question_srl || !$module_srl) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		// Inactivate last attempt
		$oQuizModel = &getModel('quiz');
		$args->question_srl = $question_srl;
		$args->is_active = 'N';
		$output = $oQuizModel->updateQuizQuestionLog($args);
		if($output->getError()) 
		{
			return $output->getMessage();
		}
		
		// Start question again
		$this->procStartQuestion();
	}
	
	/**
	 * @brief Overried procMemberInsert function in member module;
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object
	 * 
	 * Used for customizing the screen quiz users see when registering.
	 * Also useful for saving subscription info.
	 */
	function procMemberInsert() 
	{
		// Insert member
		$oMemberController = &getController('member');
		$userid = Context::get('user_id');
		Context::set('nick_name', $userid, TRUE);
		Context::set('user_name', $userid, TRUE);
		$oMemberController->procMemberInsert();
		
		// If the user wants to be notified when new questions become available, add him to the subscribers table
		if(Context::get('subscribe_to_questions')) 
		{
			$logged_info = Context::get('logged_info');
			if($logged_info->member_srl) 
			{
				$args->member_srl = $logged_info->member_srl;
			}
			$args->email_address = Context::get('email_address');
			$oQuizModel = & getModel('quiz');
			$output = $oQuizModel->insertSubscriber($args);
			if(!$output->toBool()) 
			{
				return $output;
			}
		}
		
		// Redirect user to the page where he came from
		$referer = $_SERVER["HTTP_REFERER"];
		if($referer) 
		{
			$source_url = str_replace('&act=dispMemberSignUpForm', ' ', $referer);
			$this->add('redirect_to', trim($source_url));
		}
	}
	
	/**
	 * @brief Add member to subscribers' list
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object 
	 */
	function procQuizMemberSubscribe() 
	{
		$email = Context::get('email_address');
		if(!$email) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		
		$oQuizModel = & getModel('quiz');
		$args->email_address = $email;
		$logged_info = Context::get('logged_info');
		if($logged_info->member_srl) 
		{
			$args->member_srl = $logged_info->member_srl;
		}
		$output = $oQuizModel->insertSubscriber($args);
		if(!$output->toBool()) 
		{
			return $output;
		}
		$this->setMessage('success_registed');
	}
	
	/**
	 * @brief Conveniently format user input (answers)
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return array() - An array of UserAnswer objects
	 * 
	 * Answer fields have ids that start with "item_"
	 * This is how we identify the fields we need from the question form
	 */
	function getUserAnswers() 
	{
		$args = Context::getRequestVars();
		
		$a = array();
		
		// Arrange user input conveniently
		// Foreach id => value pair
		foreach($args as $question => $answers) 
		{
			if(strpos($question, "item_") === 0) 
			{
				// Retrieve question_srl from input id
				$temp = explode("_", $question);
				$question_srl = $temp[1];
				
				// Retrieve user answers from input value
				$temp = explode("|", $answers);
				foreach($temp as $answer_srl) 
				{
					$a[$question_srl] = new UserAnswerForMultipleChoiceQuestion($question_srl);
					$a[$question_srl]->addChoice($answer_srl);
				}
			}
			else
			{
				if(strpos($question, "open_item_") === 0) 
				{
					// Retrieve question_srl from input id
					$temp = explode("_", $question);
					$question_srl = $temp[2];
					$a[$question_srl] = new UserAnswerForOpenQuestion($question_srl);
					$a[$question_srl]->setValue($answers);
				}
			}
		}
		return $a;
	}
}
/* End of file quiz.controller.php */
/* Location: quiz.controller.php */
