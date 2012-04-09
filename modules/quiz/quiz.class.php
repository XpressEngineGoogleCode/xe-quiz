<?php
    /**
     * @class  quiz
     * @author Arnia Software
     * @brief  quiz base class
     **/

	include('quiz.item.php');

    class quiz extends ModuleObject {

        /**
         * @brief Method called only once, on module installation 
         **/
        function moduleInstall() {      	
            return new Object();
        }

        /**
         * @brief Returns true if the module needs to be updated
         **/
        function checkUpdate() {
			$oDB = &DB::getInstance();
			
			// 2011.02.17 Added new columns for allowing to show/hide questions until a certain time
			// Added extra table to store user's answers per questions
        	if(!$oDB->isColumnExists('quiz_questions', 'weight')) 
        		return true;
        		
        	if(!$oDB->isColumnExists('quiz_questions', 'activation_date')) 
        		return true;

        	if(!$oDB->isColumnExists('quiz_questions', 'answer')) 
        		return true;

        	if(!$oDB->isColumnExists('quiz_questions', 'hint')) 
        		return true;
        		        		
        	if(!$oDB->isColumnExists('quiz_questions', 'is_multiple_choice'))
        		return true;
        		
        	if(!$oDB->isColumnExists('quiz_log', 'total_time'))
        		return true;

			if(!$oDB->isColumnExists('quiz_log', 'start_date'))
        		return true;
        		
			if(!$oDB->isColumnExists('quiz_log', 'end_date'))
        		return true;        		
        		
        	if(!$oDB->isColumnExists('quiz_log', 'user_identifier')) 
        		return true;
        	
        	if(!$oDB->isTableExists('quiz_questions_log')) 
        		return true;

        	// 2011.02.28 
        	if(!$oDB->isColumnExists('quiz_questions', 'execute_answer'))
        		return true;
        	
        	// 2011.02.28 Added support for subscribing to questions
        	if(!$oDB->isTableExists('quiz_subscribers')){ 
        		return true;
        	}
        	$oModuleModel = &getModel('module');
       		if($oModuleModel->getTrigger('member.getMemberMenu', 'quiz', 'controller', 'triggerGetMenu', 'before')) return true; 
            return false;
        }

        /**
         * @brief Updates the module
         **/
        function moduleUpdate() {
			$oDB = &DB::getInstance();
        	
			if(!$oDB->isColumnExists('quiz_questions', 'weight')) {
				$oDB->addColumn('quiz_questions','weight','number',11);
			}
			
        	if(!$oDB->isColumnExists('quiz_questions', 'activation_date')) {
        		$oDB->addColumn('quiz_questions','activation_date','date');
        	}
        	if(!$oDB->isColumnExists('quiz_questions', 'answer')){
        		$oDB->addColumn('quiz_questions','answer','text');
        	}
            if(!$oDB->isColumnExists('quiz_questions', 'hint')){
        		$oDB->addColumn('quiz_questions','hint','text');
        	}        	
            if(!$oDB->isColumnExists('quiz_questions', 'is_multiple_choice')){
        		$oDB->addColumn('quiz_questions','is_multiple_choice','char',1, 'N');
        	}        	
        	if(!$oDB->isColumnExists('quiz_log', 'total_time'))
        		$oDB->addColumn('quiz_log','total_time','number',11);

			if(!$oDB->isColumnExists('quiz_log', 'start_date'))
        		$oDB->addColumn('quiz_log','start_date','date');
        		
			if(!$oDB->isColumnExists('quiz_log', 'end_date'))
        		$oDB->addColumn('quiz_log','end_date','date');
        		
        	if(!$oDB->isColumnExists('quiz_log', 'user_identifier')){
        		$oDB->addColumn('quiz_log','user_identifier','varchar', 128, '', true);
        	}        	
        	
			if(!$oDB->isTableExists('quiz_questions_log')){ 
				$oDB->createTableByXmlFile('./modules/quiz/schemas/quiz_questions_log.xml');        	
			}

			
            if(!$oDB->isColumnExists('quiz_questions', 'execute_answer')){
        		$oDB->addColumn('quiz_questions','execute_answer','char',1, 'N');
        	}        	

			if(!$oDB->isTableExists('quiz_subscribers')){ 
				$oDB->createTableByXmlFile('./modules/quiz/schemas/quiz_subscribers.xml');        	
			}
        	
        	$oModuleController = &getController('module');
        	$oModuleModel = &getModel('module');			
			if($oModuleModel->getTrigger('member.getMemberMenu', 'quiz', 'controller', 'triggerGetMenu', 'before')) 
				$oModuleController->deleteTrigger('member.getMemberMenu', 'quiz', 'controller', 'triggerGetMenu', 'before');
			
            return new Object(0, 'success_updated');
        }

        /**
         * @brief Clears cache files
         **/
        function recompileCache() {
        }
    }
?>
