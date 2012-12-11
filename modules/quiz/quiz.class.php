<?php
/**
 * File containing the Quiz module base class
 */
include ('quiz.item.php'); 

/**
 * Quiz module base class
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class Quiz extends ModuleObject
{
	
	/**
	 * Module installation - method called only once, on module installation
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return Object
	 */
	function moduleInstall() 
	{
		return new Object();
	}
	
	/**
	 * Returns true if the module needs to be updated
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object
	 */
	function checkUpdate() 
	{
		$oDB = &DB::getInstance();
		
		// 2011.02.17 Added new columns for allowing to show/hide questions until a certain time
		// Added extra table to store user's answers per questions
		if(!$oDB->isColumnExists('quiz_questions', 'weight')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_questions', 'activation_date')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_questions', 'answer')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_questions', 'hint')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_questions', 'is_multiple_choice')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_log', 'total_time')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_log', 'start_date')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_log', 'end_date')) 
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists('quiz_log', 'user_identifier')) 
		{
			return TRUE;
		}
		if(!$oDB->isTableExists('quiz_questions_log')) 
		{
			return TRUE;
		}
		
		// 2011.02.28 Added support for subscribing to questions
		if(!$oDB->isTableExists('quiz_subscribers')) 
		{
			return TRUE;
		}
		$oModuleModel = &getModel('module');
		if($oModuleModel->getTrigger('member.getMemberMenu', 'quiz', 'controller', 'triggerGetMenu', 'before')) 
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Updates the module
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return Object
	 */
	function moduleUpdate() 
	{
		$oDB = &DB::getInstance();
		if(!$oDB->isColumnExists('quiz_questions', 'weight')) 
		{
			$oDB->addColumn('quiz_questions', 'weight', 'number', 11);
		}
		if(!$oDB->isColumnExists('quiz_questions', 'activation_date')) 
		{
			$oDB->addColumn('quiz_questions', 'activation_date', 'date');
		}
		if(!$oDB->isColumnExists('quiz_questions', 'answer')) 
		{
			$oDB->addColumn('quiz_questions', 'answer', 'text');
		}
		if(!$oDB->isColumnExists('quiz_questions', 'hint')) 
		{
			$oDB->addColumn('quiz_questions', 'hint', 'text');
		}
		if(!$oDB->isColumnExists('quiz_questions', 'is_multiple_choice')) 
		{
			$oDB->addColumn('quiz_questions', 'is_multiple_choice', 'char', 1, 'N');
		}
		if(!$oDB->isColumnExists('quiz_log', 'total_time')) 
		{
			$oDB->addColumn('quiz_log', 'total_time', 'number', 11);
		}
		if(!$oDB->isColumnExists('quiz_log', 'start_date')) 
		{
			$oDB->addColumn('quiz_log', 'start_date', 'date');
		}
		if(!$oDB->isColumnExists('quiz_log', 'end_date')) 
		{
			$oDB->addColumn('quiz_log', 'end_date', 'date');
		}
		if(!$oDB->isColumnExists('quiz_log', 'user_identifier')) 
		{
			$oDB->addColumn('quiz_log', 'user_identifier', 'varchar', 128, '', TRUE);
		}
		if(!$oDB->isTableExists('quiz_questions_log')) 
		{
			$oDB->createTableByXmlFile('./modules/quiz/schemas/quiz_questions_log.xml');
		}
		if(!$oDB->isTableExists('quiz_subscribers')) 
		{
			$oDB->createTableByXmlFile('./modules/quiz/schemas/quiz_subscribers.xml');
		}
		$oModuleController = &getController('module'); 
		$oModuleModel = &getModel('module');
		if($oModuleModel->getTrigger('member.getMemberMenu', 'quiz', 'controller', 'triggerGetMenu', 'before')) 
		{
			$oModuleController->deleteTrigger('member.getMemberMenu', 'quiz', 'controller', 'triggerGetMenu', 'before'); 
		}
		return new Object(0, 'success_updated');
	}

	/**
	 * Clears cache files
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 */
	function recompileCache() 
	{
	}
	
}
/* End of file quiz.class.php */
/* Location: quiz.class.php */
