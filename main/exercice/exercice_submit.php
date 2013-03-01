<?php
// $Id: exercice_submit.php 22201 2009-07-17 19:57:03Z cfasanando $

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
 * 	Exercise submission
 * 	This script allows to run an exercise. According to the exercise type, questions
 * 	can be on an unique page, or one per page with a Next button.
 *
 * 	One exercise may contain different types of answers (unique or multiple selection,
 * 	matching, fill in blanks, free answer, hot-spot).
 *
 * 	Questions are selected randomly or not.
 *
 * 	When the user has answered all questions and clicks on the button "Ok",
 * 	it goes to exercise_result.php
 *
 * 	Notice : This script is also used to show a question before modifying it by
 * 	the administrator
 * 	@package dokeos.exercise
 * 	@author Olivier Brouckaert
 * 	@author Julio Montoya multiple fill in blank option added
 * 	@version $Id: exercice_submit.php 22201 2009-07-17 19:57:03Z cfasanando $
 */
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once 'exercise.lib.php';

// debug var. Set to 0 to hide all debug display. Set to 1 to display debug messages.
$debug = 0;
/*
  // answer types
  define('UNIQUE_ANSWER',		1);
  define('MULTIPLE_ANSWER',	2);
  define('FILL_IN_BLANKS',	3);
  define('MATCHING',			4);
  define('FREE_ANSWER', 		5);
  define('HOT_SPOT', 			6);
  define('HOT_SPOT_ORDER', 	7);
 */
// name of the language file that needs to be included
$language_file = 'exercice';

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;


/* ------------	ACCESS RIGHTS ------------ */
// notice for unauthorized people.
api_protect_course_script(true);

require_once api_get_path(LIBRARY_PATH) . 'text.lib.php';

$is_allowedToEdit = api_is_allowed_to_edit();
global $_course;

/*if (!isset($_REQUEST['quizpopup'])) {
 //$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/jquery-1.4.2.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
}*/
$origin = Security::remove_XSS($_REQUEST['origin']);
if ($origin=='learnpath') {
  $htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/jquery-1.4.2.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
  $htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/jquery-ui/js/jquery-ui-1.8.1.custom.min.js" type="text/javascript"></script>';
  $htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'. api_get_path(WEB_PATH) .'javascript/jquery-ui/css/ui-lightness/jquery-ui-1.8.1.custom.css" />';
$htmlHeadXtra[] = '<script type="text/javascript">
        (function ($) {
           try {
                var a = $.ui.mouse.prototype._mouseMove; 
                $.ui.mouse.prototype._mouseMove = function (b) { 
                b.button = 1; a.apply(this, [b]); 
                } 
            }catch(e) {}
        } (jQuery));
    </script>';
}

$htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/customInput.jquery.js" language="javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript"> 
	// Run the script on DOM ready:
	$(function(){
                try {
		$("input").customInput();
                } catch(e){}
	});
	</script>';
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.corners.min.js" type="text/javascript"></script>';
//This library is necessary for the time control feature
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/epiclock/javascript/jquery.dateformat.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/epiclock/javascript/jquery.epiclock.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
if (api_get_setting('show_glossary_in_documents') != 'none' && isset($_GET['exerciseId']) && $_GET['exerciseId'] > 0 ){
  $htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js"></script>';
  if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
    $htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/plugins/glossary/fck_glossary_manual.js"></script>';
  } else {
    $htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/glossary_quiz.js"/></script>';
  }
}

$_configuration['live_exercise_tracking'] = true;
$stat_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$exercice_attemp_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

$TBL_EXERCICE_QUESTION = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES = Database :: get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS = Database :: get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES = Database :: get_course_table(TABLE_QUIZ_ANSWER);

// general parameters passed via POST/GET

if(!empty($_POST['freeaction']))
{
	$freeans_action = $_POST['freeaction'];
	$freeans_marks = $_POST['marks'];
	$freeans_questionId = $_POST['freeqnid'];
	$freeans_comment = $_POST['freeans_comment'];
	$freeans_exeid = $_POST['freeexeid'];

	$sql = "UPDATE $exercice_attemp_table SET teacher_comment = '".$freeans_comment."', marks = $freeans_marks WHERE question_id = $freeans_questionId AND exe_id = $freeans_exeid";
	$result = Database::query($sql, __FILE__, __LINE__);
}


if (empty($learnpath_id)) {
 $learnpath_id = Security::remove_XSS($_REQUEST['learnpath_id']);
}
if (empty($learnpath_item_id)) {
 $learnpath_item_id = Security::remove_XSS($_REQUEST['learnpath_item_id']);
}
if (empty($formSent)) {
 $formSent = $_REQUEST['formSent'];
}
//echo 'form=='.$formSent;
if (empty($exerciseResult)) {
 $exerciseResult = $_REQUEST['exerciseResult'];
}

if (empty($exerciseResultCoordinates)) {
 $exerciseResultCoordinates = $_REQUEST['exerciseResultCoordinates'];
}
if (empty($exerciseType)) {
 $exerciseType = $_REQUEST['exerciseType'];
}
if (empty($exerciseId)) {
 $exerciseId = Database::escape_string(intval($_REQUEST['exerciseId']));
}

if (empty($choice)) {
 $choice = $_REQUEST['choice'];
}
if (empty($_REQUEST['choice'])) {
 $choice = $_REQUEST['choice2'];
}
if (empty($questionNum)) {
 $questionNum = Database :: escape_string($_REQUEST['questionNum']);
}
if (empty($nbrQuestions)) {
 $nbrQuestions = Database :: escape_string($_REQUEST['nbrQuestions']);
}
if (empty($buttonCancel)) {
 $buttonCancel = $_REQUEST['buttonCancel'];
}

if (!empty($_REQUEST['quizpopup'])) {
 $quizpopup = $_REQUEST['quizpopup'];
}
$error = '';
if (!isset($exerciseType)) {
 $exe_start_date = time();
 $_SESSION['exercice_start_date'] = $exe_start_date;
}
// if the user has clicked on the "Cancel" button
if ($buttonCancel) {
 // returns to the exercise list
 header("Location: exercice.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id");
 exit ();
}

if ($origin == 'learnpath' && isset($_GET['not_multiple_attempt']) && $_GET['not_multiple_attempt'] == strval(intval($_GET['not_multiple_attempt']))) {
 $not_multiple_attempt = (int) $_GET['not_multiple_attempt'];
 if ($not_multiple_attempt === 1) {
  require_once '../inc/reduced_header.inc.php';
  echo '<div style="height:10px">&nbsp;</div>';   
  Display :: display_warning_message(get_lang('ReachedOneAttempt'));
  exit;
 }
}

if ($origin == 'builder' || (isset($_GET['clean']) && $_GET['clean'] === 'true')) {
 /*  * **************************** */
 /* Clears the exercise session */
 /*  * **************************** */
 if (isset($_SESSION['objExercise'])) {
  api_session_unregister('objExercise');
  unset($objExercise);
 }
 if (isset($_SESSION['objQuestion'])) {
  api_session_unregister('objQuestion');
  unset($objQuestion);
 }
 if (isset($_SESSION['objAnswer'])) {
  api_session_unregister('objAnswer');
  unset($objAnswer);
 }
 if (isset($_SESSION['questionList'])) {
  api_session_unregister('questionList');
  unset($questionList);
 }
 if (isset($_SESSION['newquestionList'])) {
  api_session_unregister('newquestionList');
  unset($newquestionList);
 }
 if (isset($_SESSION['exerciseResult'])) {
  api_session_unregister('exerciseResult');
  unset($exerciseResult);
 }
 if (isset($_SESSION['exerciseResultCoordinates'])) {
  api_session_unregister('exerciseResultCoordinates');
  unset($exerciseResultCoordinates);
 }
}

