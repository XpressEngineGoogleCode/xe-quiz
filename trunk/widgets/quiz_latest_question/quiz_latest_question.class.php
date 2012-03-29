<?php
    /**
     * @class extra_var
     * @author sol (sol@ngleader.com)
     * @brief 
     * @version 0.1
     **/

    class quiz_latest_question extends WidgetHandler {

        function proc($args) {
        	// Get info about current module
        	$module_srl = $args->selected_module_srl;
        	
        	$oModuleModel = &getModel('module');
        	$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
        	        	
        	Context::set('mid', $module_info->mid);
        	// Retrieve latest question for current module
        	$oQuizModel = &getModel('quiz');
        	$questions = $oQuizModel->getActiveQuestions($module_srl);
        	Context::set('questions', $questions);
        	
        	// Set template path
        	$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
        	$tpl_file = 'questions';
        	
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);           
        }  
    }  

?>