<?php
    if(!defined("__ZBXE__")) exit();
    /**
     * @file quiz_simple_menu.addon.php
     * @author Arnia Software (xe_dev@arnia.ro)
     * @brief Removes all menu links except for Member info from the default Member menu.
     **/

    if($called_position == 'after_module_proc' && $this->act == 'dispMemberSignUpForm'){
    	    $oQuizModel = &getModel('quiz');
            $template_path = $oQuizModel->getSkinTemplatePath();
            $this->setTemplatePath($template_path);
    		$this->setTemplateFile('signup_form');
    }      
    
    $logged_info = Context::get('logged_info');
    if(!$logged_info) return;

	if($called_position == 'before_module_init') {
    		$logged_info = Context::get('logged_info');
    		unset($logged_info->menu_list['dispMemberScrappedDocument']);
    		unset($logged_info->menu_list['dispMemberSavedDocument']);
    		unset($logged_info->menu_list['dispMemberOwnDocument']);
    		Context::set('logged_info', $logged_info);
            $_SESSION['logged_info'] = $logged_info;
    }
    else if($called_position == 'after_module_proc' && $this->act == 'dispMemberModifyInfo'){
    	    $oQuizModel = &getModel('quiz');
            $template_path = $oQuizModel->getSkinTemplatePath();
            $this->setTemplatePath($template_path);
    		$this->setTemplateFile('modify_info');
    }
    else if($called_position == 'after_module_proc' && $this->act == 'dispMemberInfo'){
    	    $oQuizModel = &getModel('quiz');
            $template_path = $oQuizModel->getSkinTemplatePath();
            $this->setTemplatePath($template_path);
    		$this->setTemplateFile('member_info');
    }
          
    
?>
