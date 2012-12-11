<?php
/**
 * File containing the Quiz model classes:
 * 	QuizInfo
 *  Question
 *  OpenQuestion
 *  MultipleChoiceQuestion
 *  UserAnswer
 *  UserAnswerForOpenQuestion
 *  UserAnswerForMultipleChoiceQuestion
 */
/**
 * Stores info specific for a quiz module instance
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class QuizInfo
{
	var $start_date;
	var $end_date;
	var $use_question_activation_date;
	var $use_timing;

	/**
	 * Class constructor
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $start_date                   string
	 * @param $end_date                     string
	 * @param $use_question_activation_date char
	 * @param $use_timing                   char
	 */
	function QuizInfo($start_date = NULL, $end_date = NULL, $use_question_activation_date = NULL, $use_timing = NULL)
	{
		$this->start_date = $start_date;
		$this->end_date = $end_date;
		$this->use_question_activation_date = $use_question_activation_date == 'Y' ? TRUE : FALSE;
		$this->use_timing = $use_timing == 'Y' ? TRUE : FALSE;
	}
	
	/**
	 * Checks if quiz is still active (current day is between start_date and end_date)
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean 
	 */
	function isActive() 
	{
		if($this->start_date && $this->end_date) 
		{
			$start = strtotime($this->start_date);
			$end = strtotime($this->end_date);
			$now = strtotime(date('Ymd'));
			if(($now - $start >= 0) && ($end - $now >= 0)) 
			{
				return TRUE;
			}
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Checks if current quiz is configured to display all questions at once
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean
	 */
	function showQuestionsAllAtOnce() 
	{
		return !$this->use_question_activation_date;
	}
	
	/**
	 * Checks if current quiz is configured to display questinos one at a time, at certain dates
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean
	 */
	function showQuestionsOneAtATime() 
	{
		return $this->use_question_activation_date;
	}
	
	/**
	 * Checks if quiz is configured to time how long it took users to answer
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean
	 */
	function timeQuestions() 
	{
		return $this->use_timing;
	}
}
/**
 * Base class for questions
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class Question
{
	var $question;

	/**
	 * Constructor
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $question stdClass
	 */
	function Question($question) 
	{
		$this->question = $question;
	}
	
	/**
	 * Returns question srl
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return int
	 */
	function getQuestionSrl() 
	{
		return $this->question->question_srl;
	}
	
	/**
	 * Returns list order
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return int 
	 */
	function getListOrder() 
	{
		return $this->question->list_order;
	}
	
	/**
	 * Returns title
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string 
	 */
	function getTitle() 
	{
		return $this->question->title;
	}
	
	/**
	 * Returns hint
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getHint() 
	{
		return $this->question->hint;
	}
	
	/**
	 * Returns activation date - date when question becomes visible
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getActivationDate() 
	{
		return $this->question->activation_date;
	}
	
	/**
	 * Returns description
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getDescription() 
	{
		return $this->question->description;
	}
	
	/**
	 * Return weight - how much points a question is worth
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return weight
	 */
	function getWeight() 
	{
		return $this->question->weight;
	}
	
	/**
	 * Returns score
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @param $user_answer UserAnswer
	 * @param $quiz QuizInfo
	 * @return int 
	 */
	function getScore($user_answer, $quiz) 
	{
		$is_correct = $this->checkAnswer($user_answer);
		if(!$is_correct) 
		{
			return 0;
		}
		
		//Only give points if quiz is active (and start/end date are defined)
		if($quiz->isActive()) 
		{
			return $this->question->weight;
		}
		else 
		{
			return 0;
		}
	}
	
	/**
	 * Returns true if question is multiple choice, false otherwise
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean 
	 */
	function isMultipleChoice() 
	{
		return FALSE;
	}
}
/**
 * Models an open question
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class OpenQuestion extends Question
{

	/**
	 * Constructor
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $question stdClass
	 */
	function OpenQuestion($question) 
	{
		parent::Question($question);
	}
	
	/**
	 * Checks if given user answer is correct
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @param $user_answer UserAnswer
	 * @return boolean 
	 */
	function checkAnswer($user_answer) 
	{
		$answer = $user_answer->getValue();
		if(strcasecmp(trim($this->question->answer), trim($answer)) == 0) 
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Overrides base method
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean 
	 */
	function isMultipleChoice() 
	{
		return FALSE;
	}
	
	/**
	 * Returns the correct answer for this question
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string 
	 */
	function getAnswer() 
	{
		return $this->question->answer;
	}
}
/**
 * Models a multiple choice question
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class MultipleChoiceQuestion extends Question
{

	/**
	 * Constructor
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $question stdClass
	 */
	function MultipleChoiceQuestion($question) 
	{
		parent::Question($question);
	}
	
	/**
	 * Returns the choices for this question (possible answers)
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return array()
	 */
	function getAnswers() 
	{
		return $this->question->answers;
	}
	
	/**
	 * Checks if user answer is correct
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @param $user_answer UserAnswer
	 * @return boolean 
	 */
	function checkAnswer($user_answer) 
	{
		$user_choices = $user_answer->getChoices();
		$answers = $this->question->answers;
		foreach($answers as $answer) 
		{
			if($answer->is_correct == "N" && in_array($answer->answer_srl, $user_choices)) 
			{
				return FALSE;
			}
			if($answer->is_correct == "Y" && !in_array($answer->answer_srl, $user_choices)) 
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	/**
	 * Overrides base method
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean 
	 */
	function isMultipleChoice() 
	{
		return TRUE;
	}
}
/**
 * Models a user answer
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class UserAnswer
{
	var $question_srl;

	/**
	 * Constructor
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $question_srl int
	 */
	function UserAnswer($question_srl) 
	{
		$this->question_srl = $question_srl;
	}
	
	/**
	 * Returns question sel
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return int 
	 */
	function getQuestionSrl() 
	{
		return $this->question_srl;
	}
}

