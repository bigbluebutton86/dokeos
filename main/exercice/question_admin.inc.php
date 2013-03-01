<?php

/*
  ==============================================================================
  Dokeos - elearning and course management software

  Copyright (c) 2004-2009 Dokeos SPRL

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
 * 	Statement (?) administration
 * 	This script allows to manage the statements of questions.
 * 	It is included from the script admin.php
 * 	@package dokeos.exercise
 * 	@author Olivier Brouckaert
 * 	@version $Id: question_admin.inc.php 22126 2009-07-15 22:38:39Z juliomontoya $
 */
/*
  ==============================================================================
  INIT SECTION
  ==============================================================================
 */

include_once(api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php');
include_once(api_get_path(LIBRARY_PATH) . 'image.lib.php');


// ALLOWED_TO_INCLUDE is defined in admin.php
if (!defined('ALLOWED_TO_INCLUDE')) {
 exit();
}

/* * *******************
 * INIT QUESTION
 * ******************* */
if (isset($_GET['editQuestion'])) {
 $objQuestion = Question::read($_GET['editQuestion']);
 $action = api_get_self() . "?" . api_get_cidreq() . "&modifyQuestion=" . Security::remove_XSS($_GET['modifyQuestion']) . "&type=".$objQuestion->type."&editQuestion=" . $objQuestion->id . "&exerciseId=" . $exerciseId;
 if ($_SESSION['fromTpl'] == '1') {
  $action .= "&fromTpl=1&startPage=" . Security::remove_XSS($_GET['startPage']) . "&totTpl=" . Security::remove_XSS($_GET['totTpl']);
 }
if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
   $action .= "&lp_id=".Security::remove_XSS($_GET['lp_id']);
 }

 if (isset($exerciseId) && !empty($exerciseId)) {
  $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
  $sql = "SELECT max_score FROM $TBL_LP_ITEM
		WHERE item_type = '" . TOOL_QUIZ . "' AND path ='" . Database::escape_string($exerciseId) . "'";
  $result = api_sql_query($sql);
  if (Database::num_rows($result) > 0) {
   Display::display_warning_message(get_lang('EditingScoreCauseProblemsToExercisesInLP'));
  }
 }
} else {
    
 $objQuestion = Question :: getInstance($_REQUEST['answerType']);
 //	&fromExercise=4&answerType=1
 $exercice_id = Security::remove_XSS($_REQUEST['fromExercise']);
 $action = api_get_self() . "?" . api_get_cidreq() . "&modifyQuestion=" . $modifyQuestion . "&type=".$objQuestion->type."&newQuestion=" . $newQuestion . "&fromExercise=" . $exercice_id;
 if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
   $action .= "&lp_id=".Security::remove_XSS($_GET['lp_id']);
 }
}

