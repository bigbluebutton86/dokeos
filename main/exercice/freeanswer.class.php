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
 * 	File containing the FreeAnswer class.
 * 	This class allows to instantiate an object of type FREE_ANSWER,
 * 	extending the class question
 * 	@package dokeos.exercise
 * 	@author Eric Marguin
 * 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
 */
if (!class_exists('FreeAnswer')):

 class FreeAnswer extends Question {

  static $typePicture = 'open_answer.gif';
  static $explanationLangVar = 'freeAnswer';

  /**
   * Constructor
   */
  function FreeAnswer() {
   parent::question();
   $this->type = FREE_ANSWER;
  }

  /**
   * function which redifines Question::createAnswersForm
   * @param the formvalidator instance
   */
  function createAnswersForm($form) {
	 global $charset; 
    $openanswer_lang_var = api_convert_encoding(get_lang('OpenAnswer'), $charset, api_get_system_encoding());

	$form->addElement('html', '<div style="float:right;padding-right:25px;"><img style="cursor: pointer;" src="../img/SmallFormFilled.png" alt="" onclick="lowlineform()" />&nbsp;<img style="cursor: pointer;" src="../img/BigFormClosed.png" alt="" onclick="highlineform()" /></div>');

   // Main container
   $form->addElement('html', '<div id="leftcontainer" class="quiz_answer_small_squarebox">');
// $form->addElement('text', 'weighting', get_lang('Weighting'), 'size="5"');
// $form->addElement('html_editor', 'open_answer', get_lang('OpenAnswer'), 'cols="55" rows="10"');
   $form->addElement('html_editor', 'open_answer',$openanswer_lang_var,'style="vertical-align:middle"',array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '90%', 'Height' => '200'));
// $form->addElement('html', '<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>');
// $form->addElement('html', '<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>');

   if (!empty($this->id)) {
    $answer = new Answer($this->id);
    $answer->read();
   }
	   $open_answer = '<table width="99%" height="145px" cellspacing="2" cellpadding="0" style="font-family:Comic Sans MS;font-size:16px;">
            <tbody>
                <tr valign="top" align="center">
                    <td width="90%" valign="top" style="padding-left:10px;">
                      &nbsp;
                    </td>
                    <td width="9%" valign="bottom" style="padding-left:10px;">
                      <img  src="../img/pen_holder.png"/>
                    </td>
                </tr>
            </tbody>
        </table>';

    if (is_object($answer)) {
     $open_answer = $answer->answer[1];
    }
   // End main container
   //$form -> addElement ('html', '</div>');
   /*
     // Feedback container
     $form -> addElement ('html', '<div style="float:left">'.get_lang('FeedbackIfTrue'));
     $form->addElement('textarea', 'comment[1]',null,'cols="60"');
     $form -> addElement ('html', '</div>');

     $form -> addElement ('html', '<div style="float:right;text-align:right">');

     $form -> addElement ('html', '<div style="float:left;text-align:left">'.get_lang('FeedbackIfFalse'));
     $form->addElement('textarea', 'comment[2]',null,'cols="60"');
     $form -> addElement ('html', '</div>');
    */
   $form->addElement('hidden', 'submitform');
   $form->addElement('hidden', 'questiontype','5');
   $form->addElement('html', '</div>');

   // setting the save button here and not in the question class.php
   $form->addElement('style_submit_button', 'submitQuestion', get_lang('Validate'), 'class="save" style="float:right"');


   if (!empty($this->id)) {
    $form->setDefaults(array('weighting' => float_format($this->weighting, 1)));
    $form->setDefaults(array('open_answer' => $open_answer));
   } else {
    $form->setDefaults(array('weighting' => '10'));
    $form->setDefaults(array('open_answer' => $open_answer));
   }
  }

  /**
   * abstract function which creates the form to create / edit the answers of the question
   * @param the formvalidator instance
   */
  function processAnswersCreation($form) {
   // Add new "open answer" for the "Open question" question type
   $objAnswer = new Answer($this->id);
   // Get the open answer for the question
   $answer = $form->getSubmitValue('open_answer');
   // Score for the answer
   $this->weighting = $form->getSubmitValue('scoreQuestions');
   //$this->weighting = $form->getSubmitValue('weighting');
   $goodAnswer = true;

   $objAnswer->createAnswer($answer, $goodAnswer, '', 0, 1);
   $objAnswer->save();

   $this->save();
  }

  /**
   * Display the question in tracking mode (use templates in tracking/questions_templates)
   * @param $nbAttemptsInExercise the number of users who answered the quiz
   */
  function displayTracking($exerciseId, $nbAttemptsInExercise) {

  }

 }

 endif;
?>