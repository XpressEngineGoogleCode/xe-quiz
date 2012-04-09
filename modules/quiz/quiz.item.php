<?php

	class QuizInfo
	{
		var $start_date;
		var $end_date;
		var $use_question_activation_date;
		var $use_timing;
		
		function QuizInfo($start_date = null, $end_date = null, $use_question_activation_date = null, $use_timing = null)
		{
			$this->start_date = $start_date;
			$this->end_date = $end_date;
			$this->use_question_activation_date = $use_question_activation_date == 'Y' ? true : false;
			$this->use_timing = $use_timing == 'Y' ? true : false;
		}
				
		function isActive()
		{
		    if($this->start_date && $this->end_date){
	    		$start = strtotime($this->start_date);
	    		$end = strtotime($this->end_date);
	    		$now = strtotime(date('Ymd'));
	    		if(($now - $start >= 0) && ($end - $now >= 0)){
	    			return true;
	    		}
	    		return false;
    		}
    		return true;			
		}
		
		function showQuestionsAllAtOnce()
		{
			return !$this->use_question_activation_date;
		}
		
		function showQuestionsOneAtATime()
		{
			return $this->use_question_activation_date;
		}
		
		function timeQuestions()
		{
			return $this->use_timing;
		}
	}

	class Question 
	{
		var $question;
		
		function Question($question)
		{
			$this->question = $question;
		}
		
		function getQuestionSrl()
		{
			return $this->question->question_srl;
		}
				
		function getListOrder()
		{
			return $this->question->list_order;
		}
		
		function getTitle()
		{
			return $this->question->title;
		}
		
		function getHint()
		{
			return $this->question->hint;
		}
		
		function getActivationDate()
		{
			return $this->question->activation_date;
		}
		
		function getDescription()
		{
			return $this->question->description;
		}
		
		function getWeight()
		{
			return $this->question->weight;
		}
		
		function getScore($user_answer, $quiz)
		{
			$is_correct = $this->checkAnswer($user_answer);
			if(!$is_correct) return 0;
			 
    		//Only give points if quiz is active (and start/end date are defined)
    		if($quiz->isActive())
    			return $this->question->weight;
    		else
    			return 0;				
		}
	}

	class OpenQuestion extends Question
	{
		function OpenQuestion($question)
		{
			parent::Question($question);
		}
		
		function checkAnswer($user_answer)
		{
			$answer = $user_answer->getValue();
			if(strcasecmp(trim($this->question->answer), trim($answer)) == 0){
				return true;
			}
			return false;			
		}		
		
		function isMultipleChoice()
		{
			return false;
		}
		
		function getAnswer()
		{
			return $this->question->answer;
		}
	}

	class MultipleChoiceQuestion extends Question
	{
		function MultipleAnswerQuestion($question)
		{
			parent::Question($question);
		}

		function getAnswers()
		{
			return $this->question->answers;
		}
		
		/**
		 * Check if user answer is correct; 
		 * @param type $user_answer
		 * @return boolean 
		 */
		function checkAnswer($user_answer)
		{
			$user_choices = $user_answer->getChoices();
			$answers = $this->question->answers;
			
			foreach($answers as $answer)
			{
				if($answer->is_correct == "N" && in_array($answer->answer_srl, $user_choices)) return false;
				if($answer->is_correct == "Y" && !in_array($answer->answer_srl, $user_choices)) return false;
			}
			
			return true;
		}		
		
		function isMultipleChoice()
		{
			return true;
		}
	}
	
	class UserAnswer 
	{
		var $question_srl;
		
		function UserAnswer($question_srl)
		{
			$this->question_srl = $question_srl;
		}
		
		function getQuestionSrl()
		{
			return $this->question_srl;
		}
	}
	
	class UserAnswerForOpenQuestion extends UserAnswer
	{
		var $answer;
		
		function UserAnswerForOpenQuestion($question_srl)
		{
			parent::UserAnswer($question_srl);
		}
		
		function setValue($user_value)
		{
			$this->answer = $user_value;
		}
		
		function getValue()
		{
			return $this->answer;
		}
		
		function toString()
		{
			return $this->answer;
		}
	}
	
	class UserAnswerForMultipleChoiceQuestion extends UserAnswer
	{
		var $choices; // List of user selected values
		
		function UserAnswerForMultipleChoiceQuestion($question_srl)
		{
			parent::UserAnswer($question_srl);
			$this->choices = array();
		}
		
		function addChoice($user_value)
		{
			$this->choices[] = $user_value;
		}
		
		function toString()
		{
			return implode(',' , $this->choices);
		}
		
		function getChoices()
		{
			return $this->choices;
		}
	}

	
	
?>