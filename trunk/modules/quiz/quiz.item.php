<?php
/**
 * @class QuizInfo 
 * @developer Corina Udrescu (xe_dev@arnia.ro)
 * @brief Stores info specific for a quiz module instance
 */
class QuizInfo
{
	var $start_date;
	var $end_date;
	var $use_question_activation_date;
	var $use_timing;

	/**
	 * @brief Class constructor
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return 
	 * @param $start_date string
	 * @param $end_date string
	 * @param $use_question_activation_date char
	 * @param $use_timing char
	 */
	function QuizInfo($start_date = NULL, $end_date = NULL, $use_question_activation_date = NULL, $use_timing = NULL) 
	{
		$this->start_date = $start_date;
		$this->end_date = $end_date;
		$this->use_question_activation_date = $use_question_activation_date == 'Y' ? TRUE : FALSE;
		$this->use_timing = $use_timing == 'Y' ? TRUE : FALSE;
	}
	
	/**
	 * @brief Checks if quiz is still active (current day is between start_date and end_date)
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
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
	 * @brief Checks if current quiz is configured to display all questions at once
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean
	 */
	function showQuestionsAllAtOnce() 
	{
		return !$this->use_question_activation_date;
	}
	
	/**
	 * @brief Checks if current quiz is configured to display questinos one at a time, at certain dates
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean
	 */
	function showQuestionsOneAtATime() 
	{
		return $this->use_question_activation_date;
	}
	
	/**
	 * @brief Checks if quiz is configured to time how long it took users to answer
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean
	 */
	function timeQuestions() 
	{
		return $this->use_timing;
	}
}
/**
 * @class Question
 * @developer Corina Udrescu (xe_dev@arnia.ro)
 * @brief Base class for questions
 */
class Question
{
	var $question;

	/**
	 * @brief Constructor
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return 
	 * @param $question stdClass
	 */
	function Question($question) 
	{
		$this->question = $question;
	}
	
	/**
	 * @brief Returns question srl
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return int
	 */
	function getQuestionSrl() 
	{
		return $this->question->question_srl;
	}
	
	/**
	 * @brief Returns list order
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return int 
	 */
	function getListOrder() 
	{
		return $this->question->list_order;
	}
	
	/**
	 * @brief Returns title
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string 
	 */
	function getTitle() 
	{
		return $this->question->title;
	}
	
	/**
	 * @brief Returns hint
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getHint() 
	{
		return $this->question->hint;
	}
	
	/**
	 * @brief Returns activation date - date when question becomes visible
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getActivationDate() 
	{
		return $this->question->activation_date;
	}
	
	/**
	 * @brief Returns description
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getDescription() 
	{
		return $this->question->description;
	}
	
	/**
	 * @brief Return weight - how much points a question is worth
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return weight
	 */
	function getWeight() 
	{
		return $this->question->weight;
	}
	
	/**
	 * @brief Returns score
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
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
		
		function isMultipleChoice()
		{
			return false;
		}
	}
	
	/**
	 * @brief Returns true if question is multiple choice, false otherwise
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean 
	 */
	function isMultipleChoice() 
	{
		return FALSE;
	}
}
/**
 * @class OpenQuestion
 * @developer Corina Udrescu (xe_dev@arnia.ro)
 * @brief Models an open question
 */
class OpenQuestion extends Question
{
	
	/**
	 * @brief Constructor
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return
	 * @param $question stdClass
	 */
	function OpenQuestion($question) 
	{
		parent::Question($question);
	}
	
	/**
	 * @brief Checks if given user answer is correct
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
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
	 * @brief Overrides base method
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean 
	 */
	function isMultipleChoice() 
	{
		return FALSE;
	}
	
	/**
	 * @brief Returns the correct answer for this question
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string 
	 */
	function getAnswer() 
	{
		return $this->question->answer;
	}
}
/**
 * @class MultipleChoiceQuestion
 * @developer Corina Udrescu (xe_dev@arnia.ro)
 * @brief Models a multiple choice question
 */
class MultipleChoiceQuestion extends Question
{
	
	/**
	 * @brief Constructor
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return
	 * @param $question stdClass
	 */
	function MultipleChoiceQuestion($question) 
	{
		parent::Question($question);
	}
	
	/**
	 * @brief Returns the choices for this question (possible answers)
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return array()
	 */
	function getAnswers() 
	{
		return $this->question->answers;
	}
	
	/**
	 * @brief Checks if user answer is correct
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
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
	 * @brief Overrides base method
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return boolean 
	 */
	function isMultipleChoice() 
	{
		return TRUE;
	}
}
/**
 * @class UserAnswer
 * @developer Corina Udrescu (xe_dev@arnia.ro)
 * @brief Models a user answer 
 */
class UserAnswer
{
	var $question_srl;

	/**
	 * @brief Constructor
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return
	 *
	 * @param $question_srl int
	 */
	function UserAnswer($question_srl) 
	{
		$this->question_srl = $question_srl;
	}
	
	/**
	 * @brief Returns question sel
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return int 
	 */
	function getQuestionSrl() 
	{
		return $this->question_srl;
	}
}

/**
 * @class UserAnswerForOpenQuestion
 * @developer CorinaUdrescu (xe_dev@arnia.ro)
 * @brief Models a user answer for an open question
 */
class UserAnswerForOpenQuestion extends UserAnswer
{
	var $answer;

	/**
	 * @brief Constructor
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return
	 * @param $question_srl int
	 */
	function UserAnswerForOpenQuestion($question_srl) 
	{
		parent::UserAnswer($question_srl);
	}
	
	/**
	 * @brief Set user answer value
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return
	 * @param $user_value string
	 */
	function setValue($user_value) 
	{
		$this->answer = $user_value;
	}
	
	/**
	 * @brief Returns user sumbitted answer
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function getValue() 
	{
		return $this->answer;
	}
	
	/**
	 * @brief Converts answer to string - for saving to database and such
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function toString() 
	{
		return $this->answer;
	}
}
/**
 * @class UserAnswerForMultipleChoiceQuestion 
 * @developer Corina Udrescu (xe_dev@arnia.ro)
 * @brief Models a user's answer to a multiple choice question
 */
class UserAnswerForMultipleChoiceQuestion extends UserAnswer
{
	var $choices; // List of user selected values
	
	/**
	 * @brief Constructor
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return
	 * @param $question_srl int
	 */
	function UserAnswerForMultipleChoiceQuestion($question_srl) 
	{
		parent::UserAnswer($question_srl);
		$this->choices = array();
	}
	
	/**
	 * @brief Add a user selected choice
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return
	 *
	 * @param $user_value int - answer_srl
	 */
	function addChoice($user_value) 
	{
		$this->choices[] = $user_value;
	}
	
	/**
	 * @brief Converts user answer to string, for saving to the database and such
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return string
	 */
	function toString() 
	{
		return implode(',', $this->choices);
	}
	
	/**
	 * @brief Returns all user choices
	 * @access public
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @return array
	 */
	function getChoices() 
	{
		return $this->choices;
	}
}
/* End of file quiz.item.php */
/* Location: quiz.item.php */
