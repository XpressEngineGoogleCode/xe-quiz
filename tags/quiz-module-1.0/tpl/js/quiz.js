function completeInsertQuiz(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];
    if(error != 0)
    	alert(message);

    var url = current_url.setQuery('act','dispQuizAdminQuizQuestions');
    if(module_srl) url = url.setQuery('module_srl',module_srl);
    if(page) url.setQuery('page',page);
    location.href = url;
}

function doChangeCategory() {
    var category_srl = jQuery('#quiz_category option:selected').val();
    location.href = decodeURI(current_url).setQuery('category',category_srl).setQuery('act', 'dispQuizAdminList');
}

function completeInsertQuestion(ret_obj){
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var module_srl = ret_obj['module_srl'];

    if(error != 0)
    	alert(message);

    var url = current_url.setQuery('act','dispQuizAdminQuizQuestions');
    if(module_srl) url = url.setQuery('module_srl',module_srl);
    url = url.setQuery('question_srl','');
    location.href = url;	
}

function completeInsertAnswer(ret_obj){
	completeInsertQuestion(ret_obj);
}

function completeDeleteAnswer(ret_obj){
	completeInsertQuestion(ret_obj);
}
function completeDeleteQuestion(ret_obj){
	completeInsertQuestion(ret_obj);
}
function completeDeleteQuiz(ret_obj){
	var url = current_url.setQuery('act','dispQuizAdminList');
	location.href = url;
}
function completeClearLogs(ret_obj){
	completeInsertQuestion(ret_obj);
}
function doDeleteAnswer(answer_srl){
	var fo_obj = jQuery('#fo_delete_answer')[0];
    fo_obj.answer_srl.value = answer_srl;
    return procFilter(fo_obj, delete_answer);
}
function doDeleteQuestion(question_srl){
	var fo_obj = jQuery('#fo_delete_question')[0];
    fo_obj.question_srl.value = question_srl;
    return procFilter(fo_obj, delete_question);
}
function doDeleteQuiz(module_srl){
	var fo_obj = jQuery('#fo_delete_quiz')[0];
    fo_obj.module_srl.value = module_srl;
    return procFilter(fo_obj, delete_quiz);	
}
function completeSendEmails(ret_obj){
	completeInsertQuestion(ret_obj);		
}