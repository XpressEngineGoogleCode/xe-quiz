<?php
    /**
     * @class  quizAdminController
     * @author corina
     * @brief Controller functions for the admin interface
     **/

    class quizAdminController extends quiz {

        /**
         * @brief Constructor
         **/
        function init() {
        }

        /**
         * @brief Create or update a quiz (module)
         **/
        function procQuizAdminInsertQuiz($args = null) {
            // module 모듈의 model/controller 객체 생성
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // 게시판 모듈의 정보 설정
            $args = Context::getRequestVars();
            $args->module = 'quiz';
            $args->mid = $args->quiz_name;
            unset($args->quiz_name);

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }         	
            
            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
        }

         /**
         * @brief Create or update a question
         **/
        // TODO Move insertQuestion logic to Model
        function procQuizAdminInsertQuestion($args = null){          
            $args = Context::getRequestVars();
        	        	
            $is_insert = !$args->question_srl;
            if($is_insert == true)
        		$question_srl = getNextSequence();
			else 
				$question_srl = $args->question_srl;
				
            $oDB = &DB::getInstance();
            $oDB->begin();

            unset($question_args);
            $question_args->question_srl = $question_srl;
            $question_args->module_srl = $args->module_srl;
            $question_args->title = $args->title;
            
            if($args->list_order)
            	$question_args->list_order = $args->list_order;
            else 
            	$question_args->list_order = $question_srl;
            	
            $question_args->description = $args->description ? $args->description : '';
            $question_args->hint = $args->hint;
            $question_args->activation_date = $args->activation_date;
            $question_args->is_multiple_choice = $args->is_multiple_choice;
            $question_args->answer = $args->answer;
            if($args->weight)
            	$question_args->weight = $args->weight;
            else 
            	$question_args->weight = 1;
            
            if($is_insert == true)
            	$output = executeQuery('quiz.insert_question', $question_args);
            else 
            	$output = executeQuery('quiz.update_question', $question_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            $oDB->commit();

            $this->setMessage('success_registed');
        	
        }
        
         /**
         * @brief Create or update an answer (for multiple choice questions)
         **/
        function procQuizAdminInsertAnswer(){
        	$args = Context::getRequestVars();
       	
        	$is_insert = !$args->answer_srl;
        	if($is_insert)
        		$answer_srl = getNextSequence();
			else 	
				$answer_srl = $args->answer_srl;
        		
            $oDB = &DB::getInstance();
            $oDB->begin();

            unset($answer_args);
            $answer_args->answer_srl = $answer_srl;
            $answer_args->question_srl = $args->question_srl;
            $answer_args->module_srl = $args->module_srl;
            $answer_args->title = $args->title;
            if($args->list_order)
            	$answer_args->list_order = $args->list_order;
            else 
            	$answer_args->list_order = $answer_srl;
            
            
            $answer_args->is_correct = $args->is_correct;
            if($is_insert)
            	$output = executeQuery('quiz.insert_answer', $answer_args);
            else
            	$output = executeQuery('quiz.update_answer', $answer_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            $oDB->commit();

            $this->setMessage('success_registed');
        }

         /**
         * @brief Delete an answer (for multiple choice questions)
         **/        
        function procQuizAdminDeleteAnswer(){
        	$args = Context::getRequestVars();
            
        	if(!$args->answer_srl){
        		return new Object(true, 'msg_invalid_request');
        	}       			
            $oDB = &DB::getInstance();
            $oDB->begin();

            $output = executeQuery('quiz.delete_answer', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            $oDB->commit();

            $this->setMessage('success_registed');
        }
        
         /**
         * @brief Deletes a question
         **/
        function procQuizAdminDeleteQuestion(){
        	$args = Context::getRequestVars();

            if(!$args->question_srl){
        		return new Object(true, 'msg_invalid_request');
        	}     
        	   	
            $oDB = &DB::getInstance();
            $oDB->begin();
			
            $output = executeQuery('quiz.delete_question', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            
        	$output = executeQuery('quiz.delete_answers', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            
            $oDB->commit();

            $this->setMessage('success_registed');
        	
        }

         /**
         * @brief Deletes a quiz (module)
         **/        
        // TODO Also delete questions and answers and logs
        function procQuizAdminDeleteQuiz(){
            $module_srl = Context::get('module_srl');

            if(!$module_srl){
        		return new Object(true, 'msg_invalid_request');
        	}
            
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');      	
        }

         /**
         * @brief Deletes all question logs and quiz logs for a given quiz (module_srl)
         **/
    	function procQuizAdminClearLogs(){
        	$args = Context::getRequestVars();

        	if(!$args->module_srl){
        		return new Object(true, 'msg_invalid_request');
        	}
        	
            $oDB = &DB::getInstance();
            $oDB->begin();
			
            $output = executeQuery('quiz.delete_all_quiz_question_log', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            
        	$output = executeQuery('quiz.delete_all_quiz_log', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            
            $oDB->commit();

            $this->setMessage('success_registed');    		
    	}
    	
    	function procQuizAdminSendEmails(){
    		$args = Context::getRequestVars();
    		
    	    if(!$args->sender_name || !$args->sender_email || !$args->title || !$args->content){
        		return new Object(true, 'msg_invalid_request');
        	}    		
        	
        	$oQuizModel = &getModel('quiz');
        	$output = $oQuizModel->sendEmailToAllSubscribers($args);
        	if(!$output->toBool())
        		return $output;
        		
        	$this->setMessage($output->getMessage());        	
    	}
    }
?>