$safe_lp_id = ($learnpath_id == '') ? 0 : (int) $learnpath_id;
$safe_lp_item_id = ($learnpath_item_id == '') ? 0 : (int) $learnpath_item_id;
$condition = ' WHERE ' .
        'exe_exo_id = ' . "'" . $exerciseId . "'" . ' AND ' .
        'exe_user_id = ' . "'" . api_get_user_id() . "'" . ' AND ' .
        'exe_cours_id = ' . "'" . $_course['id'] . "'" . ' AND ' .
        'status = ' . "'incomplete'" . ' AND ' .
        'orig_lp_id = ' . "'" . $safe_lp_id . "'" . ' AND ' .
        'orig_lp_item_id = ' . "'" . $safe_lp_item_id . "'" . ' AND ' .
        'session_id = ' . "'" . (int) $_SESSION['id_session'] . "'";


// This code is implemented in order for avoid go to question que we are left in an attempt - for more details see : Bug #7802
// Now each attempt start from first question! - Bug #7802
if (!isset($_GET['autocomplete'])) {
$sql_check_incomplete = "SELECT exe_id,status,exe_exo_id,exe_user_id,exe_cours_id,data_tracking FROM $stat_table".$condition;
$rs_check_incomplete = Database::query($sql_check_incomplete,__FILE__,__LINE__);
$row_check_incomplete = Database::fetch_array($rs_check_incomplete);

$incomplete_data_info = array();
$incomplete_exe_id = $row_check_incomplete['exe_id'];
$incomplete_status = $row_check_incomplete['status'];
$incomplete_data_tracking = $row_check_incomplete['data_tracking'];
$incomplete_data_info = explode(',',$incomplete_data_tracking);

// finish attempt for go for next attempt
$clean_field = false;
foreach ($incomplete_data_info as $incomplete_question_id) {
    $sql = "SELECT COUNT(*) as count FROM $exercice_attemp_table WHERE exe_id='".$incomplete_exe_id."' AND user_id='".api_get_user_id()."' AND course_code='".api_get_course_id()."' AND question_id='".$incomplete_question_id."'";
    $rs = Database::query($sql,__FILE__,__LINE__);
    $row = Database::fetch_array($rs);
	$clean_field = true;
    if ($row['count'] == 0) { // Complete records
        $sql = "INSERT INTO $exercice_attemp_table(exe_id,user_id,question_id,course_code) VALUES('".$incomplete_exe_id."','".api_get_user_id()."','".$incomplete_question_id."','".api_get_course_id()."')";
        Database::query($sql,__FILE__,__LINE__);
    }
}

// Update attempt
if ($clean_field) {
// Avoid go to question that we left in a first attempt - Bug #7802
 $query_clean_attempt = 'UPDATE '.$stat_table.' SET status="left_incomplete" ' . $condition;
 Database::query($query_clean_attempt,__FILE__,__LINE__);
}

}

$TBL_EXERCICES = Database :: get_course_table(TABLE_QUIZ_TEST);
$query = "SELECT type,feedback_type,expired_time FROM $TBL_EXERCICES WHERE id=$exerciseId";
$result = api_sql_query($query, __FILE__, __LINE__);
$exercise_row = Database :: fetch_array($result);
$exerciseType = $exercise_row['type'];
$exerciseFeedbackType = $exercise_row['feedback_type'];

if(($exerciseFeedbackType == '3') && (!isset($_GET['action'])))
{	
	$_SESSION['ValidateQn'] = 'Y';
}

/*
 * Time control feature
 * if the expired time is major that zero(0) then
 * the expired time is compute on this time
 */

$course_code = api_get_course_id();
//Get the expired time of the current exercice in track_e_exercices
$total_minutes = $exercise_row["expired_time"];
//echo 'from db expired time=='.$total_minutes;
$total_seconds = $total_minutes * 60;

// use gmt time here because of timezones
$current_timestamp = (int)gmdate('U');
//echo 'total seconds=='.$total_seconds;
//unset($_SESSION['expired_time']);
//echo 'session expired time=='.$_SESSION['expired_time'];
//Disable for learning path

if ($exercise_row['expired_time'] != 0) {
 if (!isset($_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$exerciseId][$safe_lp_id])) {
  //In case that the current session php is broken
  //Timer - Get expired_time for a student
  $condition = ' WHERE ' .
          'exe_exo_id =   ' . "'" . Database::escape_string($exerciseId) . "'" . ' AND ' .
          'exe_user_id =  ' . "'" . api_get_user_id() . "'" . ' AND ' .
          'exe_cours_id = ' . "'" . api_get_course_id() . "'" . ' AND ' .
          'status = ' . "'incomplete'" . ' AND ' .
          'orig_lp_id = ' . "'" . $safe_lp_id . "'" . ' AND ' .
          'orig_lp_item_id = ' . "'" . $safe_lp_item_id . "'" . ' AND ' .
          'session_id = ' . "'" . api_get_session_id() . "'";

  $sql_track = 'SELECT exe_id,expired_time_control FROM ' . $stat_table . $condition;
  $rs_sql = Database::query($sql_track, __FILE__, __LINE__);
  $exists_into_database = Database::num_rows($rs_sql);
  $track_exercice_row = Database::fetch_array($rs_sql);
  $expired_date_of_this_attempt = $track_exercice_row['expired_time_control'];

  if ($exists_into_database == 1) {
   $expected_time = strtotime($expired_date_of_this_attempt);
   // expected_time is already calculated on gmt base
   $plugin_expired_time = date('M d, Y H:i:s', $expected_time);
   $clock_expired_time = date('Y-m-d H:i:s', $expected_time);

   //Sessions  that contain the expired time
   $_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$exerciseId][$safe_lp_id] = $clock_expired_time;
   $_SESSION['end_expired_time'][$exerciseId][$safe_lp_id] = date('M d, Y H:i:s', $expected_time);
  } else {
   $expected_time = $current_timestamp + $total_seconds;
   $plugin_expired_time = gmdate('M d, Y H:i:s', $expected_time);
   $clock_expired_time = gmdate('Y-m-d H:i:s', $expected_time);
   
   $sql_track_e_exe = "UPDATE $stat_table SET expired_time_control = '" . $clock_expired_time . "' WHERE exe_id = '" . $track_exercice_row['exe_id'] . "'";
   Database::query($sql_track_e_exe, __FILE__, __LINE__);

   //Sessions  that contain the expired time
   $_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$exerciseId][$safe_lp_id] = $clock_expired_time;
   $_SESSION['end_expired_time'][$exerciseId][$safe_lp_id] = gmdate('M d, Y H:i:s', $expected_time);
  }
 } else {
  $plugin_expired_time = $_SESSION['end_expired_time'][$exerciseId][$safe_lp_id];
 }
}

/*
 * The time control feature is enable here - this feature is enable for a jquery plugin called epiclock
 * for more details of how it works see this link : http://eric.garside.name/docs.html?p=epiclock
 */