if (is_object($objQuestion)) {

 /*  * *******************
  * FORM STYLES
  * ******************* */
 // if you have a better way to improve the display, please inform me e.marguin@elixir-interactive.com
 $styles = '
	<style>
	div.row div.label{
		width: 10%;
	}
	div.row div.formw{
		width: 85%;
	}

	</style>
	';
 echo $styles;


 /*  * *******************
  * INIT FORM
  * ******************* */
 $form = new FormValidator('question_admin_form', 'post', $action);


 /*  * *******************
  * FORM CREATION
  * ******************* */

 if (isset($_GET['editQuestion'])) {
  if ($_SESSION['fromTpl'] == '1') {
   $class = "add";
   $text = get_lang('AddQuestionToExercise');
  } else {
   $class = "save";
   $text = get_lang('ModifyQuestion');
  }
 } else {
  $class = "add";
  $text = get_lang('AddQuestionToExercise');
 }

 $types_information = $objQuestion->get_types_information();
 $form_title_extra = get_lang($types_information[$_REQUEST['answerType']][1]);

 // form title
//	$form->addElement('header', '', $text.': '.$form_title_extra);
 // question form elements
 $objQuestion->createForm($form, array('Height' => 150));

 // answer form elements
 $objQuestion->createAnswersForm($form);

 // submit button is implemented in every question type
 //$form->addElement('style_submit_button','submitQuestion',$text, 'class="'.$class.'"');
 //$renderer = $form->defaultRenderer();
 //$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','submitQuestion');


 /*  * ********************
  * FORM VALIDATION
  * ******************** */

//	if(isset($_POST['submitQuestion']) && $form->validate())
//	if($_SERVER['REQUEST_METHOD'] == "POST" && $form->validate())
 if (($_POST['submitform'] == '1' || isset($_POST['submitQuestion'])) && $form->validate()) {
  $objQuestion->processCreation($form, $objExercise);
  if ($_SESSION['fromTpl'] == '1') {
   $popup = '1';
   $_SESSION['fromTpl'] = '';
   $_SESSION['editQn'] = '0';
  } else {
   if ($_SESSION['editQn'] == '1') {
    $popup = '1';
    $_SESSION['editQn'] = '0';
    $_SESSION['fromTpl'] = '';
   } else {
    $popup = '0';
   }
  }
  $quiz_id = (isset($_REQUEST['fromExercise']) && $_REQUEST['fromExercise'] > 0 ) ? Security::remove_XSS($_REQUEST['fromExercise']) : Security::remove_XSS($_REQUEST['exerciseId']);
  $objQuestion->processAnswersCreation($form, $nb_answers);
  $add_lp_id_parameter = "";
  if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
    $add_lp_id_parameter = '&lp_id='.Security::remove_XSS($_GET['lp_id']);
  }
  if ($objQuestion->type != HOT_SPOT) {
   if (isset($_SESSION['fromlp'])) {
    echo '<script type="text/javascript">window.location.href="admin.php?popup=' . $popup . '&fromlp=Y"</script>';
   } else {
    // Added cidReq and exerciseId
    echo '<script type="text/javascript">parent.location.href="admin.php?popup=' . $popup . '&exerciseId=' . $quiz_id . '&' . api_get_cidreq() .$add_lp_id_parameter. '"</script>';
   }
  } else {
   echo '<script type="text/javascript">window.location.href="admin.php?hotspotadmin=' . $objQuestion->id . '&' . api_get_cidreq() . '&exerciseId=' . $quiz_id .$add_lp_id_parameter. '"</script>';
  }
 } else {
  // question
  //	$objQuestion -> processCreation($form,$objExercise);

  /*   * ****************
   * FORM DISPLAY
   * **************** */
 // echo '<h3>' . $questionName . '</h3>';


  if (!empty($pictureName)) {
   echo '<img src="../document/download.php?doc_url=%2Fimages%2F' . $pictureName . '" border="0">';
  }

  if (!empty($msgErr)) {
   Display::display_normal_message($msgErr); //main API
  }

  // display the form
  echo '<div class="actions">';
  $form->display();
  echo '</div>';

 echo '<div class="actions">';
  if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
	  $scenario_lang_var = api_convert_encoding(get_lang('Scenario'), $charset, api_get_system_encoding());
     $return = '';
     // The lp_id parameter will be added by Javascript
  //   $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '">' . Display::return_icon('build.png', get_lang('Build')).get_lang("Build") . '</a>';
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&gradebook=&action=admin_view">' . Display::return_icon('organize.png', $scenario_lang_var).$scenario_lang_var . '</a>';
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&gradebook=&action=view">' . Display::return_icon('view.png', get_lang('ViewRight')).get_lang("ViewRight") . '</a>';
     echo $return;
   } else {
  echo '<a href="exercice.php?show=result&' . api_get_cidreq() . '">' . Display :: return_icon('pixel.gif', get_lang('Tracking'),array('class'=>'actionplaceholdericon toolactionreporting22')) . get_lang('Tracking') . '</a>';
   }
  echo '</div>';
 }
}

if (isset($_GET['viewQuestion'])) {
 $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
 $i = 1;
 $nbrQuestions = Security::remove_XSS($_GET["totTpl"]);
 $origin = 'fromgallery';
 // This query must be replaced
 $sql = "SELECT * FROM $TBL_QUESTIONS WHERE id=" . Database::escape_string(Security::remove_XSS($_GET["viewQuestion"]));
 $result = Database::query($sql, __FILE__, __LINE__);

 // Get quiz title
 $quiz_id = Security::remove_xss($_REQUEST['exerciseId']);
 $my_quiz = new Exercise();
 $my_quiz->read($quiz_id);

 $qnExist = Database::num_rows($result);
 if ($qnExist > 0) {
  // Content of secondary actions
  echo '<div class="actions" id="content_with_secondary_actions"><div style="width: 100%; padding: 1px;">';
  // Quiz title
  echo '<div class="sectiontitle" style="width:49%;margin:0px;">';
  echo $my_quiz->selectTitle();
  echo '</div><div>';
  showQuestion($_GET['viewQuestion'], false, $origin, $i, $nbrQuestions);
  echo '</div></div></div></div>';

 } else {
  if ($_GET["viewQuestion"] <= $nbrQuestions) {
   if ($_GET['prev']) {
    echo '<script type="text/javascript">window.location.href="admin.php?' . api_get_cidreq() . '&fromTpl=1&startPage=' . ($startPage - 1) . '&totTpl=' . $_GET["totTpl"] . '&viewQuestion=' . ($_GET['viewQuestion'] - 1) . '&fromExercise=' . Security::remove_XSS($_GET['fromExercise']) . '&prev=Y"</script>';
   } else {
    echo '<script type="text/javascript">window.location.href="admin.php?' . api_get_cidreq() . '&fromTpl=1&startPage=' . ($startPage + 1) . '&totTpl=' . $_GET["totTpl"] . '&viewQuestion=' . ($_GET['viewQuestion'] + 1) . '&fromExercise=' . Security::remove_XSS($_GET['fromExercise']) . '&next=Y"</script>';
   }
  }
 }
}
?>
