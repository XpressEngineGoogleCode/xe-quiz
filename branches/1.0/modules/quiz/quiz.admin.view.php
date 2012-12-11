<?php
/**
 * File containing the Quiz admin view class
 */
/**
 * View functions for the admin interface
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class QuizAdminView extends Quiz
{

	/**
	 * Constructor
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return \ModuleObject
	 */
	function init() 
	{
		// Retrieve the module_srl of the current module
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl) 
		{
			$module_srl = $this->module_srl; 
			Context::set('module_srl', $module_srl);
		}
		
		// Load information about current module
		$oModuleModel = &getModel('module');
		if($module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info) 
			{
				Context::set('module_srl', ''); 
				$this->act = 'list';
			}
			else 
			{
				ModuleModel::syncModuleToSite($module_info); 
				$this->module_info = $module_info; 
				Context::set('module_info', $module_info);
			}
		}
		if($module_info && $module_info->module != 'quiz') 
		{
			return $this->stop("msg_invalid_request");
		}
		
		// Load module categories in order to populate the dropdown inside the views
		$module_category = $oModuleModel->getModuleCategories(); 
		Context::set('module_category', $module_category);
		
		// Set path for the template files
		$template_path = sprintf("%stpl/", $this->module_path); 
		$this->setTemplatePath($template_path);
	}

	/**
	 * Index view - displays a list of all quizzes
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 */
	function dispQuizAdminList() 
	{
		// Retrieve all quizzes with standard attributes
		$output = executeQueryArray('quiz.getAllQuizzes', $args); 
		$quiz_list = $output->data;
		
		// Append custom attributes (extra vars)
		$oModuleModel = &getModel('module'); 
		$oModuleModel->addModuleExtraVars($quiz_list); 
		
		Context::set('quiz_list', $quiz_list); 
		$this->setTemplateFile('quiz_list');
	}

	/**
	 * Quiz view - Add/Edit a quiz
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 */
	function dispQuizAdminQuizInfo() 
	{
		$this->dispQuizAdminInsertQuiz();
	}

	/**
	 * Quiz view - Add/Edit a quiz
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return Object
	 */
	function dispQuizAdminInsertQuiz() 
	{
		if(!in_array($this->module_info->module, array('admin', 'quiz'))) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		// Load data needed to populate the dropdowns in the template file:
		// Load a list of all the skins available for this module
		$oModuleModel = &getModel('module'); 
		$skin_list = $oModuleModel->getSkins($this->module_path); 
		Context::set('skin_list', $skin_list);
		
		// Load a list of all the layouts available
		$oLayoutModel = &getModel('layout'); 
		$layout_list = $oLayoutModel->getLayoutList(); 
		Context::set('layout_list', $layout_list);
		
		// Load datepicker javascript library
		Context::loadJavascriptPlugin('ui.datepicker'); 
		
		$this->setTemplateFile('quiz_insert');
	}

	/**
	 * Questions view - displays a list of all the questions associated with a quiz
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return Object
	 */
	function dispQuizAdminQuizQuestions() 
	{
		$args->module_srl = Context::get('module_srl');
		if(!$args->module_srl) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		// Retrieve all questions
		$output = executeQueryArray('quiz.getQuestions', $args); 
		Context::set('questions_list', $output->data);
		
		// Get options for multiple choice questions
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
		Context::set('answers_list', $answers_list); 
		$this->setTemplateFile('questions_list');
	}

	/**
	 * Question view - Add/Edit a question
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object
	 */
	function dispQuizAdminInsertQuestion() 
	{
		if(!in_array($this->module_info->module, array('admin', 'quiz'))) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		$args->question_srl = Context::get('question_srl');
		if($args->question_srl) 
		{
			$oQuizModel = &getModel('quiz'); 
			$question = $oQuizModel->getQuestion($args);
		}
		else 
		{
			$question = new Question($args); 
		}
		
		Context::set('question', $question); 
		Context::loadJavascriptPlugin('ui.datepicker'); 
		
		$this->setTemplateFile('question_insert');
	}

	/**
	 * Answer view - Add/Edit an option for multiple choice questions
	 * TODO Hide getAnswer inside model
	 *
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return Object
	 */
	function dispQuizAdminInsertAnswer() 
	{
		if(!in_array($this->module_info->module, array('admin', 'quiz'))) 
		{
			return new Object(-1, 'msg_invalid_request');
		}
		$answer->answer_srl = Context::get('answer_srl');
		if($answer->answer_srl) 
		{
			$output = executeQueryArray('quiz.getAnswer', $answer);
			if($output->data) 
			{
				unset($answer); 
				$answer = $output->data[0];
			}
		}
		
		Context::set('answer', $answer); 
		$this->setTemplateFile('answer_insert');
	}

	/**
	 * Manage permissions view - Displays template files from the 'member' module
	 * Allows the user to customize quiz permissions
	 *
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 */
	function dispQuizAdminGrantInfo() 
	{
		$oModuleAdminModel = &getAdminModel('module'); 
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant); 
		
		Context::set('grant_content', $grant_content); 
		$this->setTemplateFile('grant_list');
	}

	/**
	 * View for managing quiz logs
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 */
	function dispQuizAdminQuizLog() 
	{
		$this->setTemplateFile('quiz_logs');
	}

	/**
	 * View for managing quiz subscribers
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 */
	function dispQuizAdminQuizEmail() 
	{
		$this->setTemplateFile('send_email');
	}

	/**
	 * Helper function to dislay messages as alerts
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 * @param $message string - Message to display to the user
	 */
	function alertMessage($message) 
	{
		$script = sprintf('<script type="text/javascript"> xAddEventListener(window,"load", function() { alert("%s"); } );</script>', Context::getLang($message)); 
		Context::addHtmlHeader($script);
	}
}
/* End of file quiz.admin.view.php */
/* Location: quiz.admin.view.php */