//Disable for learning path
if ($exercise_row['expired_time'] != 0) { //Sends the exercice form when the expired time is finished

 $htmlHeadXtra[] = "<script type='text/javascript'>
    $(document).ready(function(){	
      function onExpiredTimeExercise() {
        $('#wrapper-clock').hide();
        $('#expired-message-id').show();		
      }

       $('#text-content').epiclock({
         mode:  jQuery.epiclock.modes.countdown,
         format: 'x{ : } i{ : } s{}',
         offset: {seconds:".(strtotime($plugin_expired_time)-strtotime(gmdate('Y-m-d H:i:s')))."}
       }).bind('timer', onExpiredTimeExercise);  
       //$('.rounded').corners('transparent');  
       $('#submit_save').click(function () { 		
      });      
    });
    </script>";
}

if ($_configuration['live_exercise_tracking'] == true && $exerciseType == 2 && $exerciseFeedbackType != 1) {
 $query = 'SELECT * FROM ' . $stat_table . $condition;
 $result_select = api_sql_query($query, __FILE__, __LINE__);
 if (Database :: num_rows($result_select) > 0) {
  $getIncomplete = Database :: fetch_array($result_select);
  $exe_id = $getIncomplete['exe_id'];
  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
   define('QUESTION_LIST_ALREADY_LOGGED', 1);
   $recorded['questionList'] = explode(',', $getIncomplete['data_tracking']);
   $query = 'SELECT * FROM ' . $exercice_attemp_table . ' WHERE exe_id = ' . $getIncomplete['exe_id'] . ' ORDER BY tms ASC';
   $result = api_sql_query($query, __FILE__, __LINE__);
   while ($row = Database :: fetch_array($result)) {
    $recorded['exerciseResult'][$row['question_id']] = 1;
   }
   $exerciseResult = $_SESSION['exerciseResult'] = $recorded['exerciseResult'];
   $exerciseType = 2;
   $questionNum = count($recorded['exerciseResult']);
   $questionNum++;
   $questionList = $_SESSION['questionList'] = $recorded['questionList'];
   $do_not_delete_question_list = true;
  }
 } else {
  $table_recorded_not_exist = true;
 }
}


