<?php
    /**
     * @class  quizView
     * @author Arnia Software
     * @brief  
     **/

    class quizView extends quiz {

        /**
         * @brief Constructor function - initializes the state for all of this classes' methods.
         **/
        function init() {
			// Retrieve the module_srl of the current module
            $module_srl = Context::get('module_srl');
            if(!$module_srl && $this->module_srl) {
                $module_srl = $this->module_srl;
                Context::set('module_srl', $module_srl);
            }          
            
        	// Load information about current module
        	$oModuleModel = &getModel('module');
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else {
                    ModuleModel::syncModuleToSite($module_info);
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }

            if($module_info && $module_info->module != 'quiz') return $this->stop("msg_invalid_request");

            // Set the path for the frontend template files
            $oQuizModel = &getModel('quiz');
            $template_path = $oQuizModel->getSkinTemplatePath();
            $this->setTemplatePath($template_path);
        }

        /**
         * @brief Index page for a given quiz
         **/
        function dispQuizIndex() {
        	// If no quiz is specified (module_srl), return
        	$args->module_srl = Context::get('module_srl');
        	if(!$args->module_srl)
        		return $this->alertMessage('msg_invalid_request');
        	
        	$args->question_srl = Context::get('question_srl');
        	if($args->question_srl){
        		// Redirect to quiz index page
        		$this->dispQuizQuestion(); return;
        	}
        	        		
        	// Retrieve list of questions for the current quiz from the database
        	$oQuizModel = &getModel('quiz');
        	$output = $oQuizModel->getQuestions($args);
        	if(!$output)
        		return $this->alertMessage('msg_invalid_request');
        		
        	$questions_list = $output;
            
        	// See if quiz ended or not
        	$module_info = Context::get('module_info');
			$output = $oQuizModel->quizIsActive($module_info);
			Context::set('quiz_is_active', $output);        	
        	
            // Retrieve list of answers from the database (only for multiple_choice quizzes)
            $answers_list = array();
            $output = executeQueryArray('quiz.getAnswers', $args);
            if($output && $output->data)
	            foreach($output->data as $answer){
	            	if(!isset($answers_list[$answer->question_srl])) $answers_list[$answer->question_srl] = array();
	            	array_push($answers_list[$answer->question_srl], $answer);
	            }
            
            // Retrieve information about this quiz related to the current user
            $log_args->module_srl = $args->module_srl;
            $quiz_log = $oQuizModel->getQuizLog($log_args);
            
            $questions_log = array();
            if($quiz_log){
		        // If the user had something to do with this quiz before
		        // 1. load current score
				Context::set('score', $quiz_log->score);
            	
		        // 2. load answers log, using the same args as for the quiz log
		        $log_args->is_active = 'Y';
		        $output = $oQuizModel->getQuestionsLog($log_args);
		        
		        foreach($output as $log){
		        	foreach($log as $key => $val){
		        		$questions_log[$log->question_srl]->{$key} = $val;	
		        	}
		        }
            }
            
            $questionsHTML = array();
            foreach($questions_list as $question){
            	$question_log = $questions_log[$question->question_srl];
            	$question->answers = $answers_list[$question->question_srl];
            	Context::set('question', $question);
            	Context::set('log', $question_log);
            	$questionHTML = $oQuizModel->getQuestionHTML($question
            												, $question_log 
            												, $this->module_info->use_question_activation_date
            												, $this->module_info->use_timing
            												, $quiz_log
            												);
            	$questionsHTML[$question->question_srl] = $questionHTML;
            }
            Context::set('questionsHTML', $questionsHTML);
            
        	// Set template for the case when users have access to the quiz
        	$this->setTemplateFile('index');
        }
    
        /**
         * @brief Question page
         **/
        function dispQuizQuestion(){
        	$args->question_srl = Context::get('question_srl');
        	if(!$args->question_srl){
        		// Redirect to quiz index page
        		$this->dispQuizIndex(); return;
        	}
        	
        	$oQuizModel = &getModel('quiz');
        	
        	// Retrieve question data
        	$output = $oQuizModel->getQuestion($args);
        	if(!$output)
        		return $this->alertMessage('msg_invalid_request');
        	$question = $output;
        	Context::set('question', $question);

            // If is not allowed to see this quiz, redirect to please_login page
        	if(!$this->grant->take_quiz){
        		$this->setTemplateFile('please_login');
        		return;
        	}
        	        	
        	$module_info = Context::get('module_info');
			$output = $oQuizModel->quizIsActive($module_info);
			Context::set('quiz_is_active', $output);
			
        	// Retrieve question log data
        	$output = $oQuizModel->getQuestionLog($args);
        	$question_log = $output;
        	Context::set('log', $question_log);
        	
        	// Get HTML for question
        	$questionHTML = $oQuizModel->getQuestionHTML($question
        												, $question_log
            											, $this->module_info->use_question_activation_date
            											, $this->module_info->use_timing        												
        												);
        	Context::set('questionHTML', $questionHTML);
        	Context::set('question_srl', $args->question_srl);
        	
        	$this->setTemplateFile('question');
        }

        /**
         * @brief Displays a list of all the score for a given quiz
         **/        
        // TODO Change XML query to retrieve user_name from quiz_log table instead of join with member
        function dispGlobalStanding(){
            // If no quiz is specified (module_srl), return
        	$args->module_srl = Context::get('module_srl');
        	if(!$args->module_srl)
        		return $this->alertMessage('msg_invalid_request');
        	
        	// If is not allowed to see this quiz, redirect to please_login page
        	//if(!$this->grant->take_quiz){
        	//	$this->setTemplateFile('please_login');
        	//	return;
        	//}

        	// Retrieve scores from the DB, in descending order
        	$output = executeQueryArray('quiz.getQuizRanking', $args);
        	if($output->error)
        		return $this->alertMessage($output->message);
        	
        	if($output->data){
        		$users = $output->data;
        	}
        	
        	Context::set('users', $users);
        	
        	$this->setTemplateFile('quizRankings');
        }
    
    }
?>
