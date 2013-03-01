<?php

// $Id: document.php 16494 2008-10-10 22:07:36Z yannoo $

/*
  ==============================================================================
  Dokeos - elearning and course management software

  Copyright (c) 2004-2008 Dokeos SPRL
  Copyright (c) 2003 Ghent University (UGent)
  Copyright (c) 2001 Universite catholique de Louvain (UCL)
  Copyright (c) various contributors

  For a full list of contributors, see "credits.txt".
  The full license can be read in "license.txt".

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  See the GNU General Public License for more details.

  Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
  Mail: info@dokeos.com
  ==============================================================================
 */

/**
 * 	File containing the MultipleAnswer class.
 * 	@package dokeos.exercise
 * 	@author Eric Marguin
 * 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
 */
if (!class_exists('Reasoning')):

 /**
   CLASS MultipleAnswer
  *
  * 	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
  * 	extending the class question
  *
  * 	@author Eric Marguin
  * 	@package dokeos.exercise
  * */
 class Reasoning extends Question {

  static $typePicture = 'mcma.gif';
  static $explanationLangVar = 'Reasoning';

  /**
   * Constructor
   */
  function Reasoning() {
   parent::question();
   $this->type = REASONING;
  }

  /**
   * function which redifines Question::createAnswersForm
   * @param the formvalidator instance
   * @param the answers number to display
   */
  function createAnswersForm($form) {
   global $charset;
   $nb_answers = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 4;
   $nb_answers += ( isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));
   $navigator_info = api_get_navigator();  

   if(isset($_POST['formsize']))
   {
	  $formsize = $_POST['formsize'];
   }
   else
   {
	  $formsize = '';
   }

   if(empty($formsize) || $formsize == 'Low')
   {
	  $formsize_px = "40px";
   }
   else
   {
	  $formsize_px = "150px";
   }

   $answer_lang_var = api_convert_encoding(get_lang('Answer'), $charset, api_get_system_encoding());
   $form->addElement('html', '<div style="float:right;padding-right:25px;"><img style="cursor: pointer;" src="../img/SmallFormFilled.png" alt="" onclick="lowlineform()" />&nbsp;<img style="cursor: pointer;" src="../img/BigFormClosed.png" alt="" onclick="highlineform()" /></div>');

   $html = '
		<div class="row">			
			<div>
				<table class="data_table" style="width:100%;">
					<tr>
						<th style="text-align: center;">'.get_lang('True').'</th>
						<th  style="text-align: center;">
							' . $answer_lang_var . '
						</th>
					</tr>';
   $form->addElement('html', '<div id="leftcontainer" class="quiz_answer_small_squarebox">');
   $form->addElement('html', $html);

   $defaults = array();
   $correct = 0;
   if (!empty($this->id)) {
    $answer = new Answer($this->id);
    $answer->read();
    if (count($answer->nbrAnswers) > 0 && !$form->isSubmitted()) {
     $nb_answers = $answer->nbrAnswers;
    }
    $question = question::read($this->id);
    $question_weight = $question->weighting;
   }

   $form->addElement('hidden', 'nb_answers');
   $form->addElement('hidden', 'submitform');
   $form->addElement('hidden', 'questiontype','3');
   $form->addElement('hidden', 'formsize');
   $boxes_names = array();

   $count_if_true = 0;
   $count_if_false = 0;
   for ($i = 1; $i <= $nb_answers; $i++) {
     $goodAnswer = trim($answer->correct[$i]);
     if ($goodAnswer && $count_if_true==0 ) {
       $defaults['comment[1]'] = $answer -> comment[$i];
       $count_if_true ++;
     } elseif(!$goodAnswer && $count_if_false==0 ) {
       $defaults['comment[2]'] = $answer -> comment[$i];
       $count_if_false ++;
     }
     if ($count_if_true == 1 && $count_if_false==1) {
       break;
     }
   }
   
   for ($i = 1; $i <= $nb_answers; ++$i) {
    $class = ($i%2 == 0) ? 'row_odd' : 'row_even';
    $form->addElement('html', '<tr class="'.$class.'">');
    if (is_object($answer)) {
     $defaults['answer[' . $i . ']'] = $answer->answer[$i];
     //$defaults['comment[' . $i . ']'] = $answer->comment[$i];
     $defaults['weighting[' . $i . ']'] = float_format($answer->weighting[$i], 1);
     $defaults['correct[' . $i . ']'] = $answer->correct[$i];
    } /*else {
       $defaults['answer[1]']  = get_lang('langDefaultMultipleAnswer2');
       $defaults['comment[1]'] = get_lang('langDefaultMultipleComment2');
       $defaults['correct[1]'] = true;
       $defaults['weighting[1]'] = 10;

       $defaults['answer[2]']  = get_lang('langDefaultMultipleAnswer1');
       $defaults['comment[2]'] = get_lang('langDefaultMultipleComment1');
       $defaults['correct[2]'] = false;
       $defaults['weighting[2]'] = 0; 
    }*/
    $renderer = & $form->defaultRenderer();
    $renderer->setElementTemplate('<!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><td align="center"><br/>{element}</td>');

    $form->addElement('checkbox', 'correct[' . $i . ']', null, null, 'class="checkbox" style="margin-left: 0em;"');
    $boxes_names[] = 'correct[' . $i . ']';

    //$form->addElement('html_editor', 'answer['.$i.']',null, 'style="vertical-align:middle"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '90%', 'Height' => '65'));
 //   $form->addElement('textarea', 'answer[' . $i . ']', null, 'id="answer['.$i.']" cols="55" rows="1"');
	$form->add_html_editor('answer[' . $i . ']','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => ''.$formsize_px.''));
    $form->addRule('answer[' . $i . ']', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('html', '</tr>');
   }
   $form->addElement('html', '</table>');
   $form->addElement('html', '<br />');
   $form->addElement('html', '</div>');


   $form->addElement('html', '<table width="100%"><tr><td width="100%">');   
   global $text, $class;
   //ie6 fix
   if ($navigator_info['name'] == 'Internet Explorer' && ($navigator_info['version'] >= '6')) {
		if($navigator_info['version'] == '6' || $navigator_info['version'] == '7'){	
		$form->addElement('html','<div style="padding-right:60px;float:right;">');
		}
		else {
		$form->addElement('html','<div style="float:right;">');
		}
 //   $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'), 'class="minus"');
 //   $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'), 'class="plus"');
	  $form->addElement('submit', 'lessAnswers', '', 'class="button_less"');
	  $form->addElement('submit', 'moreAnswers', '', 'class="button_more"');	
   } else {
 //   $form->addElement('style_submit_button', 'lessAnswers', get_lang('LessAnswer'), 'class="minus"');
 //   $form->addElement('style_submit_button', 'moreAnswers', get_lang('PlusAnswer'), 'class="plus"');
	  $form->addElement('html','<div align="right">');
      $form->addElement('submit', 'lessAnswers', '', Display::return_icon('pixel.gif','',array('class'=>'button_less')));
   	  //$form->addElement('submit', 'lessAnswers', '', 'style="background:url(\'../img/form-minus.png\') no-repeat;width:35px;height:40px;border:0px;"');
	  //$form->addElement('submit', 'moreAnswers', '', 'style="background:url(\'../img/form-plus.png\') no-repeat;width:35px;height:40px;border:0px;"');	
      $form->addElement('submit', 'moreAnswers', '', Display::return_icon('pixel.gif','',array('class'=>'button_more')));
   }

   $form->addElement('html', '</div></td></tr></table><br/>');


   $form->addElement('html', '</div></div>');
   $form->add_multiple_required_rule($boxes_names, get_lang('ChooseAtLeastOneCheckbox'), 'multiple_required');

// $form -> addElement ('html', '<table width="100%"><tr><td width="15%"><font size="2">Feedback if True</font>');
// $form -> addElement ('html', '</td><td>');
// $form->addElement('html_editor', 'comment[1]',null,'style="vertical-align:left"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '90%', 'Height' => '65'));
   $form->addElement('html', '<div id="feedback_container" style="float:left;width:100%">');
// $form->addElement('html', '<br />');
   $form->addElement('html', '<div style="float:left; width:50%;">' . get_lang('FeedbackIfTrue'));
// $form->addElement('textarea', 'comment[1]', null, 'id="comment[1]" cols="55" rows="1"');
   $form->add_html_editor('comment[1]','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '400px', 'Height' => ''.$formsize_px.''));
// $form -> addElement ('html', '</td><td><font size="2">Score:</font></td><td valign="top">');
   $form->addElement('html', '</div>');

   // Score
   /*$form->addElement('html', '<div style="float:left;margin-left:2px;">' . get_lang('Score'));
   $form->addElement('text', 'weighting[1]', null, array('size' => '3'));
   $form->addElement('html', '</div>');*/


// $form -> addElement ('html', '</td></tr><tr><td><font size="2">Feedback if False</font>');
// $form -> addElement ('html', '</td><td>');
   $form->addElement('html', '<div style="float:right;text-align:right">');
   $form->addElement('html', '<div style="float:left;text-align:left">' . get_lang('FeedbackIfFalse'));
// $form->addElement('html_editor', 'comment[2]',null,'style="vertical-align:left"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '90%', 'Height' => '65'));
// $form->addElement('textarea', 'comment[2]', null, 'id="comment[2]" cols="55" rows="1"');
   $form->add_html_editor('comment[2]','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '400px', 'Height' => ''.$formsize_px.''));
// $form -> addElement ('html', '</td><td><font size="2">Score:</font></td><td valign="top">');
// $form->addElement('text', 'weighting[2]',null,array('size'=>'3'));
// $form -> addElement ('html', '</td></tr></table>');
   $form->addElement('html', '</div></div>');
   $form->addElement('html', '<div style="float:right;text-align:left">');

   // Score
   /*$form->addElement('html', '<div style="float:left;margin-left:2px;text-align:left">' . get_lang('Score'));
   $form->addElement('text', 'weighting[2]', null, array('size' => '3'));
   $form->addElement('html', '</div>');*/
   $form->addElement('html', '<br />');
   //ie6 fix
   if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {    
//  $form->addElement('submit', 'submitQuestion', $text, 'class="' . $class . '"');
	$form->addElement('style_submit_button', 'submitQuestion', get_lang('Validate'), 'class="save"');
   } else {
 // $form->addElement('style_submit_button', 'lessAnswers', get_lang('LessAnswer'),'class="minus"');
 // $form->addElement('style_submit_button', 'moreAnswers', get_lang('PlusAnswer'),'class="plus"');
 // setting the save button here and not in the question class.php
    $form->addElement('html', '<br/><br/>');
    $form->addElement('style_submit_button', 'submitQuestion', get_lang('Validate'), 'class="save"');
   }

   $renderer->setElementTemplate('{element}&nbsp;', 'lessAnswers');
   $renderer->setElementTemplate('{element}&nbsp;', 'submitQuestion');
   $renderer->setElementTemplate('{element}', 'moreAnswers');
   $form->addElement('html', '</div></div>');

   $defaults['correct'] = $correct;
   $form->setDefaults($defaults);

   $form->setConstants(array('nb_answers' => $nb_answers));
  }

  /**
   * abstract function which creates the form to create / edit the answers of the question
   * @param the formvalidator instance
   * @param the answers number to display
   */
  function processAnswersCreation($form) {

   $questionWeighting = $nbrGoodAnswers = 0;

   $objAnswer = new Answer($this->id);

   $nb_answers = $form->getSubmitValue('nb_answers');

   // Currently all questions has 2 feedback fields
   $feedback_if_true = $form->getSubmitValue('comment[1]');
   $feedback_if_false = $form->getSubmitValue('comment[2]');

   // Score for the correct answer
   $answer_score = $form->getSubmitValue('scoreQuestions');

   for ($i = 1; $i <= $nb_answers; $i++) {
    $answer = trim($form->getSubmitValue('answer[' . $i . ']'));
    //$comment = trim($form->getSubmitValue('comment[' . $i . ']'));
    //$weighting = trim($form->getSubmitValue('weighting[' . $i . ']'));
    $goodAnswer = trim($form->getSubmitValue('correct[' . $i . ']'));

    /*if ($i == 1 && $weighting > 0) {
     $questionWeighting += $weighting;
    }*/

    if ($i == 1 && $answer_score > 0) {
     $questionWeighting += $answer_score;
    }

    if ($goodAnswer) {
      $comment = $feedback_if_true;
    } else {
      $comment = $feedback_if_false;
    }

    $objAnswer->createAnswer($answer, $goodAnswer, $comment, $weighting, $i);
   }

   // saves the answers into the data base
   $objAnswer->save();

   // sets the total weighting of the question
   $this->updateWeighting($questionWeighting);
   $this->save();
  }

  /**
   * Display the question in tracking mode (use templates in tracking/questions_templates)
   * @param $nbAttemptsInExercise the number of users who answered the quiz
   */
  function displayTracking($exerciseId, $nbAttemptsInExercise) {

   if (!class_exists('Answer'))
    require_once(api_get_path(SYS_CODE_PATH) . 'exercice/answer.class.php');

   $stats = $this->getAverageStats($exerciseId, $nbAttemptsInExercise);
   include(api_get_path(SYS_CODE_PATH) . 'exercice/tracking/questions_templates/reasoning.page');
  }

  /**
   * Returns learners choices for each question in percents
   * @param $nbAttemptsInExercise the number of users who answered the quiz
   * @return array the percents
   */
  function getAverageStats($exerciseId, $nbAttemptsInExercise) {

   $preparedSql = 'SELECT COUNT(1) as nbCorrectAttempts
						FROM ' . Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT) . ' as attempts
						INNER JOIN ' . Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES) . ' as exercises
							ON exercises.exe_id = attempts.exe_id
						WHERE course_code = "%s"
						AND exercises.exe_exo_id = %d
						AND attempts.question_id = %d
						AND marks = %d
						GROUP BY answer';
   $sql = sprintf($preparedSql, api_get_course_id(), $exerciseId, $this->id, $this->weighting);
   $rs = Database::query($sql, __FILE__, __LINE__);

   $stats['correct'] = array();
   $stats['correct']['total'] = intval(@mysql_result($rs, 0, 'nbCorrectAttempts'));
   $stats['correct']['average'] = $stats['correct']['total'] / $nbAttemptsInExercise * 100;

   $stats['wrong'] = array();
   $stats['wrong']['total'] = $nbAttemptsInExercise - $stats['correct']['total'];
   $stats['wrong']['average'] = 100 - $stats['correct']['average'];


   return $stats;
  }

 }

 endif;
?>