// if the user has submitted the form
if ($formSent) {
//	echo 'coming';
 if ($debug > 0) {
  echo str_repeat('&nbsp;', 0) . '$formSent was set' . "<br />\n";
 }

 // initializing
 if (!is_array($exerciseResult)) {
  $exerciseResult = array();
  $exerciseResultCoordinates = array();
 }

 // if the user has answered at least one question
 if (is_array($choice)) {
  if ($debug > 0) {
   echo str_repeat('&nbsp;', 0) . '$choice is an array' . "<br />\n";
  }
  
  if ($exerciseType == 1) {
   // $exerciseResult receives the content of the form.
   // Each choice of the student is stored into the array $choice
   $exerciseResult = $choice;

   // Also store hotspot spots in the session ($exerciseResultCoordinates
   // will be stored in the session at the end of this script)
   // The results will be stored by exercise_result.php if we are in
   // an exercise of type 1 (=all on one page)
   if (isset($_POST['hotspot'])) {
    $exerciseResultCoordinates = $_POST['hotspot'];
   }
  } else {
   // gets the question ID from $choice. It is the key of the array
   list ($key) = array_keys($choice);

   // if the user didn't already answer this question
   //if (!isset($exerciseResult[$key])) {
    // stores the user answer into the array
    $exerciseResult[$key] = $choice[$key];

    //saving each question
    if ($_configuration['live_exercise_tracking'] == true && $exerciseType == 2 && $exerciseFeedbackType != 1) {

     $nro_question = $questionNum; // - 1;
     //START of saving and qualifying each question submitted
     //------------------------------------------------------------------------------------------
     //
     define('ENABLED_LIVE_EXERCISE_TRACKING', 1);
     require_once 'question.class.php';
     require_once 'answer.class.php';
     $counter = 0;
     $correctChoice = 'Y';
     $main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
     $table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
     //foreach($questionList as $questionId)
     if (true) {
      $exeId = $exe_id;
      $questionId = $key;
      $counter++;
      // gets the student choice for this question
      $choice = $exerciseResult[$questionId];
      
      // timeout ?
	 if (isset($_SESSION['expired_time']) && $exercise_row['expired_time'] != 0) {
	     $current_time = (int)gmdate('U');
	     $expired_date = $_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$exerciseId][$safe_lp_id];
	 	 $expired_time = strtotime($expired_date.' UTC');
     	 $total_time_allowed = $expired_time + 5;
     	 if ($total_time_allowed < $current_time) {
      	 	$choice = 0;
     	 }
	 }
      
      // creates a temporary Question object
      $objQuestionTmp = Question :: read($questionId);

      $questionName = $objQuestionTmp->selectTitle();
      $questionDescription = $objQuestionTmp->selectDescription();
      $questionWeighting = $objQuestionTmp->selectWeighting();
      $answerType = $objQuestionTmp->selectType();
      $quesId = $objQuestionTmp->selectId(); //added by priya saini
      // destruction of the Question object
      unset($objQuestionTmp);

      if (isset($_POST['hotspot']) && isset($_POST['hotspot'][$key])) {
       $exerciseResultCoordinates[$key] = $_POST['hotspot'][$key];
      }

      // construction of the Answer object
      $objAnswerTmp = new Answer($questionId);
      $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
      $questionScore = 0;
      if ($answerType == FREE_ANSWER) {
       $nbrAnswers = 1;
      }
      // Count correct answers for reasoning question type
      if ($answerType == REASONING) {
          $correct_answers = 0;
          $nbrCorrects = $objAnswerTmp->selectNbrCorrects();
      }
      //Start loop

      for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
       $answer = $objAnswerTmp->selectAnswer($answerId);
       $answerComment = $objAnswerTmp->selectComment($answerId);
       $answerCorrect = $objAnswerTmp->isCorrect($answerId);
       $answerWeighting = $objAnswerTmp->selectWeighting($answerId);
       //	echo 'answer=='.$answer;
       //	echo 'answer correct=='.$answerCorrect;
       //	echo 'choice of answerid=='.$choice[$answerId];
       switch ($answerType) {
        // for unique answer
        case UNIQUE_ANSWER :
        	
         $studentChoice = ($choice == $answerId) ? 1 : 0;

         if ($studentChoice) {
          $questionScore += $answerWeighting;
          $totalScore += $answerWeighting;
         }

         break;
        // for multiple answers
        case MULTIPLE_ANSWER :

         //	echo 'stuudent choice==='.$choice[$answerId];
         //	echo 'answer coorrect===='.$answerCorrect;
         $studentChoice = $choice[$answerId];

         if ($studentChoice) {
          $questionScore += $answerWeighting;
          $totalScore += $answerWeighting;
         }

         break;
        // for reasoning answers
        case REASONING :

         //	echo 'inside case=='.$choice[$answerId];
         //	echo 'answer coorrect===='.$answerCorrect;
         //	echo 'before question score==='.$questionScore;
         //	echo 'before question weight===='.$questionWeighting;
         $studentChoice = $choice[$answerId];
         if ($answerCorrect && $correctChoice == 'Y') {
          if ($studentChoice == '1') {
              $correct_answers++;
           $correctChoice = 'Y';
          } else {
           $correctChoice = 'N';
           break;
          }
          if ($correct_answers == $nbrCorrects) {
           $questionScore += $questionWeighting;
           $totalScore += $questionWeighting;
          }
         }

         break;
        // for fill in the blanks
        case FILL_IN_BLANKS :

         // the question is encoded like this
         // [A] B [C] D [E] F::10,10,10@1
         // number 1 before the "@" means that is a switchable fill in blank question
         // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
         // means that is a normal fill blank question
         // first we explode the "::"
         $answer = str_replace("\r\n",'<br />', $answer);
         $pre_array = explode('::', $answer);

         // is switchable fill blank or not
         $last = count($pre_array) - 1;
         $is_set_switchable = explode('@', $pre_array[$last]);

         $switchable_answer_set = false;
         if (isset($is_set_switchable[1]) && $is_set_switchable[1] == 1) {
          $switchable_answer_set = true;
         }

         $answer = '';
         for ($k = 0; $k < $last; $k++) {
          $answer .= $pre_array[$k];
         }

         // splits weightings that are joined with a comma
         $answerWeighting = explode(',', $is_set_switchable[0]);

         // we save the answer because it will be modified
         $temp = $answer;

         // TeX parsing
         // 1. find everything between the [tex] and [/tex] tags
         $startlocations = strpos($temp, '[tex]');
         $endlocations = strpos($temp, '[/tex]');

         if ($startlocations !== false && $endlocations !== false) {
          $texstring = substr($temp, $startlocations, $endlocations - $startlocations + 6);
          // 2. replace this by {texcode}
          $temp = str_replace($texstring, '{texcode}', $temp);
         }

         $answer = '';
         $j = 0;

         //initialise answer tags
         $user_tags = array();
         $correct_tags = array();
         $real_text = array();
         // the loop will stop at the end of the text
         while (1) {
          // quits the loop if there are no more blanks (detect '[')
          if (($pos = strpos($temp, '[')) === false) {
           // adds the end of the text
           $answer = $temp;
           // TeX parsing - replacement of texcode tags
           $texstring = api_parse_tex($texstring);
           $answer = str_replace("{texcode}", $texstring, $answer);
           $real_text[] = $answer;
           break; //no more "blanks", quit the loop
          }
          // adds the piece of text that is before the blank
          //and ends with '[' into a general storage array
          $real_text[] = substr($temp, 0, $pos + 1);
          $answer .= substr($temp, 0, $pos + 1);
          //take the string remaining (after the last "[" we found)
          $temp = substr($temp, $pos + 1);
          // quit the loop if there are no more blanks, and update $pos to the position of next ']'
          if (($pos = strpos($temp, ']')) === false) {
           // adds the end of the text
           $answer .= $temp;
           break;
          }
          $choice[$j] = trim($choice[$j]);
          $user_tags[] = strtolower($choice[$j]);
          //put the contents of the [] answer tag into correct_tags[]
          $correct_tags[] = strtolower(substr($temp, 0, $pos));
          $j++;
          $temp = substr($temp, $pos + 1);
          //$answer .= ']';
         }

         $answer = '';
         $real_correct_tags = $correct_tags;
         $chosen_list = array();

         for ($i = 0; $i < count($real_correct_tags); $i++) {
          if ($i == 0) {
           $answer .= $real_text[0];
          }

          if (!$switchable_answer_set) {
           if (trim($correct_tags[$i]) == trim($user_tags[$i])) {
            // gives the related weighting to the student
            $questionScore += $answerWeighting[$i];
            // increments total score
            $totalScore += $answerWeighting[$i];
            // adds the word in green at the end of the string
            $answer .= $correct_tags[$i];
           }
           // else if the word entered by the student IS NOT the same as the one defined by the professor
           elseif (!empty($user_tags[$i])) {
            // adds the word in red at the end of the string, and strikes it
            $answer .= '<font color="red"><s>' . $user_tags[$i] . '</s></font>';
           } else {
            // adds a tabulation if no word has been typed by the student
            $answer .= '&nbsp;&nbsp;&nbsp;';
           }
          } else {
           // switchable fill in the blanks
           if (in_array($user_tags[$i], $correct_tags)) {
            $chosen_list[] = $user_tags[$i];
            $correct_tags = array_diff($correct_tags, $chosen_list);

            // gives the related weighting to the student
            $questionScore += $answerWeighting[$i];
            // increments total score
            $totalScore += $answerWeighting[$i];
            // adds the word in green at the end of the string
            $answer .= $user_tags[$i];
           } elseif (!empty($user_tags[$i])) {
            // else if the word entered by the student IS NOT the same as the one defined by the professor
            // adds the word in red at the end of the string, and strikes it
            $answer .= '<font color="red"><s>' . $user_tags[$i] . '</s></font>';
           } else {
            // adds a tabulation if no word has been typed by the student
            $answer .= '&nbsp;&nbsp;&nbsp;';
           }
          }
          // adds the correct word, followed by ] to close the blank
          $answer .= ' / <font color="green"><b>' . $real_correct_tags[$i] . '</b></font>]';
          if (isset($real_text[$i + 1])) {
           $answer .= $real_text[$i + 1];
          }
         }

         break;
        // for free answer
        case FREE_ANSWER :
         $studentChoice = $choice;

         if ($studentChoice) {
          //Score is at -1 because the question has'nt been corected
          $questionScore = -1;
          $totalScore += 0;
         }
         break;
        // for matching
        case MATCHING :
         if ($answerCorrect) {
          if ($answerCorrect == $choice[$answerId]) {
           $questionScore += $answerWeighting;
           $totalScore += $answerWeighting;
           $choice[$answerId] = $matching[$choice[$answerId]];
          } elseif (!$choice[$answerId]) {
           $choice[$answerId] = '&nbsp;&nbsp;&nbsp;';
          } else {
           //	$choice[$answerId] = '<font color="red"><s>' . $matching[$choice[$answerId]] . '</s></font>';
           $choice[$answerId] = $matching[$choice[$answerId]];
          }
         } else {
          $matching[$answerId] = $answer;
         }
         break;
        // for hotspot with no order
        case HOT_SPOT :
         $studentChoice = $choice[$answerId];

         if ($studentChoice) {
          $questionScore += $answerWeighting;
          $totalScore += $answerWeighting;
         }
         break;
        // for hotspot with fixed order
        case HOT_SPOT_ORDER :
         $studentChoice = $choice['order'][$answerId];

         if ($studentChoice == $answerId) {
          $questionScore += $answerWeighting;
          $totalScore += $answerWeighting;
          $studentChoice = true;
         } else {
          $studentChoice = false;
         }
         break;
         case HOT_SPOT_DELINEATION :	
         	$studentChoice=$choice[$answerId];
   			if($studentChoice) {
   				$questionScore+=$answerWeighting;
   				$totalScore+=$answerWeighting;
   			}										
			$_SESSION['hotspot_coord'][$answerId]=$objAnswerTmp->selectHotspotCoordinates($answerId);
			break;	
       } // end switch Answertype
      } // end for that loops over all answers of the current question
      // destruction of Answer
      unset($objAnswerTmp);

      //	echo 'questionweight final=='.$questionScore;
      //	echo 'final total score =='.$totalScore;

      $i++;

      $totalWeighting += $questionWeighting;
      //added by priya saini
      // Store results directly in the database
      // For all in one page exercises, the results will be
      // stored by exercise_results.php (using the session)
      if ($_configuration['tracking_enabled']) {
       if (empty($choice)) {
        $choice = 0;
       }
       if ($answerType == MULTIPLE_ANSWER) {
        if ($choice != 0) {
         $reply = array_keys($choice);

         for ($i = 0; $i < sizeof($reply); $i++) {
          $ans = $reply[$i];

          exercise_attempt($questionScore, $ans, $quesId, $exeId, $i);
         }
        } else {
         exercise_attempt($questionScore, 0, $quesId, $exeId, 0);
        }
       } elseif ($answerType == REASONING) {
        //echo 'choiceiiiiiiiiii===='.$choice;
        if ($choice != 0) {
         $reply = array_keys($choice);

         for ($i = 0; $i < sizeof($reply); $i++) {
          $ans = $reply[$i];
          //	echo 'question score=='.$questionScore;
          exercise_attempt($questionScore, $ans, $quesId, $exeId, $i);
         }
        } else {
         //	echo 'question score=='.$questionScore;
         exercise_attempt($questionScore, 0, $quesId, $exeId, 0);
        }
       } elseif ($answerType == MATCHING) {
        $j = sizeof($matching) + 1;

        for ($i = 0; $i < sizeof($choice); $i++, $j++) {
         $val = $choice[$j];
         //	echo 'aaa===='.$val;
         if (preg_match_all('#<font color="red"><s>([0-9a-z ]*)</s></font>#', $val, $arr1)) {
          $val = $arr1[1][0];
         }
         $val = $val;
         //	$val = strip_tags($val);
         $sql = "select position from $table_ans where question_id='" . Database :: escape_string($questionId) . "' and answer LIKE BINARY '" . Database :: escape_string($val) . "' AND correct=0";
         $res = api_sql_query($sql, __FILE__, __LINE__);
         if (Database :: num_rows($res) > 0) {
          $answer = Database :: result($res, 0, "position");
         } else {
          $answer = '';
          //$answer = $choice[$j];
         }
         //		echo 'fff==='.$answer;
         exercise_attempt($questionScore, $answer, $quesId, $exeId, $j);
        }
       } elseif ($answerType == FREE_ANSWER) {
        //$answer = $choice;
		$answer = $_POST['newchoice'];	
        exercise_attempt($questionScore, $answer, $quesId, $exeId, 0);
       } elseif ($answerType == UNIQUE_ANSWER) {
        $answer = $choice;
        exercise_attempt($questionScore, $answer, $quesId, $exeId, 0);
       } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) {
        exercise_attempt($questionScore, $answer, $quesId, $exeId, 0);
        if (is_array($exerciseResultCoordinates[$key])) {
         foreach ($exerciseResultCoordinates[$key] as $idx => $val) {
          exercise_attempt_hotspot($exeId, $quesId, $idx, $choice[$idx], $val);
         }
        }
       } else {
        exercise_attempt($questionScore, $answer, $quesId, $exeId, 0);
       }
      }
     }
     // end huge foreach() block that loops over all questions
     //at loops over all questions
     if (isset($exe_id)) {
      $sql_update = 'UPDATE ' . $stat_table . ' SET exe_result = exe_result + ' . (int) $totalScore . ',exe_weighting = exe_weighting + ' . (int) $totalWeighting . ' WHERE exe_id = ' . Database::escape_string($exe_id);
      api_sql_query($sql_update, __FILE__, __LINE__);
     }
     //END of saving and qualifying
     //------------------------------------------------------------------------------------------
     //
    }