/**
 * Models a user answer for an open question
 *
 * @author CorinaUdrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class UserAnswerForOpenQuestion extends UserAnswer
{
	var $answer;

	/**
	 * Constructor
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $question_srl int
	 */
	function UserAnswerForOpenQuestion($question_srl) 
	{
		parent::UserAnswer($question_srl);
	}

	/**
	 * Set user answer value
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $user_value string
	 */
	function setValue($user_value) 
	{
		$this->answer = $user_value;
	}
	
	/**
	 * Returns user sumbitted answer
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getValue() 
	{
		return $this->answer;
	}
	
	/**
	 * Converts answer to string - for saving to database and such
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function toString() 
	{
		return $this->answer;
	}
}
/**
 * Models a user's answer to a multiple choice question
 *
 * @author Corina Udrescu (xe_dev@arnia.ro)
 * @package quiz
 */
class UserAnswerForMultipleChoiceQuestion extends UserAnswer
{
	var $choices; // List of user selected values

	/**
	 * Constructor
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $question_srl int
	 */
	function UserAnswerForMultipleChoiceQuestion($question_srl) 
	{
		parent::UserAnswer($question_srl);
		$this->choices = array();
	}

	/**
	 * Add a user selected choice
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return void
	 * @param $user_value int - answer_srl
	 */
	function addChoice($user_value) 
	{
		$this->choices[] = $user_value;
	}
	
	/**
	 * Converts user answer to string, for saving to the database and such
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function toString() 
	{
		return implode(',', $this->choices);
	}
	
	/**
	 * Returns all user choices
	 * @access public
	 * @author Corina Udrescu (xe_dev@arnia.ro)
	 * @return array
	 */
	function getChoices() 
	{
		return $this->choices;
	}
}
/* End of file quiz.item.php */
/* Location: quiz.item.php */
