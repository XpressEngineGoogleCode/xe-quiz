<?php
/**
 * File containing the Quiz model class
 */
/**
 * Class for handling common quiz logic and selects to the database
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class QuizModel extends Quiz
{
	/**
	 * Returns path to this module's skins folder
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return string
	 */
	function getSkinTemplatePath() 
	{
		$template_path = sprintf("%sskins/%s/", $this->module_path, $this->module_info->skin);
		if(!is_dir($template_path) || !$this->module_info->skin) 
		{
			$this->module_info->skin = 'xe_default';
			$template_path = sprintf("%sskins/%s/", $this->module_path, $this->module_info->skin);
		}
		return $template_path;
	}
	
	/**
	 * Gets all questions and associated answers for the current quiz
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return array() - array of Question objects
	 * @param $args stdClass - must contain module_srl
	 */
	function getQuestions($args) 
	{
		// TODO Implement caching
		if(!$args->module_srl) 
		{
			return FALSE;
		}
		
		$output = executeQueryArray('quiz.getQuestions', $args);
		if(!$output) 
		{
			return FALSE;
		}
		
		$questions_list = $output->data;
		
		// Retrieve list of answers from the database (only for multiple_choice quizzes)
		$answers_list = array();
		$output = executeQueryArray('quiz.getAnswers', $args);
		if($output && $output->data)
		{
			foreach($output->data as $answer) 
			{
				if(!isset($answers_list[$answer->question_srl])) 
				{
					$answers_list[$answer->question_srl] = array();
				}
				array_push($answers_list[$answer->question_srl], $answer);
			}
		}
		
		$result = array();
		foreach($questions_list as $question) 
		{
			$oQuestion = NULL;
			if($question->is_multiple_choice == 'Y') 
			{
				$question->answers = $answers_list[$question->question_srl];
				$oQuestion = new MultipleChoiceQuestion($question);
			}
			else 
			{
				$oQuestion = new OpenQuestion($question);
			}
			$result[] = $oQuestion;
		}
		return $result;
	}
	
	/**
	 * Gets a certain question
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return Question
	 * @param $args stdClass - must contain question_srl
	 */	
	function getQuestion($args) 
	{
		// TODO Implement caching
		if(!$args->question_srl) 
		{
			return FALSE;
		}
		
		$output = executeQueryArray('quiz.getQuestion', $args);
		if(!$output) 
		{
			return FALSE;
		}
		$question = $output->data[0];
		$oQuestion = NULL;
		if($question->is_multiple_choice == 'Y') 
		{
			$question->answers = array();
			$output = executeQueryArray('quiz.getAnswers', $args);
			if($output && $output->data)
			{
				foreach($output->data as $answer) 
				{
					array_push($question->answers, $answer);
				}
			}
			$oQuestion = new MultipleChoiceQuestion($question);
		}
		else 
		{
			$oQuestion = new OpenQuestion($question);
		}
		return $oQuestion;
	}
	
	/**
	 * Get a unique user ID - works with both authenticated and anonymous users
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return string
	 */
	function getUserIdentifier() 
	{
		$member_srl = $this->getMemberSrl();
		if($member_srl == - 1) 
		{
			// Retrieve unique key from cookie
			$key = $_COOKIE['unique_user_key'];
			if(!$key) 
			{
				// Generate unique key
				$key = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
				// Store key in cookie
				$number_of_days = 30;
				$date_of_expiry = time() + 60 * 60 * 24 * $number_of_days;
				setCookie('unique_user_key', $key, $date_of_expiry);
			}
			return $key;
		}
		return $member_srl;
	}
	
	/**
	 * Returns member_srl if user is logged in or -1 otherwise
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return string
	 */	
	function getMemberSrl() 
	{
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl ? $logged_info->member_srl : -1;
		return $member_srl;
	}
	
	/**
	 * Initializez info about the current memeber
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return stdClass
	 *  
	 *  If user is logged in: member_srl
	 *  Else: hash made from ipaddress and request header info
	 */
	function addMemberInfo() 
	{
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl ? $logged_info->member_srl : -1;
		
		// 1. User not logged in -> search just by Ipaddess hash
		if($member_srl == - 1) 
		{
			$key = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
			$log_args->ipaddress = $key;
		}
		// 2. User logged in -> search just by Member_srl
		else 
		{
			$log_args->member_srl = $member_srl;
			$log_args->ipaddress = - 1;
		}
		return $log_args;
	}
	
	/**
	 * Returns log for the current quiz
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return stdClass
	 * 
	 * Uniquely identified by module_srl and user_identifier
	 */
	function getQuizLog($args) 
	{
		if(!$args->module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$log_args->module_srl = $args->module_srl;
		$log_args->user_identifier = $this->getUserIdentifier();
		$output = executeQueryArray('quiz.getQuizLog', $log_args);
		if(!$output->data) 
		{
			return NULL;
		}
		return $output->data[0];
	}

	/**
	 * Inserts a new record in Quiz log table
	 *   $args->module_srl - Quiz unique identifier (mandatory)
	 *   $args->score - Total score obtained by user at this quiz
	 *
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args - data to be inserted
	 * @return Object
	 */
	function insertQuizLog($args) 
	{
		if(!$args->module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$log_args->module_srl = $args->module_srl;
		$log_args->member_srl = $this->getMemberSrl();
		$log_args->user_identifier = $this->getUserIdentifier();
		$log_args->score = $args->score;
		if($args->total_time) 
		{
			$log_args->total_time = $args->total_time;
		}
		if($args->start_date) 
		{
			$log_args->start_date = $args->start_date;
		}
		if($args->end_date) 
		{
			$log_args->end_date = $args->end_date;
		}
		
		$oDB = &DB::getInstance();
		$oDB->begin();
		$output = executeQuery('quiz.insert_log', $log_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return new Object(TRUE, $output);
		}
		$oDB->commit();
		return new Object(0);
	}

	/**
	 * Updates a record in Quiz log table
	 *  $args->module_srl - Quiz unique identifier (mandatory)
	 *
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args - data to be updated
	 * @return Object
	 */
	function updateQuizLog($args) 
	{
		if(!$args->module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		
		// Where clause
		$log_args->module_srl = $args->module_srl;
		$log_args->user_identifier = $this->getUserIdentifier();
		
		// Update clause
		if($args->score) 
		{
			$log_args->score = $args->score;
		}
		if($args->total_time) 
		{
			$log_args->total_time = $args->total_time;
		}
		if($args->start_date) 
		{
			$log_args->start_date = $args->start_date;
		}
		if($args->end_date) 
		{
			$log_args->end_date = $args->end_date;
		}
		
		$oDB = & DB::getInstance();
		$oDB->begin();
		$output = executeQuery('quiz.update_log', $log_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return new Object(TRUE, $output);
		}
		$oDB->commit();
		return new Object(0);
	}

	/**
	 * Inserts a new record in QuizQuestion log table
	 *     $args->module_srl - Quiz unique identifier (mandatory)
	 *  $args->question_srl - Question unique identifier (mandatory)
	 *  $args->start_date - Time when the user started answering the question
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args - data to be inserted
	 * @return Object
	 */
	function insertQuizQuestionLog($args) 
	{
		if(!$args->module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		if(!$args->question_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		
		$log_args->module_srl = $args->module_srl;
		$log_args->question_srl = $args->question_srl;
		$log_args->member_srl = $this->getMemberSrl();
		$log_args->user_identifier = $this->getUserIdentifier();
		if($args->start_date) 
		{
			$log_args->start_date = $args->start_date;
		}
		if($args->is_active) 
		{
			$log_args->is_active = $args->is_active;
		}
		if($args->answer) 
		{
			$log_args->answer = $args->answer;
		}
		else 
		{
			$log_args->answer = '';
		}
		if($args->is_correct) 
		{
			$log_args->is_correct = $args->is_correct;
		}
		if($args->weight) 
		{
			$log_args->weight = $args->weight;
		}
		
		$attempts = $this->getQuizQuestionLogCount($log_args);
		$log_args->attempt = $attempts + 1;
		
		$oDB = & DB::getInstance();
		$oDB->begin();
		$output = executeQuery('quiz.insert_question_log', $log_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return new Object(TRUE, $output);
		}
		$oDB->commit();
		return new Object(0);
	}
	
	/**
	 * Updates question log
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return Object 
	 */
	function updateQuizQuestionLog($args) 
	{
		if(!$args->question_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		// Initializing variables for the Where clause
		$log_args->question_srl = $args->question_srl;
		$log_args->user_identifier = $this->getUserIdentifier();
		
		if($args->where_is_active) 
		{
			$log_args->where_is_active = $args->where_is_active;
		}
		else 
		{
			$log_args->where_is_active = 'Y';
		}
		
		// Initializing variables for the Update clause
		if($args->end_date) 
		{
			$log_args->end_date = $args->end_date;
		}
		if($args->answer) 
		{
			$log_args->answer = substr($args->answer, 0, 254);
		}
		if($args->is_active) 
		{
			$log_args->is_active = $args->is_active;
		}
		if($args->is_correct) 
		{
			$log_args->is_correct = $args->is_correct;
		}
		if($args->weight) 
		{
			$log_args->weight = $args->weight;
		}
		
		$oDB = & DB::getInstance();
		$oDB->begin();
		$output = executeQuery('quiz.update_question_log', $log_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return new Object(TRUE, $output);
		}
		$oDB->commit();
		return new Object(0);
	}
	
	/**
	 * Return log for a certain question
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return stdClass
	 */
	function getQuestionLog($args) 
	{
		if(!$args->question_srl) 
		{
			return FALSE;
		}
		$log_args->question_srl = $args->question_srl;
		$log_args->user_identifier = $this->getUserIdentifier();
		if($args->is_active) 
		{
			$log_args->is_active = $args->is_active;
		}
		else 
		{
			$log_args->is_active = 'Y';
		}
		$output = executeQueryArray('quiz.getQuizQuestionLog', $log_args);
		if(!$output->data) 
		{
			return NULL;
		}
		return $output->data[0];
	}
	
	/**
	 * Returs the log for all the questions in the current quiz
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return stdClass
	 */
	function getQuestionsLog($args) 
	{
		if(!$args->module_srl) 
		{
			return FALSE;
		}
		$log_args->module_srl = $args->module_srl;
		$log_args->user_identifier = $this->getUserIdentifier();
		if($args->is_active) 
		{
			$log_args->is_active = $args->is_active;
		}
		else 
		{
			$log_args->is_active = 'Y';
		}
		$output = executeQueryArray('quiz.getQuizQuestionsLog', $log_args);
		if(!$output) 
		{
			return FALSE;
		}
		return $output->data;
	}
	
	/**
	 * Counts the number of times a question was already answered
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return stdClass
	 */
	function getQuizQuestionLogCount($args) 
	{
		if(!$args->question_srl) 
		{
			return FALSE;
		}
		$log_args->question_srl = $args->question_srl;
		$log_args->user_identifier = $this->getUserIdentifier();
		$output = executeQueryArray('quiz.getQuestionLogCount', $log_args);
		if(!$output) 
		{
			return FALSE;
		}
		return $output->data[0]->count;
	}
	
	/**
	 * Returns difference between two dates
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $date1 string
	 * @param $date2 string
	 * @return int
	 */
	function getDateDiff($date1, $date2) 
	{
		return abs(strtotime($date2) - strtotime($date1));
	}
	
	/**
	 * Returns the difference between two dates formatted for displaying on the frontend
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $date1 string
	 * @param $date2 string
	 * @return string
	 */
	function getFormattedDateDiff($date1, $date2) 
	{
		$diff = $this->getDateDiff($date1, $date2);
		return $this->getFormattedTime($diff);
	}

	/**
	 * Returns the a formatted date for displaying on the frontend
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $diff int
	 * @return string
	 */	
	function getFormattedTime($diff) 
	{
		$years = floor($diff / (365 * 60 * 60 * 24));
		$months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
		$days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
		$hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));
		$minutes = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);
		$seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minutes * 60));
		$result = '';
		
		if($years != 0) 
		{
			$result .= sprintf("%d years", $years);
		}
		if($months != 0) 
		{
			$result .= ($result != '' ? ', ' : '') . sprintf("%d months", $months);
		}
		if($days != 0) 
		{
			$result .= ($result != '' ? ', ' : '') . sprintf("%d days", $days);
		}
		if($hours != 0) 
		{
			$result .= ($result != '' ? ', ' : '') . sprintf("%d hours", $hours);
		}
		if($minutes != 0) 
		{
			$result .= ($result != '' ? ', ' : '') . sprintf("%d minutes", $minutes);
		}
		if($seconds != 0) 
		{
			$result .= ($result != '' ? ', ' : '') . sprintf("%d seconds", $seconds);
		}
		return $result;
	}
	
	/**
	 * Returs the view for a question and log
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $question Question
	 * @param $log stdClass
	 * @param $quiz_info QuizInfo
	 * @param $quiz_log stdClass
	 * @return string
	 */
	function getQuestionHTML($question, $log, $quiz_info, $quiz_log = NULL)
	{
		// Question already answered and correct
		if(($log && $log->is_correct == 'Y')) 
		{
			$duration = $this->getFormattedDateDiff($log->start_date, $log->end_date);
			Context::set('duration', $duration);
			$template_file = 'question.correct';
			$class = 'correct';
			if($quiz_info->showQuestionsAllAtOnce()) 
			{
				$class .= ' uncollapsed';
			}
		} 		
		else
		{
			//  Question already answered and incorrect 
			if($log && $log->is_correct == 'N') 
			{
				if($quiz_info->timeQuestions()) 
				{
					$template_file = 'question.incorrect.timed';
					$class = 'incorrect timed';
				}
				else 
				{
					$template_file = 'question.incorrect.untimed';
					$class = 'incorrect untimed';
				}
				if($quiz_info->showQuestionsAllAtOnce()) 
				{
					$class .= ' uncollapsed';
				}
			}
			else 
			{
				$template_file = 'question.form';
				$class = 'form teaser';
			}			
		}

		if($quiz_info->showQuestionsOneAtATime()) 
		{
			$activation_date = strtotime($question->getActivationDate());
			$today = strtotime(date("Y-m-d"));
			
			// Question is not avaible yet
			if($today - $activation_date < 0) 
			{
				$template_file = 'question.inactive';
				$class = 'inactive';
			}
		}
		// Question was not started and timing is used
		if((!$log && $quiz_info->timeQuestions())) 
		{
			$template_file = 'question.teaser';
			$class = 'teaser';
		}
		Context::set('class', $class);
		// Retrieve template file path
		$template_path = $this->getSkinTemplatePath();
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($template_path, $template_file);
	}

	/**
	 * Returns most recent question that became active (visible)
	 * Used for a "latest question" widget
	 *
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $module_srl int
	 * @return null|Object $activeQuestion Question
	 */
	function getLatestActiveQuestion($module_srl) 
	{
		if(!$module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$args->module_srl = $module_srl;
		$questions = $this->getQuestions($args);
		if($questions) 
		{
			$activeQuestion = $questions[0];
		}
		else 
		{
			return NULL;
		}
		if(!$this->isActive($activeQuestion)) // Question is inactive
		{
			return NULL;
		}
		$q_count = count($questions);
		for($i = 1;	$i < $q_count; $i++) 
		{
			if(!$this->isActive($questions[$i])) 
			{
				break;
			}
			else 
			{
				$activeQuestion = $questions[$i];
			}
		}
		return $activeQuestion;
	}
	
	/**
	 * Checks if a question has become visible or not (active)
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $question Question
	 * @return boolean
	 */	
	function isActive($question) 
	{
		$activation_date = strtotime($question->getActivationDate());
		$today = strtotime(date("Y-m-d"));
		if($today - $activation_date < 0) // Question is inactive
		{
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Returs a list of all the questions that are visible
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $module_srl int
	 * @return array
	 */
	function getActiveQuestions($module_srl) 
	{
		if(!$module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		
		$args->module_srl = $module_srl;
		$questions = $this->getQuestions($args);
		if(!$questions) 
		{
			return NULL;
		}
		foreach($questions as $question) 
		{
			if($this->isActive($question)) 
			{
				$activeQuestions[] = $question;
			}
		}
		return $activeQuestions;
	}
	
	/**
	 * Adds a subscriber
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return Object
	 */	
	function insertSubscriber($args) 
	{
		$email = $args->email_address;
		if(!$email) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		if($this->isSubscribed($email)) 
		{
			return new Object(TRUE, 'You are already subscribed!');
		}
		$s_args->email_address = $email;
		if($args->member_srl) 
		{
			$s_args->member_srl = $args->member_srl;
		}
		
		$oDB = &DB::getInstance();
		$oDB->begin();
		$output = executeQuery('quiz.insert_subscriber', $s_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return new Object(TRUE, 'msg_error_occured');
		}
		$oDB->commit();
		return new Object(0);
	}
	
	/**
	 * Deletes a subscriber
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return Object
	 */		
	function deleteSubscriber($args) 
	{
		$email = $args->email;
		if(!$email) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$s_args->email = $email;
		$oDB = &DB::getInstance();
		$oDB->begin();
		$output = executeQuery('quiz.delete_subscriber', $s_args);
		if(!$output->toBool()) 
		{
			$oDB->rollback();
			return new Object(TRUE, $output);
		}
		$oDB->commit();
		return new Object(0);
	}
	
	/**
	 * Returns a list of all subscribers to the quiz
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return array
	 */		
	function getAllSubscribers() 
	{
		$output = executeQueryArray('quiz.getSubscribers');
		if(!$output) 
		{
			return FALSE;
		}
		return $output->data;
	}
	
	/**
	 * Sends an email to all subscribed users
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $args stdClass
	 * @return Object
	 */		
	function sendEmailToAllSubscribers($args) 
	{
		if(!$args->sender_name || !$args->sender_email || !$args->title || !$args->content) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
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
		if($subscribers) 
		{
			foreach($subscribers as $subscriber) 
			{
				$oMail->setReceiptor(NULL, $subscriber->email_address);
				$oMail->send();
				$mailsSent++;
			}
		}
		return new Object(0, sprintf(Context::getLang('msg_send_success'), $mailsSent));
	}
	
	/**
	 * Checks if a given email addres exists in the subscribers list
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @param $email string
	 * @return boolean
	 */		
	function isSubscribed($email) 
	{
		if(!$email) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		
		$args->email_address = $email;
		$oDB = & DB::getInstance();
		$output = executeQuery('quiz.getSubscriber', $args);
		if(!$output->toBool()) 
		{
			return new Object(TRUE, $output);
		}
		if($output->data) 
		{
			return TRUE;
		}
		return FALSE;
	}
}
/* End of file quiz.model.php */
/* Location: quiz.model.php */