//   }
  }
  if ($debug > 0) {
   echo str_repeat('&nbsp;', 0) . '$choice is an array - end' . "<br />\n";
  }
 }

 // the script "exercise_result.php" will take the variable $exerciseResult from the session
 api_session_register('exerciseResult');
 api_session_register('exerciseResultCoordinates');
 define('ALL_ON_ONE_PAGE', 1);
 define('ONE_PER_PAGE', 2);
 // if all questions on one page OR if it is the last question (only for an exercise with one question per page)

 //if ($exerciseType == ALL_ON_ONE_PAGE || $questionNum >= $nbrQuestions) {
 if ($exerciseType == ALL_ON_ONE_PAGE || ($exerciseType == ONE_PER_PAGE && !isset($_GET['action']) && $questionNum >= $nbrQuestions)) {
  if ($debug > 0) {
   echo str_repeat('&nbsp;', 0) . 'Redirecting to exercise_result.php - Remove debug option to let this happen' . "<br />\n";
  }
  // goes to the script that will show the result of the exercise
  if ($exerciseType == ALL_ON_ONE_PAGE) {
   if (isset($_REQUEST['quizpopup'])) {
    header("Location: exercise_result.php?id=$exe_id&exerciseType=$exerciseType&quizpopup=1&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id");
   } else {
    header("Location: exercise_result.php?id=$exe_id&exerciseType=$exerciseType&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id");
   }
  } else {
   /* if ($exe_id != '') {
     //clean incomplete
     $update_query = 'UPDATE ' . $stat_table . ' SET ' . "status = '', data_tracking='', exe_date = '" . date('Y-m-d H:i:s') . "'" . ' WHERE exe_id = ' . Database::escape_string($exe_id);
     api_sql_query($update_query, __FILE__, __LINE__);
     } */

   if ($exe_id != '') {
    //clean incomplete
    $update_query = 'UPDATE ' . $stat_table . ' SET ' . "status = '', data_tracking='', exe_date = '" . date('Y-m-d H:i:s') . '\' WHERE exe_id = ' . Database::escape_string($exe_id);
	Database::query($update_query, __FILE__, __LINE__);
   }
/*   if (isset($_REQUEST['quizpopup'])) {
    header("Location: exercise_show.php?id=$exe_id&exerciseType=$exerciseType&quizpopup=1&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&exerciseid=$exerciseId");
   } else {
    header("Location: exercise_show.php?id=$exe_id&exerciseType=$exerciseType&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&exerciseid=$exerciseId");
   }*/
   if($exerciseFeedbackType == 2)
	{
		unset($_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$exerciseId][$safe_lp_id]);
		unset($_SESSION['end_expired_time'][$exerciseId][$safe_lp_id]);
	   if($origin == 'learnpath')
		{
	    $lp_mode =  $_SESSION['lp_mode'];
		$url = '../newscorm/lp_controller.php?cidReq='.api_get_course_id().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId.'&fb_type='.$exerciseFeedbackType;
		$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';	 
		echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";
		}
		else {
			header("Location: exercice.php?".api_get_cidreq());
		}
	}   
/*	elseif($exerciseFeedbackType == 0)
	{
		header("Location: exercise_showbyquestion.php?id=$exe_id&exerciseType=$exerciseType&quizpopup=1&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&exerciseid=$exerciseId");
	}*/
	else
	{
		header("Location: exercise_show.php?id=$exe_id&exerciseType=$exerciseType&quizpopup=1&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&exerciseid=$exerciseId");
	}
  }
  exit ();
 }
 if ($debug > 0) {
  echo str_repeat('&nbsp;', 0) . '$formSent was set - end' . "<br />\n";
 }
}


// if the object is not in the session
//why destroying the exercise when a LP is loaded ?
//if (!isset($_SESSION['objExercise']) || $origin == 'learnpath' || $_SESSION['objExercise']->id != $_REQUEST['exerciseId']) {

if (!isset($_SESSION['objExercise']) || $_SESSION['objExercise']->id != $_REQUEST['exerciseId']) {
 if ($debug > 0) {
  echo str_repeat('&nbsp;', 0) . '$_SESSION[objExercise] was unset' . "<br />\n";
 }

 // construction of Exercise
 $objExercise = new Exercise();
 if(!isset($do_not_delete_question_list))
 	unset($_SESSION['questionList']);

 // if the specified exercise doesn't exist or is disabled
 if (!$objExercise->read($exerciseId) || (!$objExercise->selectStatus() && !$is_allowedToEdit && ($origin != 'learnpath'))) {
  unset($objExercise);
  $error = get_lang('ExerciseNotFound');
  //die(get_lang('ExerciseNotFound'));
 } else {
  // saves the object into the session
  api_session_register('objExercise');
  if ($debug > 0) {
   echo str_repeat('&nbsp;', 0) . '$_SESSION[objExercise] was unset - set now - end' . "<br />\n";
  }
 }
}
if (!isset($objExercise) && isset($_SESSION['objExercise'])) {
 $objExercise = $_SESSION['objExercise'];
 api_session_unregister('questionList');
}

