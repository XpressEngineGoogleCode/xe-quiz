<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  board modules's basic language pack
     **/

	$lang->about_browser_title = 'Text that will be displayed in the browser title.';

    $lang->quiz = 'Quiz';
    
	// Admin - insert / edit quiz
	$lang->start_date = 'Start date';
	$lang->end_date = 'End date';
	$lang->about_start_date = 'The date when the competition becomes active. Users will be given points for their answers starting from this date.';
	$lang->about_end_date = 'The date when the competition finishes. Users will be given points for their answers only until this date.';
	$lang->open_answer = 'Open answer';
	$lang->multiple_choice = 'Multiple choice';
	$lang->use_question_activation_date = 'Use question activation date';
	$lang->about_use_question_activation_date = 'Choose whether questions will be made visible one at a time and must be answered individually (Y) or answer all questions at once (N). This is usually used for competitions: where you make a new question available each week. You can also use this option if you want to time your users (how long it takes them to answer).';
	$lang->use_timing = 'Use timing';
	$lang->about_use_timing = 'Choose whether rankings should take into consideration the time it took the user to answer the questions.';
	
	// Admin - insert / edit question
	$lang->question_title = 'Question text';
	$lang->question_description = 'Question description';
	$lang->question_hint = 'Question hint';
	$lang->about_question_title = '';
	$lang->about_question_description = 'Text displayed only after the test was taken, as an explanation for the correct answers.';
	$lang->about_question_hint = 'This field indicates how the answer should look like - whether the items, if multiple,  should be separated by comma or the other required delimiter.';
	$lang->list_order = 'Order';
	$lang->about_question_list_order = 'Order in which the questions will be displayed';
	$lang->activation_date = 'Activation date.';
	$lang->about_activation_date = 'Date when the question will become visible (only applies to ongoing competitions).';
	$lang->question_weight = 'Weight';
	$lang->about_question_weight = 'How much a question is worth. This represents the number of points to be given to a user that answers correctly to this question';
	$lang->answer = 'Correct answer';
	$lang->about_answer = '';
	$lang->type = 'Question type';
	$lang->about_type = 'Define what type of question this quiz will be.';
	$lang->select_yes = 'Yes';
	$lang->select_no = 'No';
	
	// Admin - questions list for a quiz
	$lang->questions = 'Questions';
	$lang->delete_question = 'Delete question';
	$lang->add_answer = 'Add answer';
	$lang->edit = 'Edit';
	$lang->edit_question = 'Edit this question';
	$lang->delete_answer = 'Delete answer';
	$lang->add_new_question = 'Add new question';
	
	// Member quiz signup
	$lang->user_id = 'Username';
	$lang->user_name = 'Full name';
	$lang->about_user_name = 'Name should between 2~40 letters long';
	$lang->subscribe_to_questions = 'Subscribe to questions';
	$lang->about_subscribe_to_questions = 'Choose whether you would like to receive a notification when a new question becomes available.';
	
	// Send emails
	$lang->sender_name = 'Sender name';
	$lang->about_sender_name = '';
	$lang->sender_email = 'Sender email';
	$lang->about_sender_email = '';
	$lang->title = 'Subject';
	$lang->about_title = 'Email subject';
	$lang->content = 'Content';
	$lang->about_content = 'Email text';
	
    $lang->except_notice = 'Exclude Notices';
    $lang->use_anonymous = 'Use Anonymous';

    $lang->cmd_manage_menu = 'Manage Menus';
    $lang->list_target_item = 'Target Item';
    $lang->list_display_item = 'Display Item';
    $lang->summary = 'Summary';
    $lang->thumbnail = 'Thumbnail';
    $lang->last_post = 'Last post';

    // Category attributes
    $lang->last_update = 'Last update';
    $lang->document_count = 'No. articles';
    
    // Item
    $lang->search_result = 'Search Result';
    $lang->consultation = 'Consultation';
    $lang->secret = 'Secret';
    $lang->thisissecret = 'This is a secret article.';
    $lang->admin_mail = "Administrator's Mail";

    // words used in button
    $lang->cmd_quiz_list = 'Quizzes List';
    $lang->cmd_module_config = 'Common Board Setting';
    $lang->cmd_view_info = 'Quiz Info';
    $lang->cmd_list_setting = 'List Setting';
    $lang->cmd_create_quiz = 'Create a new quiz';

    // blah blah..
    $lang->about_layout_setup = 'You can manually modify board layout code. Insert or manage the widget code anywhere you want';
    $lang->about_board_category = 'You can make board categories.<br />When board category is broken, try rebuilding the cache file manually.';
    $lang->about_except_notice = 'Notice articles will not be displayed on normal list.';
    $lang->about_use_anonymous = 'Make this board into an anonymous board by hiding the author\'s information.<br /><strong>Please turn off history at additional setup. If not, editing document might show the author\'s info.</strong>';
    $lang->about_quiz = 'This module is for creating and managing quizzes.';
    $lang->about_consultation = "Non-administrator members would see their own articles.\nNon-members would not be able to write articles when using consultation.";
    $lang->about_secret = 'Users will be able to write secret articles or comments.';
    $lang->about_admin_mail = 'A mail will be sent when an article or comment is submitted.<br />Multiple mails can be sent with commas(,).';
    $lang->about_list_config = 'If using list-style skin, you may arrange items to display.<br />However, this feature might not be availble for non-official skins.<br />If you double-click target items and display items, then you can add / remove them';
	
    // Admin - Board info
    $lang->display_categories_on_index = 'Display category list on index page';
    $lang->about_display_categories_on_index = 'Choose whether to display a list of all forum categories on home page or a list of the most recent articles.';
    
    $lang->msg_not_enough_point = 'Your point is not enough to write an article in this board.';

	$lang->write_comment = 'Write a comment';
?>
