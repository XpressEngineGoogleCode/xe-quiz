function completeQuiz(ret_obj){
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    //var module_srl = ret_obj['module_srl'];
    if(error != 0){
    	alert(message);
    	return;
    }
    //var url = current_url.setQuery('act','dispQuizAdminQuizQuestions');
    //if(module_srl) url = url.setQuery('module_srl',module_srl);
    //url = url.setQuery('question_srl','');
    location.href = current_url;	
}

function doRetakeQuiz(module_srl){
	var fo_obj = jQuery('#fo_retake_quiz')[0];
    fo_obj.module_srl.value = module_srl;
    return procFilter(fo_obj, retake_quiz);
}

function completeRetakeQuiz(ret_obj){
    var error = ret_obj['error'];
    var message = ret_obj['message'];

	location.href = current_url;	
}

function completeStartQuestion(ret_obj){
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    if(error != 0){
    	alert(message);
    	return false;
    }
    
    var redirect_url = ret_obj['login_url'];
    if(redirect_url)
    	location.href = redirect_url;
    var question_srl = ret_obj['question_srl'];
    location.href = current_url.setQuery('act','dispQuizQuestion').setQuery('question_srl', question_srl);
}

function completeQuestion(ret_obj){
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    if(error != 0){
    	alert(message);
    	return false;
    }
    
    location.href = current_url.setQuery('act','').setQuery('question_srl', '');
}
function completeRetakeQuestion(ret_obj){
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    if(error != 0){
    	alert(message);
    	return false;
    }
    
    var question_srl = ret_obj['question_srl'];
    location.href = current_url.setQuery('act','dispQuizQuestion').setQuery('question_srl', question_srl);
}