if (!is_object($objExercise)) {
 header('Location: exercice.php');
 exit ();
}
$Exe_starttime = $objExercise->start_time;
$Exe_endtime = $objExercise->end_time;
$quizID = $objExercise->selectId();
$exerciseAttempts = $objExercise->selectAttempts();
$exerciseTitle = $objExercise->selectTitle();
$exerciseDescription = $objExercise->selectDescription();
$exerciseDescription = $exerciseDescription;
$exerciseSound = $objExercise->selectSound();
$randomQuestions = $objExercise->isRandom();
$exerciseType = $objExercise->selectType();
$table_quiz_test = Database :: get_course_table(TABLE_QUIZ_TEST);
//if (!isset($_SESSION['questionList']) || $origin == 'learnpath') {
//in LP's is enabled the "remember question" feature?
if (!isset($_REQUEST['action'])) {
 $my_exe_id = Security :: remove_XSS($_GET['exerciseId']);
 if (!isset($_SESSION['questionList'])) {
  if ($debug > 0) {
   echo str_repeat('&nbsp;', 0) . '$_SESSION[questionList] was unset' . "<br />\n";
  }
  // selects the list of question ID
  $my_question_list = array();

 $TBL_QUIZ_TYPE = Database::get_course_table(TABLE_QUIZ_TYPE);
 $sql_scenario = "SELECT count(*) FROM $TBL_QUIZ_TYPE WHERE exercice_id = ".Database::escape_string(Security::remove_XSS($_REQUEST['exerciseId']))."  AND current_active = 1";
 $rs_scenario = Database::query($sql_scenario, __FILE__, __LINE__);
 $scenario = Database::result($rs_scenario, 0);

  if($scenario == 0){
  $questionList = ($randomQuestions ? $objExercise->selectRandomList() : $objExercise->selectQuestionList());
  }
  else
  {
	  $sql = "SELECT category_id,quiz_level,number_of_question,scenario_type FROM $TBL_QUIZ_TYPE WHERE exercice_id = ".Database::escape_string(Security::remove_XSS($_REQUEST['exerciseId'])). " AND current_active = 1";
		$result = Database::query($sql, __FILE__, __LINE__);
		while($row = Database::fetch_array($result))
		{	
			$sql_in = "SELECT DISTINCT(question.id) AS id FROM $TBL_EXERCICES quiz, $TBL_QUESTIONS question, $TBL_EXERCICE_QUESTION rel_question, $TBL_QUIZ_TYPE quiz_type WHERE quiz.id=rel_question.exercice_id AND rel_question.question_id = question.id AND quiz.id = quiz_type.exercice_id AND rel_question.exercice_id = quiz_type.exercice_id AND question.level = ".$row['quiz_level']." AND question.category = ".$row['category_id']." ORDER BY rel_question.question_order LIMIT ".$row['number_of_question'];			
			$result_in = Database::query($sql_in, __FILE__, __LINE__);
			$nbrQuestions = Database::num_rows($result_in);
			if($nbrQuestions <> 0){			
			while ($row_in = Database::fetch_array($result_in)) {				
				$questionList[] = $row_in[0];
			}
		    }
		}
  }

  // saves the question list into the session
  $sql = 'SELECT random FROM ' . $table_quiz_test . ' WHERE id="' . Database :: escape_string($my_exe_id) . '";';
  $rs = api_sql_query($sql, __FILE__, __LINE__);
  $row_number = Database :: fetch_array($rs);
  $z = 0;

  if ($row_number['random'] <> 0) {
   foreach ($questionList as $infoquestionList) {
    if ($z < $row_number['random']) {
     $my_question_list[$z] = $infoquestionList;
    } else {
     break;
    }
    $z++;
   }
   // $questionList=array();
   $questionList = $my_question_list;
  }
  api_session_register('questionList');
  if ($debug > 0) {
   echo str_repeat('&nbsp;', 0) . '$_SESSION[questionList] was unset - set now - end' . "<br />\n";
  }
 }
 if (!isset($objExercise) && isset($_SESSION['objExercise'])) {
  $questionList = $_SESSION['questionList'];
 }

 $quizStartTime = time();
 api_session_register('quizStartTime');
 $nbrQuestions = sizeof($questionList);
// if questionNum comes from POST and not from GET
 if (!$questionNum || $_POST['questionNum']) {
  // only used for sequential exercises (see $exerciseType)
  if (!$questionNum) {
   $questionNum = 1;
  } else {
   $questionNum++;
  }
 }
} //end by breetha

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
 $_SESSION['gradebook'] = Security :: remove_XSS($_GET['gradebook']);
 $gradebook = $_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
 unset($_SESSION['gradebook']);
 $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
 $interbreadcrumb[] = array(
     'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
     'name' => get_lang('Gradebook')
 );
}
//$nameTools=get_lang('Exercice');

$interbreadcrumb[] = array(
    "url" => "exercice.php?gradebook=$gradebook",
    "name" => get_lang('Exercices')
);
$interbreadcrumb[] = array(
    "url" => api_get_self() . "?gradebook=$gradebook",
    "name" => $exerciseTitle
);

