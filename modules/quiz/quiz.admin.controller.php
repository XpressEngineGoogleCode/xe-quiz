<?php
/**
 * File containing the Quiz admin controller class
 */
/**
 * Controller functions for the admin interface
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class QuizAdminController extends Quiz
{	
	/**
	 * Create or update a quiz (module)
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return
	 */
	function procQuizAdminInsertQuiz() 
	{
		// Get module controller 
		$oModuleController = &getController('module'); 
		
		// Get request arguments
		$args = Context::getRequestVars(); 
		$args->module = 'quiz'; 
		$args->mid = $args->quiz_name; 
		unset($args->quiz_name);
		
		// Insert or update module info
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args); 
			$msg_code = 'success_registed';
		}
		else 
		{
			$output = $oModuleController->updateModule($args); 
			$msg_code = 'success_updated';
		}
		if(!$output->toBool()) 
		{
			return $output; 
		}
		
		$this->add('module_srl', $output->get('module_srl')); 
		$this->setMessage($msg_code);
	}

	/**
	 * Create or update a question
	 * TODO Move insertQuestion logic to Model
	 *
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return object
	 */
	function procQuizAdminInsertQuestion() 
	{
		$args = Context::getRequestVars(); 
		
		$is_insert = !$args->question_srl;
		if($is_insert == TRUE)
		{
			$question_srl = getNextSequence();
		}
		else 
		{
			$question_srl = $args->question_srl; 
		}

		unset($question_args); 
		$question_args->question_srl = $question_srl; 
		$question_args->module_srl = $args->module_srl; 
		$question_args->title = $args->title;
		if($args->list_order) 
		{
			$question_args->list_order = $args->list_order;
		}
		else 
		{
			$question_args->list_order = $question_srl; 
		}
		$question_args->description = $args->description ? $args->description : ''; 
		$question_args->hint = $args->hint; 
		$question_args->activation_date = $args->activation_date; 
		$question_args->is_multiple_choice = $args->is_multiple_choice; 
		$question_args->answer = $args->answer;
		if($args->weight) 
		{
			$question_args->weight = $args->weight;
		}
		else
		{
			$question_args->weight = 1;
		}

		$oDB = &DB::getInstance(); 
		$oDB->begin(); 		
		
		if($is_insert == TRUE)
		{
			$output = executeQuery('quiz.insert_question', $question_args);
		}
		else 
		{
			$output = executeQuery('quiz.update_question', $question_args);
		}
		
		if(!$output->toBool()) 
		{
			$oDB->rollback(); 
			return $output;
		}
		$oDB->commit(); 
		
		$this->setMessage('success_registed');
	}

	/**
	 * Create or update an answer (for multiple choice questions)
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return object
	 */
	function procQuizAdminInsertAnswer() 
	{
		$args = Context::getRequestVars(); 
		
		$is_insert = !$args->answer_srl;
		if($is_insert) 
		{
			$answer_srl = getNextSequence();
		}
		else 
		{
			$answer_srl = $args->answer_srl; 
		}
				
		unset($answer_args); 
		$answer_args->answer_srl = $answer_srl; 
		$answer_args->question_srl = $args->question_srl; 
		$answer_args->module_srl = $args->module_srl; 
		$answer_args->title = $args->title;
		if($args->list_order) 
		{
			$answer_args->list_order = $args->list_order;
		}
		else 
		{
			$answer_args->list_order = $answer_srl; 
		}
		$answer_args->is_correct = $args->is_correct;

		$oDB = &DB::getInstance(); 
		$oDB->begin(); 
		
		if($is_insert) 
		{
			$output = executeQuery('quiz.insert_answer', $answer_args);
		}
		else 
		{
			$output = executeQuery('quiz.update_answer', $answer_args);
		}
		if(!$output->toBool()) 
		{
			$oDB->rollback(); 
			return $output;
		}
		$oDB->commit(); 
		$this->setMessage('success_registed');
	}

	/**
	 * Delete an answer (for multiple choice questions)
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return object
	 */
	function procQuizAdminDeleteAnswer() 
	{
		$args = Context::getRequestVars();
		if(!$args->answer_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$oDB = &DB::getInstance(); 
		$oDB->begin(); 
		
		$output = executeQuery('quiz.delete_answer', $args);
		if(!$output->toBool()) 
		{
			$oDB->rollback(); 
			return $output;
		}
		$oDB->commit(); 
		$this->setMessage('success_registed');
	}

	/**
	 * Deletes a question
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return object
	 */
	function procQuizAdminDeleteQuestion() 
	{
		$args = Context::getRequestVars();
		if(!$args->question_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$oDB = &DB::getInstance(); 
		$oDB->begin(); 
		
		$output = executeQuery('quiz.delete_question', $args);
		if(!$output->toBool()) 
		{
			$oDB->rollback(); 
			return $output;
		}
		$output = executeQuery('quiz.delete_answers', $args);
		if(!$output->toBool()) 
		{
			$oDB->rollback(); 
			return $output;
		}
		$oDB->commit(); 
		$this->setMessage('success_registed');
	}

	/**
	 * Deletes a quiz (module)
	 * TODO Also delete questions and answers and logs
	 *
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object
	 */
	function procQuizAdminDeleteQuiz() 
	{
		$module_srl = Context::get('module_srl');
		if(!$module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$oModuleController = &getController('module'); 
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) 
		{
			return $output; 
		}
		$this->setMessage('success_deleted');
	}

	/**
	 * Deletes all question logs and quiz logs for a given quiz (module_srl)
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return object
	 */
	function procQuizAdminClearLogs() 
	{
		$args = Context::getRequestVars();
		if(!$args->module_srl) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$oDB = &DB::getInstance(); 
		$oDB->begin(); 
		
		$output = executeQuery('quiz.delete_all_quiz_question_log', $args);
		if(!$output->toBool()) 
		{
			$oDB->rollback(); 
			return $output;
		}
		$output = executeQuery('quiz.delete_all_quiz_log', $args);
		if(!$output->toBool()) 
		{
			$oDB->rollback(); 
			return $output;
		}
		$oDB->commit(); 
		$this->setMessage('success_registed');
	}

	/**
	 * Sends email to all users who subscried to XE notifications
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object
	 */
	function procQuizAdminSendEmails() 
	{
		$args = Context::getRequestVars();
		if(!$args->sender_name || !$args->sender_email || !$args->title || !$args->content) 
		{
			return new Object(TRUE, 'msg_invalid_request');
		}
		$oQuizModel = &getModel('quiz'); 
		$output = $oQuizModel->sendEmailToAllSubscribers($args);
		if(!$output->toBool()) 
		{
			return $output; 
		}
		$this->setMessage($output->getMessage());
	}
}
/* End of file quiz.admin.controller.php */
/* Location: quiz.admin.controller.php */
