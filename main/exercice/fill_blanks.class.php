<?php

/*
  ==============================================================================
  Dokeos - elearning and course management software

  Copyright (c) 2004-2008 Dokeos SPRL

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
 * 	File containing the FillBlanks class.
 * 	@package dokeos.exercise
 * 	@author Eric Marguin
 * 	@author Julio Montoya Armas switchable fill in blank option added
 * 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
 */
if (!class_exists('FillBlanks')):

 /**
   CLASS FillBlanks
  *
  * 	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
  * 	extending the class question
  *
  * 	@author Eric Marguin
  * 	@author Julio Montoya multiple fill in blank option added
  * 	@package dokeos.exercise
  * */
 class FillBlanks extends Question {

  static $typePicture = 'fill_in_blanks.gif';
  static $explanationLangVar = 'FillBlanks';

  /**
   * Constructor
   */
  function FillBlanks() {
   parent::question();
   $this->type = FILL_IN_BLANKS;
  }

  /**
   * function which redifines Question::createAnswersForm
   * @param the formvalidator instance
   */
  function createAnswersForm($form) {
   $defaults = array();

   if (!empty($this->id)) {
    $objAnswer = new answer($this->id);
    // Unserialize the feedback(comment) for the fill in blank question type
    $feedback_data = unserialize($objAnswer -> comment[1]);
    // Set the value for each feedback
    $defaults['comment[1]'] = $feedback_data['comment[1]'];
    $defaults['comment[2]'] = $feedback_data['comment[2]'];
  
    // the question is encoded like this
    // [A] B [C] D [E] F::10,10,10@1
    // number 1 before the "@" means that is a switchable fill in blank question
    // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
    // means that is a normal fill blank question

    $pre_array = explode('::', $objAnswer->selectAnswer(1));

    //make sure we only take the last bit to find special marks
    $sz = count($pre_array);
    $is_set_switchable = explode('@', $pre_array[$sz - 1]);
    if ($is_set_switchable[1]) {
     $defaults['multiple_answer'] = 1;
    } else {
     $defaults['multiple_answer'] = 0;
    }

    //take the complete string except after the last '::'
    $defaults['answer'] = '';
    for ($i = 0; $i < ($sz - 1); $i++) {
		$str_answer = str_replace('[','[<u>',$pre_array[$i]);
		$str_final = str_replace(']','</u>]',$str_answer);
		$defaults['answer'] .= $str_final;
    }
    $a_weightings = explode(',', $is_set_switchable[0]);
   }/* else {
     //	$defaults['answer'] = get_lang('DefaultTextInBlanks');
     } */

   // javascript

   echo '
		<script type="text/javascript">
			
		var firstTime = true;
		function Addfillup()
	    {
			var oEditor = FCKeditorAPI.GetInstance(\'answer\');		  
		//  var selection = (oEditor.EditorWindow.getSelection ? oEditor.EditorWindow.getSelection() : oEditor.EditorDocument.selection);	
			var selection = "";
			if(oEditor.EditorDocument.selection != null) {
			  selection = oEditor.EditorDocument.selection.createRange().text;
			}
			else {
			  selection = oEditor.EditorWindow.getSelection();
			}	
			if(selection == "")
				return;
			var new_selection = "[<u>"+selection+"</u>]&nbsp;&nbsp;";
			var final_text = new_selection.replace(" </u>","</u>");			
		    oEditor.InsertHtml(final_text);
			updateBlanks();
		}
		function Removefillup()
	    {
			var oEditor = FCKeditorAPI.GetInstance(\'answer\');		  
		    var selection = (oEditor.EditorWindow.getSelection ? oEditor.EditorWindow.getSelection() : oEditor.EditorDocument.selection);	
			var rm_str = ""+selection;
			var rm_str1 = rm_str.replace("[","");
			var rm_str2 = rm_str1.replace("]","");	
			rm_str = "&nbsp;"+rm_str2;
			oEditor.InsertHtml(rm_str); 
			updateBlanks();
		}
		function updateBlanks()
		{				
		  if (firstTime) {
			 
				var field = document.getElementById("answer");
				var answer = field.value; }
			else {
				var oEditor = FCKeditorAPI.GetInstance(\'answer\');
				var answer =  oEditor.GetXHTML( true ) ;
			}
		
			var blanks = answer.match(/\[[^\]]*\]/g);
			var fields = "<div class=\"row\"><div class=\"label\">' . get_lang('Weighting') . '</div><div class=\"formw\"><table>";
			if(blanks!=null){
				for(i=0 ; i<blanks.length ; i++){
					
					var str = blanks[i].replace("<u>","");
					var blank_str = str.replace("</u>","");

					if(blank_str == "[]")
					{
						return;				
					}
					
					if(document.getElementById("weighting["+i+"]"))
						value = document.getElementById("weighting["+i+"]").value;
					else
						value = "10";
					fields += "<tr><td>"+blank_str+"</td><td><input style=\"margin-left: 0em;\" size=\"5\" value=\""+value+"\" type=\"text\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" /></td></tr>";

				}
			}
			document.getElementById("blanks_weighting").innerHTML = fields + "</table></div></div>";
  
			if(firstTime){
				firstTime = false;
			';

   if (count($a_weightings) > 0) {
    foreach ($a_weightings as $i => $weighting) {
     echo 'document.getElementById("weighting[' . $i . ']").value = "' . $weighting . '";';
    }
   }
   echo '} 
  
		}
		window.onload = updateBlanks;
		</script>
		';
	
	$form->addElement('html', '<div style="float:right;padding-right:25px;"><img style="cursor: pointer;" src="../img/SmallFormFilled.png" alt="" onclick="lowlineform()" />&nbsp;<img style="cursor: pointer;" src="../img/BigFormClosed.png" alt="" onclick="highlineform()" /></div>');

   // Main container
   $form->addElement('html', '<div id="leftcontainer" class="quiz_answer_small_squarebox">');
   // answer
// $form->addElement('html', '<div class="row" ><div class="label"></div><div class="formw">' . get_lang('TypeTextBelow') . ', ' . get_lang('And') . ' ' . get_lang('UseTagForBlank') . '</div></div>');
   $form->addElement('html', '<div align="right"><img src="../img/smallbrackets.png" onclick="Addfillup()" alt="'.get_lang('Addblank').'" title="'.get_lang('Addblank').'"></div>');
// $form -> addElement ('html_editor', 'answer', '<img src="../img/fill_field.png">','id="answer" cols="122" rows="6" onkeyup="javascript: updateBlanks(this);"', array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '250'));
// $form -> addElement ('html_editor', 'answer', get_lang('FillTheBlanks'),'id="answer" cols="122" rows="6" onkeyup="javascript: updateBlanks(this);"', array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '250'));
   $form->addElement('html_editor', 'answer', get_lang('FillTheBlanks'), 'id="answer" cols="122" rows="6" onkeyup="javascript: updateBlanks(this);"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '250'));
   $form->addRule('answer', get_lang('GiveText'), 'required');
   $form->addRule('answer', get_lang('DefineBlanks'), 'regex', '/\[.*\]/');

   //added multiple answers
// $form -> addElement ('checkbox','multiple_answer','', get_lang('FillInBlankSwitchable'));
   $form->addElement('html', '<br />');
   $form->addElement('html', '<div id="blanks_weighting"></div>');
   $form->addElement('html', '</div>');

  // Feedback container
   $form->addElement('html', '<div id="feedback_container" style="float:left;width:100%">');
// $form->addElement('html', '<br /><br />');
   $form->addElement('html', '<div style="float:left;width:50%;">' . get_lang('FeedbackIfTrue'));
// $form->addElement('textarea', 'comment[1]', null, 'id="comment[1]" cols="55" rows="1"');
   $form->add_html_editor('comment[1]','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '400px', 'Height' => '40px'));
   $form->addElement('html', '</div>');

   $form->addElement('html', '<div style="float:right;text-align:right">');
   $form->addElement('html', '<div style="float:left;text-align:left">' . get_lang('FeedbackIfFalse'));
// $form->addElement('textarea', 'comment[2]', null, 'id="comment[2]" cols="55" rows="1"');
   $form->add_html_editor('comment[2]','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '400px', 'Height' => '40px'));
   $form->addElement('html', '</div></div>');
   $form->addElement('html', '<div style="float:right;text-align:left">');
   // setting the save button here and not in the question class.php
   $form->addElement('style_submit_button', 'submitQuestion', get_lang('Validate'), 'class="save" style="float:right"');
   $form->addElement('hidden', 'submitform');
   $form->addElement('hidden', 'questiontype','4');
   $renderer = & $form->defaultRenderer();
   $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>');
   $renderer->setElementTemplate('{element}&nbsp;', 'lessAnswers');
   $renderer->setElementTemplate('{element}&nbsp;', 'submitQuestion');
   $renderer->setElementTemplate('{element}', 'moreAnswers');

   // End feedback container
   $form->addElement('html', '</div></div>');

   $form->setDefaults($defaults);
  }

  /**
   * abstract function which creates the form to create / edit the answers of the question
   * @param the formvalidator instance
   */
  function processAnswersCreation($form) {
   global $charset;

   $answer = $form->getSubmitValue('answer');
   //Due the fckeditor transform the elements to their HTML value
   $answer = api_html_entity_decode($answer, ENT_QUOTES, $charset);

   //remove the :: eventually written by the user
   $answer = str_replace('::', '', $answer);
   $answer = str_replace('<u>', '', $answer);
   $answer = str_replace('</u>', '', $answer);
   // Get the value of feedback fields
   $feedback_if_true = $form->getSubmitValue('comment[1]');
   $feedback_if_false = $form->getSubmitValue('comment[2]');

   $feedback = array('comment[1]' => $feedback_if_true, 'comment[2]' => $feedback_if_false);
   $feedback_comment = serialize($feedback);

   // get the blanks weightings
   $nb = preg_match_all('/\[[^\]]*\]/', $answer, $blanks);
   if (isset($_GET['editQuestion']) || isset($_GET['newQuestion'])) {
    $this->weighting = 0;
   }

   if ($nb > 0) {
    $answer .= '::';
    for ($i = 0; $i < $nb; ++$i) {
     $answer .= $form->getSubmitValue('weighting[' . $i . ']') . ',';
     $this->weighting += $form->getSubmitValue('weighting[' . $i . ']');
    }
    $answer = api_substr($answer, 0, -1);
   }
   $is_multiple = $form->getSubmitValue('multiple_answer');
   $answer.='@' . $is_multiple;

   $this->save();
   $objAnswer = new answer($this->id);
   $objAnswer->createAnswer($answer, 0, $feedback_comment, 0, '');
   $objAnswer->save();
  }

  /**
   * Display the question in tracking mode (use templates in tracking/questions_templates)
   * @param $nbAttemptsInExercise the number of users who answered the quiz
   */
  function displayTracking($exerciseId, $nbAttemptsInExercise) {

   if (!class_exists('Answer'))
    require_once(api_get_path(SYS_CODE_PATH) . 'exercice/answer.class.php');

   $stats = $this->getAverageStats($exerciseId, $nbAttemptsInExercise);
   include(api_get_path(SYS_CODE_PATH) . 'exercice/tracking/questions_templates/fill_in_blanks.page');
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