if ($origin != 'learnpath') { //so we are not in learnpath tool
 $htmlHeadXtra[] = "<script type=\"text/javascript\" src=\"../plugin/hotspot/JavaScriptFlashGateway.js\"></script>
						<script src=\"../plugin/hotspot/hotspot.js\" type=\"text/javascript\"></script>
						<script language=\"JavaScript\" type=\"text/javascript\">
						<!--
						// -----------------------------------------------------------------------------
						// Globals
						// Major version of Flash required
						var requiredMajorVersion = 7;
						// Minor version of Flash required
						var requiredMinorVersion = 0;
						// Minor version of Flash required
						var requiredRevision = 0;
						// the version of javascript supported
						var jsVersion = 1.0;
						// -----------------------------------------------------------------------------
						// -->
						</script>
						<script language=\"VBScript\" type=\"text/vbscript\">
						<!-- // Visual basic helper required to detect Flash Player ActiveX control version information
						Function VBGetSwfVer(i)
						  on error resume next
						  Dim swControl, swVersion
						  swVersion = 0
	
						  set swControl = CreateObject(\"ShockwaveFlash.ShockwaveFlash.\" + CStr(i))
						  if (IsObject(swControl)) then
						    swVersion = swControl.GetVariable(\"\$version\")
						  end if
						  VBGetSwfVer = swVersion
						End Function
						// -->
						</script>
	
						<script language=\"JavaScript1.1\" type=\"text/javascript\">
						<!-- // Detect Client Browser type
						var isIE  = (navigator.appVersion.indexOf(\"MSIE\") != -1) ? true : false;
						var isWin = (navigator.appVersion.toLowerCase().indexOf(\"win\") != -1) ? true : false;
						var isOpera = (navigator.userAgent.indexOf(\"Opera\") != -1) ? true : false;
						jsVersion = 1.1;
						// JavaScript helper required to detect Flash Player PlugIn version information
						function JSGetSwfVer(i){
							// NS/Opera version >= 3 check for Flash plugin in plugin array
							if (navigator.plugins != null && navigator.plugins.length > 0) {
								if (navigator.plugins[\"Shockwave Flash 2.0\"] || navigator.plugins[\"Shockwave Flash\"]) {
									var swVer2 = navigator.plugins[\"Shockwave Flash 2.0\"] ? \" 2.0\" : \"\";
						      		var flashDescription = navigator.plugins[\"Shockwave Flash\" + swVer2].description;
									descArray = flashDescription.split(\" \");
									tempArrayMajor = descArray[2].split(\".\");
									versionMajor = tempArrayMajor[0];
									versionMinor = tempArrayMajor[1];
									if ( descArray[3] != \"\" ) {
										tempArrayMinor = descArray[3].split(\"r\");
									} else {
										tempArrayMinor = descArray[4].split(\"r\");
									}
						      		versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
						            flashVer = versionMajor + \".\" + versionMinor + \".\" + versionRevision;
						      	} else {
									flashVer = -1;
								}
							}
							// MSN/WebTV 2.6 supports Flash 4
							else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.6\") != -1) flashVer = 4;
							// WebTV 2.5 supports Flash 3
							else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.5\") != -1) flashVer = 3;
							// older WebTV supports Flash 2
							else if (navigator.userAgent.toLowerCase().indexOf(\"webtv\") != -1) flashVer = 2;
							// Can't detect in all other cases
							else {
	
								flashVer = -1;
							}
							return flashVer;
						}
						// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
						function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
						{
						 	reqVer = parseFloat(reqMajorVer + \".\" + reqRevision);
						   	// loop backwards through the versions until we find the newest version
							for (i=25;i>0;i--) {
								if (isIE && isWin && !isOpera) {
									versionStr = VBGetSwfVer(i);
								} else {
									versionStr = JSGetSwfVer(i);
								}
								if (versionStr == -1 ) {
									return false;
								} else if (versionStr != 0) {
									if(isIE && isWin && !isOpera) {
										tempArray         = versionStr.split(\" \");
										tempString        = tempArray[1];
										versionArray      = tempString .split(\",\");
									} else {
										versionArray      = versionStr.split(\".\");
									}
									versionMajor      = versionArray[0];
									versionMinor      = versionArray[1];
									versionRevision   = versionArray[2];
	
									versionString     = versionMajor + \".\" + versionRevision;   // 7.0r24 == 7.24
									versionNum        = parseFloat(versionString);
						        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
									if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
										return true;
									} else {
										return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );
									}
								}
							}
						}
						// -->
						</script>";
 if (isset($_REQUEST['quizpopup'])) {
  Display :: display_reduced_header();
 } else {
  Display :: display_tool_header($nameTools, "Exercise");
 }
} else {
 if (empty($charset)) {
  $charset = 'ISO-8859-15';
 }
 /*
  * HTML HEADER
  */
 Display::display_reduced_header();
 echo '<div style="height:10px">&nbsp;</div>';
}

/*echo '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/customInput.jquery.js" language="javascript"></script>';
echo '<script type="text/javascript"> 
	// Run the script on DOM ready:
	$(function(){			
		$("input").customInput();
	});
	</script>';*/

// I'm in a preview mode
if (!isset($_REQUEST['quizpopup'])) {
 if (api_is_course_admin() && $origin != 'learnpath') {
  echo '<div class="actions">';
	echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'">'.Display::return_icon('pixel.gif', get_lang('GoBackToEx'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('GoBackToEx').'</a>';
	echo '<a href="exercise_admin.php?scenario=yes&modifyExercise=yes&' . api_get_cidreq() . '&exerciseId='.$objExercise->id.'">' . Display::return_icon('pixel.gif', get_lang('Scenario'), array('class' => 'toolactionplaceholdericon toolactionscenario')) . get_lang('Scenario') . '</a>';
	echo '</div>';
 }
}

echo '<div id="content" class="actions"><div class="exercise_questions" style="width: 100%; padding: 1px;"><table width="100%" class="exercise_questions" ><tr><td>';
if ($exercise_row['expired_time'] != 0) {
 echo '<div width="100%" align="right" id="wrapper-clock"><div id="square" class="square"><div id="text-content" align="center" class="count_down"></div></div></div>';
 echo '<div style="display:none" class="section" id="expired-message-id">' . get_lang('ExerciceExpiredTimeMessage') . '</div>';
}
$exerciseTitle = api_parse_tex($exerciseTitle);
echo "<div class='sectiontitle quiztitle' style='width:49%;margin:0px;height:25px;'>" . $exerciseTitle ."<br/>" . strip_tags($exerciseDescription,'<a><span><img><sub><sup>') . "</div>";
if ($exerciseAttempts > 0) {
 $user_id = api_get_user_id();
 $course_code = api_get_course_id();
 $sql = "SELECT count(*) FROM $stat_table WHERE exe_exo_id = '$quizID'
				AND exe_user_id = '$user_id'
				AND status != 'incomplete'
				AND status != 'left_incomplete'
				AND orig_lp_id = $safe_lp_id 
				AND orig_lp_item_id = $safe_lp_item_id  
	            AND exe_cours_id = '$course_code' AND session_id = '" . (int) $_SESSION['id_session'] . "'";

 $aquery = api_sql_query($sql, __FILE__, __LINE__);
 $attempt = Database :: fetch_array($aquery);

 if ($attempt[0] >= $exerciseAttempts) {
  if (!api_is_allowed_to_edit()) {
   Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttempts'), $exerciseTitle, $exerciseAttempts), false, true);
   if ($origin != 'learnpath')    
    //Display :: display_footer();
    exit;
  } else {
   Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttemptsAdmin'), $exerciseTitle, $exerciseAttempts), false, true);
  }
 }
}
if (!function_exists('convert_date_to_number')) {

 function convert_date_to_number($default) {
  // 2008-10-12 00:00:00 ---to--> 12345672218 (timestamp)
  $parts = split(' ', $default);
  list ($d_year, $d_month, $d_day) = split('-', $parts[0]);
  list ($d_hour, $d_minute, $d_second) = split(':', $parts[1]);
  return mktime($d_hour, $d_minute, $d_second, $d_month, $d_day, $d_year);
 }

}
$limit_time_exists = (($Exe_starttime != '0000-00-00 00:00:00') || ($Exe_endtime != '0000-00-00 00:00:00')) ? true : false;
if ($limit_time_exists) {
 $exercise_start_time = convert_date_to_number($Exe_starttime);
 $exercise_end_time = convert_date_to_number($Exe_endtime);
 $time_now = convert_date_to_number(date('Y-m-d H:i:s'));
 $permission_to_start = (($time_now - $exercise_start_time) > 0) ? true : false;
 if ($_SERVER['REQUEST_METHOD'] != 'POST')
  $exercise_timeover = (($time_now - $exercise_end_time) > 0) ? true : false;
 $lock_quiz_access = false;
 if ($permission_to_start == false || $exercise_timeover == true) { //
     $lock_quiz_access = true;
  if (!api_is_allowed_to_edit()) {
   $message_warning = ($permission_to_start == false) ? get_lang('ExerciseNoStartedYet') : get_lang('ReachedTimeLimit');
   echo '<br/>'.(sprintf($message_warning, $exerciseTitle, $exerciseAttempts));
  } else {
   $message_warning = ($permission_to_start == false) ? get_lang('ExerciseNoStartedAdmin') : get_lang('ReachedTimeLimitAdmin');
   echo '<br/>'.(sprintf($message_warning, $exerciseTitle, $exerciseAttempts));
  }
 }
}

// The user did not allow the test to start yet
if ($lock_quiz_access === true) {
    exit;
}
if (!empty($error)) {   
    Display :: display_error_message($error, false, true);
} else {
 if (!empty($exerciseSound)) {
  echo "<a href=\"../document/download.php?doc_url=%2Faudio%2F" . Security::remove_XSS($exerciseSound) . "\" target=\"_blank\">", "<img src=\"../img/sound.gif\" border=\"0\" align=\"absmiddle\" alt=", get_lang('Sound') .
  "\" /></a>";
 }
 // Get number of hotspot questions for javascript validation
 $number_of_hotspot_questions = 0;
 $onsubmit = '';
 $i = 0;
 //i have a doubt in this line cvargas
 if (!strcmp($questionList[0], '') === 0) {
  foreach ($questionList as $questionId) {
   $i++;
   $objQuestionTmp = Question :: read($questionId);
   // for sequential exercises
   if ($exerciseType == 2) {
    // if it is not the right question, goes to the next loop iteration
    if ($questionNum != $i) {
     continue;
    } else {
     if ($objQuestionTmp->selectType() == HOT_SPOT || $objQuestionTmp->selectType() == HOT_SPOT_DELINEATION) {
      $number_of_hotspot_questions++;
     }

     break;
    }
   } else {
    if ($objQuestionTmp->selectType() == HOT_SPOT || $objQuestionTmp->selectType() == HOT_SPOT_DELINEATION) {
     $number_of_hotspot_questions++;
    }
   }
  }
 }
 if ($number_of_hotspot_questions > 0) {
  $onsubmit = "onsubmit=\"return validateFlashVar('" . $number_of_hotspot_questions . "', '" . get_lang('HotspotValidateError1') . "', '" . get_lang('HotspotValidateError2') . "');\"";
 }
 //$s = strip_tags($exerciseDescription);
 $s = '';

 if ($exerciseType == 2) {
  $s2 = "&exerciseId=" . $exerciseId;
 }
 if (isset($_REQUEST['quizpopup'])) {
  $s3 = "&quizpopup=1";
 }

 if ($_SESSION['ValidateQn'] == 'Y' && !isset($_GET['action'])) {
  $s4 = "&action=Feedback";
 }
// Get real question ID
$real_question_id = $questionList[$questionNum];

 // If quiz is into learnpath then button is displayed on top
$submit_btn = '';
 if (!empty($questionList)) {
    $submit_btn = "<button style='margin-top:-37px;' class='next' type='submit' name='submit_save' id='submit_save'>"; 
     if ($objExercise->selectFeedbackType() == 1 && $_SESSION['objExercise']->selectType() == 2) {
            $submit_btn = '';
            $hotspot_get = $_POST['hotspot'];
            echo '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/thickbox.js" type="text/javascript"></script>';
            echo '<style type="text/css" media="all">@import "' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/thickbox.css";</style>';
     } else {
            $submit_btn .= get_lang('Validate');
            $submit_btn .= "</button>";
     }
 }
 $s .= " <form method='post'  action='" . api_get_self() . "?".  api_get_cidreq()."&autocomplete=off&gradebook=$gradebook".$s2;
 if (!empty($s3)) {
  $s .= $s3;
 }
 if (!empty($s4)) {
  $s .= $s4;
 }
 $s .= "' id='my_frm_exercise' name='frm_exercise' $onsubmit>
		 <input type='hidden' name='formSent' value='1' />
		 <input type='hidden' name='exerciseType' value='" . $exerciseType . "' />
		 <input type='hidden' name='exerciseId' value='" . $exerciseId . "' />
		 <input type='hidden' name='questionNum' value='" . $questionNum . "' />
		 <input type='hidden' name='nbrQuestions' value='" . $nbrQuestions . "' />
		 <input type='hidden' name='origin' value='" . $origin . "' />
		 <input type='hidden' name='learnpath_id' value='" . $learnpath_id . "' />
		 <input type='hidden' name='learnpath_item_id' value='" . $learnpath_item_id . "' />";
        $s .= $submit_btn;
        echo $s;
 $i = 1;
 $_SESSION['is_within_submit'] = 1;
 if (!isset($_REQUEST['action'])) {
  foreach ($questionList as $questionId) {
   // for sequential exercises
   if ($exerciseType == 2) {  
    // if it is not the right question, goes to the next loop iteration
    if ($questionNum != $i) { // Avoid go to next question
     $i++;
     continue;
    } else {
     if ($exerciseFeedbackType != 1) {
      // if the user has already answered this question
      if (isset($exerciseResult[$questionId]) && false) {
       // construction of the Question object
       $objQuestionTmp = Question :: read($questionId);
       $questionName = $objQuestionTmp->selectTitle();
       // destruction of the Question object
       unset($objQuestionTmp);
       echo '<div class="confirmation-message">'.get_lang('AlreadyAnswered'),'</div>';
       $i++; 
       //echo '<tr><td>'.get_lang('AlreadyAnswered').' &quot;'.$questionName.'&quot;</td></tr>';
       break;
    }
   }
    }
   }
   if ($exerciseType == 1) {
	   echo '<table border="0" cellpadding="5" cellspacing="5" width="100%"><tr><td>';
   }
   $questionId = !empty($questionId) ? $questionId : $real_question_id;
   echo  "<input type='hidden' name='questionId' value='" . $questionId . "' />";
   // shows the question and its answers
   showQuestion($questionId, false, $origin, $i, $nbrQuestions);
   if ($exerciseType == 1) {
	   echo '</td></tr></table>';
   }
   $i++;
   // for sequential exercises
   if ($exerciseType == 2) {
    // quits the loop
    break;
   }
  }
 } else {
  $questionId = !empty($questionId) ? $questionId : intval($_REQUEST['questionId']);
  showFeedback($questionId, false, $origin, $i, $nbrQuestions, $exe_id);
 }

 $b = 2;
}

if ($_configuration['live_exercise_tracking'] == true && $exerciseFeedbackType != 1) {
 //if($questionNum < 2){
 if ($table_recorded_not_exist) {
  if(empty($clock_expired_time))
  {
  	$clock_expired_time = '0000-00-00';
  }
  if ($exerciseType == 2) {
   api_sql_query("INSERT INTO $stat_table(exe_exo_id,exe_user_id,exe_cours_id,status,session_id,data_tracking,start_date,orig_lp_id,orig_lp_item_id, expired_time_control )
										VALUES('$exerciseId','" . api_get_user_id() . "','" . $_course['id'] . "','incomplete','" . api_get_session_id() . "','" . implode(',', $questionList) . "','" . date('Y-m-d H:i:s') . "',$safe_lp_id,$safe_lp_item_id, '$clock_expired_time')", __FILE__, __LINE__);
  } else {
   api_sql_query("INSERT INTO $stat_table (exe_exo_id,exe_user_id,exe_cours_id,status,session_id,start_date,orig_lp_id,orig_lp_item_id, expired_time_control )
									   VALUES('$exerciseId','" . api_get_user_id() . "','" . $_course['id'] . "','incomplete','" . api_get_session_id() . "','" . date('Y-m-d H:i:s') . "',$safe_lp_id,$safe_lp_item_id, '$clock_expired_time')", __FILE__, __LINE__);
  }
 }
}

echo '</td></tr></table></div></div>';

if ($origin != 'learnpath') {
 //so we are not in learnpath tool
 if (isset($_REQUEST['quizpopup'])) {
  Display :: display_reduced_header();
 } else {
  echo '<div class="actions">';
 	echo '</div>';
  Display :: display_footer();
 }
}