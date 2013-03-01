<?php

/*
  DOKEOS - elearning and course management software

  For a full list of contributors, see documentation/credits.html

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.
  See "documentation/licence.html" more details.

  Contact:
  Dokeos
  Rue des Palais 44 Paleizenstraat
  B-1030 Brussels - Belgium
  Tel. +32 (2) 211 34 56
 */


/**
 * 	File containing the Matching class.
 * 	@package dokeos.exercise
 * 	@author Eric Marguin
 * 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
 */
if (!class_exists('Matching')):

 /**
   CLASS Matching
  *
  * 	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
  * 	extending the class question
  *
  * 	@author Eric Marguin
  * 	@package dokeos.exercise
  * */
 class Matching extends Question {

  static $typePicture = 'matching.gif';
  static $explanationLangVar = 'Matching';

  /**
   * Constructor
   */
  function Matching() {
   parent::question();
   $this->type = MATCHING;
  }

  /**
   * function which redifines Question::createAnswersForm
   * @param the formvalidator instance
   */
  function createAnswersForm($form) {
   global $charset;
   $defaults = array();
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
	  $formsize_px = "100px";
	}
	else
	{
	   $formsize_px = "150px";
	}

   $nb_matches = 3;
   $nb_options = 3;
   if ($form->isSubmitted()) {
    $nb_matches = $form->getSubmitValue('nb_matches');
    $nb_options = $form->getSubmitValue('nb_options');
    if (isset($_POST['lessMatches']))
     $nb_matches--;
    if (isset($_POST['moreMatches']))
     $nb_matches++;
    if (isset($_POST['lessOptions']))
     $nb_options--;
    if (isset($_POST['moreOptions']))
     $nb_options++;

    if (!empty($this->id)) {
     $answer = new Answer($this->id);
     $answer->read();
     for ($i = 1; $i <= $nb_options; $i++) {
      if (($i == $nb_options)) {
       $defaults['option[' . $i . ']'] = '';
      } else {
       $defaults['option[' . $i . ']'] = $answer->selectAnswer($i);
       $defaults['comment[' . $i . ']'] = $answer->comment[$i];
      }
     }
    }
   } else if (!empty($this->id)) {
    $answer = new Answer($this->id);
    $answer->read();
    if (count($answer->nbrAnswers) > 0) {
     $a_matches = $a_options = array();
     $nb_matches = $nb_options = 0;
     for ($i = 1; $i <= $answer->nbrAnswers; $i++) {
      if ($answer->isCorrect($i)) {
       $nb_matches++;
       $defaults['answer[' . $nb_matches . ']'] = $answer->selectAnswer($i);
       $defaults['weighting[' . $nb_matches . ']'] = float_format($answer->selectWeighting($i), 1);
       $defaults['matches[' . $nb_matches . ']'] = $answer->correct[$i];
      } else {
       $nb_options++;
       $defaults['option[' . $nb_options . ']'] = $answer->selectAnswer($i);
       $defaults['comment[' . $nb_options . ']'] = $answer->comment[$i];
      }
     }
    }
   } /* else {
     $defaults['answer[1]'] = get_lang('DefaultMakeCorrespond1');
     $defaults['answer[2]'] = get_lang('DefaultMakeCorrespond2');
     $defaults['matches[2]'] = '2';
     $defaults['option[1]'] = get_lang('DefaultMatchingOptA');
     $defaults['option[2]'] = get_lang('DefaultMatchingOptB');
     } */
   $a_matches = array();
   for ($i = 1; $i <= $nb_options; ++$i) {
    $a_matches[$i] = chr(64 + $i) . ' ' . get_lang('MatchesTo') . ' ';  // fill the array with A, B, C.....
   }

   $form->addElement('hidden', 'nb_matches', $nb_matches);
   $form->addElement('hidden', 'nb_options', $nb_options);
   $form->addElement('hidden', 'submitform');
   $form->addElement('hidden', 'questiontype','6');
   $form->addElement('hidden', 'formsize');

   ////////////////////////
   // DISPLAY MATCHES ////
   //////////////////////

 /*   echo '<script>
	function updateBlanks()
	  {		 
		  var nb_matches = document.question_admin_form.nb_matches.value;			  
		  for(var i=1;i<=nb_matches;i++)
		  {	
			  var oEditor = FCKeditorAPI.GetInstance("answer["+i+"]");
				var answer =  oEditor.GetXHTML( true ) ;
				
				if(answer.length > 75)
				{				
				oEditor.EditorDocument.body.disabled = true;
				}
		  }
	  }
   </script>';*/
   
 //  $form->addElement('html', '<div style="float:right;padding-right:25px;"><img src="../img/SmallFormFilled.png" alt="" onclick="lowlineform()" />&nbsp;<img src="../img/BigFormClosed.png" alt="" onclick="highlineform()" /></div>');
   $leftset_lang_var = api_convert_encoding(get_lang('LeftSet'), $charset, api_get_system_encoding());
   $matchesto_lang_var = api_convert_encoding(get_lang('MatchesTo'), $charset, api_get_system_encoding());
   $rightset_lang_var = api_convert_encoding(get_lang('RightSet'), $charset, api_get_system_encoding());
   $html = '<table width="100%" class="data_table">
					<tr style="text-align: center" class="row_odd">
						<th width="5%" >&nbsp;</hd>
						<th width="35%" >' . $leftset_lang_var . '</th>
						<th width="20%" >' . $matchesto_lang_var . '</th>
						<th width="35%" >' . $rightset_lang_var . '</th>			
						<th width="5%" >&nbsp;</hd>
					</tr></table><table width="100%" class="data_table">';

   // Main container
   $form->addElement('html', '<div style="float:left;width:100%">');

   $form->addElement('html', $html);
   $form->addElement('html', '<tr class="row_odd"><td valign="top">&nbsp');
   // Match to column
/*   $form->addElement('html', '<div>');
   $form->addElement('html', '<table width="100%" border="0">');
   for ($i = 1; $i <= $nb_matches; ++$i) {
    $group = array();
    $form->addElement('html', '<tr><td id="nos['.$i.']" valign="top" style="height:103px;">');
    $puce = FormValidator :: createElement('text', null, null, 'value="' . $i . '."');
    $puce->freeze();
    $group[] = $puce;
    $form->addGroup($group, null, null, '</td>');
    $form->addElement('html', '</tr>');
   }
   $form->addElement('html', '</table>');

   $form->addElement('html', '</div>');*/
   $form->addElement('html', '</td><td valign="top">');
   $form->addElement('html', '<div><table width="100%">');
   $tmp_matches = array();
   $tmp_matches = $a_matches;
   for ($i = 1; $i <= $nb_matches; ++$i) {
    $form->addElement('html', '<tr><td valign="top" style="padding-top:10px;">'.$i.'</td><td style="height:43px;">');
	$form->add_html_editor('answer[' . $i . ']','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '350px', 'Height' => ''.$formsize_px.''));
 /*   $group = array();
    $group[] = FormValidator :: createElement ('html_editor', 'answer['.$i.']',null, 'style="margin:0em;"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '350px', 'Height' => ''.$formsize_px.'')); 
    $form->addGroup($group, null, null, '</td>');*/

//	$form->addElement('html', '</td></tr>');
    $form->addElement('html', '</td>');
	$group = array();
    $form->addElement('html', '<td valign="top" style="padding-top:5px;">');
    for ($k = 1; $k <= $nb_options; $k++) {
     $tmp_matches[$k] = $tmp_matches[$k] . $i;
	// $match_lang_var[] = api_convert_encoding($tmp_matches[$k], $charset, api_get_system_encoding());
    }
    $group[] = FormValidator :: createElement('select', 'matches[' . $i . ']', null, $tmp_matches, 'id="matches[' . $i . ']""');
    $form->addGroup($group, null, null, '</td>');
    $tmp_matches = array();
    $tmp_matches = $a_matches;
//	$form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');
   }
   $form->addElement('html', '<tr><td>');

   $form->addElement('html', '</td></tr></table></div>');

   /*$group = array();

   if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {
    $group[] = FormValidator :: createElement('submit', 'lessMatches', get_lang('Delete'), 'class="minus"');
    $group[] = FormValidator :: createElement('submit', 'moreMatches', get_lang('Add'), 'class="plus"');
   } else {
    $group[] = FormValidator :: createElement('style_submit_button', 'moreMatches', get_lang('Add'), 'class="plus" style="margin-left:3px;float:right"');
    $group[] = FormValidator :: createElement('style_submit_button', 'lessMatches', get_lang('Delete'), 'class="minus" style="float:right"');
   }

   $form->addGroup($group);*/

   $form->addElement('html', '</td><td valign="top">');
   // Match to column
   $form->addElement('html', '<div>');
/*   $form->addElement('html', '<table width="100%" border="0">');
   $tmp_matches = array();
   $tmp_matches = $a_matches;
 //  $match_lang_var = array();
  
   for ($i = 1; $i <= $nb_matches; ++$i) {
    $group = array();
    $form->addElement('html', '<tr><td valign="top" style="height:103px;">');
    for ($k = 1; $k <= $nb_options; $k++) {
     $tmp_matches[$k] = $tmp_matches[$k] . $i;
	// $match_lang_var[] = api_convert_encoding($tmp_matches[$k], $charset, api_get_system_encoding());
    }
    $group[] = FormValidator :: createElement('select', 'matches[' . $i . ']', null, $tmp_matches, 'id="matches[' . $i . ']""');
    $form->addGroup($group, null, null, '</td>');
    $form->addElement('html', '</tr>');
    $tmp_matches = array();
    $tmp_matches = $a_matches;
   }
   $form->addElement('html', '</table>');*/

   $form->addElement('html', '</div>');

   // End Match to column
   $form->addElement('html', '</td><td valign="top">');

   $form->addElement('html', '<div style="text-align:left"><table width="90%" align="right" border="0">');
   for ($i = 1; $i <= $nb_options; ++$i) {
    $form->addElement('html', '<tr><td>');
 /*   $group = array();
    $group[] = FormValidator :: createElement ('html_editor', 'option['.$i.']',null, 'id="option['.$i.']" style="margin-left: 0em;"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '350px', 'Height' => ''.$formsize_px.'')); 
    $form->addGroup($group, null, null, '</td>');*/
	$form->add_html_editor('option[' . $i . ']','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '350px', 'Height' => ''.$formsize_px.''));
//    $form->addRule('option['.$i.']', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('html', '</td><td valign="top" style="padding-top:10px;">'.chr(64 + $i).'</td></tr>');
   }
   $form->addElement('html', '<tr><td colspan="2">');
   /*$group = array();

   if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {
    $group[] = FormValidator :: createElement('submit', 'lessOptions', get_lang('Delete'), 'class="minus"');
    $group[] = FormValidator :: createElement('submit', 'moreOptions', get_lang('Add'), 'class="plus"');
    // setting the save button here and not in the question class.php
    //	$group[] = FormValidator :: createElement('submit','submitQuestion',$text, 'class="'.$class.'"');
   } else {
    $group[] = FormValidator :: createElement('style_submit_button', 'lessOptions', get_lang('Delete'), 'class="minus"');
    $group[] = FormValidator :: createElement('style_submit_button', 'moreOptions', get_lang('Add'), 'class="plus"');
    // setting the save button here and not in the question class.php
    //	$group[] = FormValidator :: createElement('style_submit_button','submitQuestion',$text, 'class="'.$class.'"');
   }


   $form->addGroup($group);*/
   $form->addElement('html', '</td></tr></table></div></td><td valign="top">');
   /* $form->addElement('html', '<table width="100%" border="0">');
     for ($i = 1; $i <= $nb_matches; ++$i) {
     $group = array();
     $form->addElement('html', '<tr valign="top"><td style="text-align:center;height:43px" >');
     //$group[] = FormValidator :: createElement ('select', 'matches['.$i.']',null,$a_matches,'id="matches['.$i.']"');
     //$form -> addGroup($group, null, null, '</td>');
     /* $puce = FormValidator :: createElement ('text', null,null,'value="'.chr(64+$i).'"');
     $puce->freeze();
     $group[] = $puce; */

   /* $group[] = FormValidator :: createElement('text', 'weighting[' . $i . ']', null, 'style="vertical-align:middle;margin-left: 0em;" size="3"');
     $form->addGroup($group, null, null, '</td>');

     $form->addElement('html', '</tr>');
     }

     $form->addElement('html', '</table></td><td valign="top">'); */
   /*$form->addElement('html', '<table width="100%" border="0">');
   for ($i = 1; $i <= $nb_options; ++$i) {
    $group = array();
    $form->addElement('html', '<tr><td id="alpha['.$i.']" valign="top" style="text-align:center;height:103px"><br/>');
    //$group[] = FormValidator :: createElement ('text', 'weighting['.$i.']',null, 'style="vertical-align:middle;margin-left: 0em;" size="3"');

    $puce = FormValidator :: createElement('text', null, null, 'value="' . chr(64 + $i) . '."');
    $puce->freeze();
    $group[] = $puce;
    $form->addGroup($group, null, null, '</td>');

    $form->addElement('html', '</tr>');
   }
   $form->addElement('html', '</table>');*/
   $form->addElement('html', '</td></tr><tr>');

   $form->addElement('html', '<td>&nbsp;</td>');
   $form->addElement('html', '<td style="padding-left:250px;">');

   $group = array();
   if ($navigator_info['name'] == 'Internet Explorer' && ($navigator_info['version'] >= '6')) {	
    $group[] = FormValidator :: createElement('submit', 'lessMatches', '', 'class="button_less"');
    $group[] = FormValidator :: createElement('submit', 'moreMatches', '', 'class="button_more"');
   } else {
 //   $group[] = FormValidator :: createElement('style_submit_button', 'moreMatches', get_lang('Add'), 'class="plus" style="margin-left:3px;float:right"');
 //   $group[] = FormValidator :: createElement('style_submit_button', 'lessMatches', get_lang('Delete'), 'class="minus" style="float:right"');
	  $group[] = FormValidator :: createElement('submit', 'lessMatches', '', 'class="button_less"');
      $group[] = FormValidator :: createElement('submit', 'moreMatches', '', 'class="button_more"');
   	   }
   $form->addGroup($group);
   $form->addElement('html', '</td>');

   $form->addElement('html', '<td>&nbsp;</td>');
   $form->addElement('html', '<td style="padding-left:250px;">');

   $group = array();

   if ($navigator_info['name'] == 'Internet Explorer' && ($navigator_info['version'] >= '6')) {	
    $group[] = FormValidator :: createElement('submit', 'lessOptions', '', 'class="button_less"');
    $group[] = FormValidator :: createElement('submit', 'moreOptions', '', 'class="button_more"');
   } else {
 //   $group[] = FormValidator :: createElement('style_submit_button', 'lessOptions', get_lang('Delete'), 'class="minus"');
 //   $group[] = FormValidator :: createElement('style_submit_button', 'moreOptions', get_lang('Add'), 'class="plus"');
   	$group[] = FormValidator :: createElement('submit', 'lessOptions', '', 'class="button_less"');
    $group[] = FormValidator :: createElement('submit', 'moreOptions', '', 'class="button_more"');
   }
   $form->addGroup($group);
   $form->addElement('html', '</td>');

   $form->addElement('html', '<td>&nbsp;</td>');

   $form->addElement('html', '</tr></table><br/>');
   /*
     $form -> addElement ('html', '<table width="100%"><tr><td><div style="width:100%;"><table width="100%" border="0"><tr><td width="10%" align="center"><font size="2">if True</font>');
     $form -> addElement ('html', '</td><td>');
     $form->addElement('html_editor', 'comment[1]',null,'style="vertical-align:middle"',array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '90%', 'Height' => '170'));
     $form->addElement('textarea', 'comment[1]',null,'style="vertical-align:middle" cols="50" rows="1"');
     $form -> addElement ('html', '</td></tr>');
     $form -> addElement ('html', '<tr><td align="center"><font size="2">if False</font>');
     $form -> addElement ('html', '</td><td>');
     $form->addElement('html_editor', 'comment[2]',null,'style="vertical-align:middle"',array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '90%', 'Height' => '170'));
     $form->addElement('textarea', 'comment[2]',null,'style="vertical-align:middle" cols="50" rows="1"');
     $form -> addElement ('html', '</td></tr>');
     $form -> addElement ('html', '</table></div>');
     $form -> addElement ('html', '</td></tr></table>'); */

   //$form->addElement('html', '<br />');

   $group = array();
   // global $text, $class;

   if ($navigator_info['name'] == 'Internet Explorer' && ($navigator_info['version'] == '6' || $navigator_info['version'] == '7')) {
    // setting the save button here and not in the question class.php
    $group[] = FormValidator :: createElement('style_submit_button', 'submitQuestion', get_lang('Validate'), 'class="save"');
   } else {
    // setting the save button here and not in the question class.php
    $group[] = FormValidator :: createElement('style_submit_button', 'submitQuestion', get_lang('Validate'), 'class="save"');
   }
   //$form -> addGroup($group);
   // End main container
   $form->addElement('html', '</div>');

   // Feedback container
   $form->addElement('html', '<table width="100%"><tr><td>');
   $form->addElement('html', '<div style="float:left;width:50%;">' . get_lang('FeedbackIfTrue'));
//   $form->addElement('textarea', 'comment[1]', null, 'id="comment[1]" style="vertical-align:middle" cols="50" rows="1"');
	$form->add_html_editor('comment[1]','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '400px', 'Height' => ''.$formsize_px.''));
    $form->addElement('html', '</div></td><td>');

   $form->addElement('html', '<div style="float:right;text-align:right">');
   $form->addElement('html', '<div style="float:left;text-align:left">' . get_lang('FeedbackIfFalse'));
//   $form->addElement('textarea', 'comment[2]', null, 'id="comment[2]" style="vertical-align:middle" cols="50" rows="1"');
	$form->add_html_editor('comment[2]','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '400px', 'Height' => ''.$formsize_px.''));
   $form->addElement('html', '</div></td></tr></table>');
   $form->addElement('html', '<div style="float:right;text-align:left">');
   $form->addGroup($group);
   $form->addElement('html', '</div>');

   $form->setDefaults($defaults);
   $form->setConstants(array('nb_matches' => $nb_matches, 'nb_options' => $nb_options));
  }

  /**
   * abstract function which creates the form to create / edit the answers of the question
   * @param the formvalidator instance
   */
  function processAnswersCreation($form) {

   $nb_matches = $form->getSubmitValue('nb_matches');
   $nb_options = $form->getSubmitValue('nb_options');
   $this->weighting = 0;
   $objAnswer = new Answer($this->id);

   $position = 0;

   // Score for the correct answers
   $answer_score = $form->getSubmitValue('scoreQuestions');

   // insert the options
   for ($i = 1; $i <= $nb_options; ++$i) {
    $position++;
    $option = $form->getSubmitValue('option[' . $i . ']');
    $comment = $form->getSubmitValue('comment[' . $i . ']');
    $objAnswer->createAnswer($option, 0, $comment, 0, $position);
   }

   $real_score = 0;
   $real_score = ($answer_score/$nb_matches);
   // insert the answers
   for ($i = 1; $i <= $nb_matches; ++$i) {
    $position++;
    $answer = $form->getSubmitValue('answer[' . $i . ']');
    $matches = $form->getSubmitValue('matches[' . $i . ']');
    //$weighting = $form->getSubmitValue('weighting[' . $i . ']');
    $objAnswer->createAnswer($answer, $matches, '', $real_score, $position);
   }

    $this->weighting = $answer_score;

   $objAnswer->save();
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
   include(api_get_path(SYS_CODE_PATH) . 'exercice/tracking/questions_templates/matching.page');
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