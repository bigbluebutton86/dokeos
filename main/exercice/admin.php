<?php // $Id: admin.php 21662 2009-06-29 14:55:09Z iflorespaz $

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
*	Exercise administration
* 	This script allows to manage (create, modify) an exercise and its questions
*
*	 Following scripts are includes for a best code understanding :
*
* 	- exercise.class.php : for the creation of an Exercise object
* 	- question.class.php : for the creation of a Question object
* 	- answer.class.php : for the creation of an Answer object
* 	- exercise.lib.php : functions used in the exercise tool
* 	- exercise_admin.inc.php : management of the exercise
* 	- question_admin.inc.php : management of a question (statement & answers)
* 	- statement_admin.inc.php : management of a statement
* 	- answer_admin.inc.php : management of answers
* 	- question_list_admin.inc.php : management of the question list
*
* 	Main variables used in this script :
*
* 	- $is_allowedToEdit : set to 1 if the user is allowed to manage the exercise
* 	- $objExercise : exercise object
* 	- $objQuestion : question object
* 	- $objAnswer : answer object
* 	- $aType : array with answer types
* 	- $exerciseId : the exercise ID
* 	- $picturePath : the path of question pictures
* 	- $newQuestion : ask to create a new question
* 	- $modifyQuestion : ID of the question to modify
* 	- $editQuestion : ID of the question to edit
* 	- $submitQuestion : ask to save question modifications
* 	- $cancelQuestion : ask to cancel question modifications
* 	- $deleteQuestion : ID of the question to delete
* 	- $moveUp : ID of the question to move up
* 	- $moveDown : ID of the question to move down
* 	- $modifyExercise : ID of the exercise to modify
* 	- $submitExercise : ask to save exercise modifications
* 	- $cancelExercise : ask to cancel exercise modifications
* 	- $modifyAnswers : ID of the question which we want to modify answers for
* 	- $cancelAnswers : ask to cancel answer modifications
* 	- $buttonBack : ask to go back to the previous page in answers of type "Fill in blanks"
*
*	@package dokeos.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: admin.php 21662 2009-06-29 14:55:09Z iflorespaz $
*/


include('exercise.class.php');
include('question.class.php');
include('answer.class.php');


// name of the language file that needs to be included
$language_file = array('exercice','hotspot');

define('DOKEOS_QUIZGALLERY', true);

include("../inc/global.inc.php");
include('exercise.lib.php');
$this_section=SECTION_COURSES;

$is_allowedToEdit=api_is_allowed_to_edit();

if(!$is_allowedToEdit) {
	api_not_allowed(true);
}

// allows script inclusions
define(ALLOWED_TO_INCLUDE,1);

include_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include_once(api_get_path(LIBRARY_PATH).'document.lib.php');
require_once '../newscorm/learnpath.class.php';
// Load jquery library
//$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.2.min.js" language="javascript"></script>';

//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.corners.min.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/dhtmlwindow.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/modal.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<style type="text/css" media="all">@import "' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/modal.css";</style>';
$htmlHeadXtra[] = '<style type="text/css" media="all">@import "' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/dhtmlwindow.css";</style>';
//$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.tablednd_0_5.js"></script>';
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.ui.all.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script>
  $(document).ready(function (){
    $("div.label").attr("style","width: 100%;text-align:left");
    $("div.row").attr("style","width: 100%;");
    $("div.formw").attr("style","width: 100%;");
  });
</script>';


$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/jquery.epiclock.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
if (api_get_setting('show_glossary_in_documents') != 'none' && isset($_GET['viewQuestion']) && $_GET['viewQuestion'] > 0 ){
  $htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js"></script>';
  if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
    $htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/plugins/glossary/fck_glossary_manual.js"></script>';
  } else {
    $htmlHeadXtra[] = '<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/glossary_quiz.js"/></script>';
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
}

// Add the lp_id parameter to all links if the lp_id is defined in the uri
if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
  $lp_id = Security::remove_XSS($_GET['lp_id']);
 $htmlHeadXtra[] = '<script>
    $(document).ready(function (){
      $("a[href]").attr("href", function(index, href) {
           var param = "lp_id=' . $lp_id . '";
           var is_javascript_link = false;
           var info = href.split("javascript");

           if (info.length >= 2) {
             is_javascript_link = true;
           }
           if ($(this).attr("class") == "course_main_home_button" || $(this).attr("class") == "course_menu_button"  || $(this).attr("class") == "next_button"  || $(this).attr("class") == "prev_button" || is_javascript_link) {
             return href;
           } else {
             if (href.charAt(href.length - 1) === "?")
                 return href + param;
             else if (href.indexOf("?") > 0)
                 return href + "&" + param;
             else
                 return href + "?" + param;
           }
      });
    });
  </script>';
}

// Variable
$learnpath_id = Security::remove_XSS($_GET['lp_id']);
// Lp object
if (isset($_SESSION['lpobject'])) {
 if ($debug > 0)
  error_log('New LP - SESSION[lpobject] is defined', 0);
 $oLP = unserialize($_SESSION['lpobject']);
 if (is_object($oLP)) {
  if ($debug > 0)
   error_log('New LP - oLP is object', 0);
  if ($myrefresh == 1 OR (empty($oLP->cc)) OR $oLP->cc != api_get_course_id()) {
   if ($debug > 0)
    error_log('New LP - Course has changed, discard lp object', 0);
   if ($myrefresh == 1) {
    $myrefresh_id = $oLP->get_id();
   }
   $oLP = null;
   api_session_unregister('oLP');
   api_session_unregister('lpobject');
  } else {
   $_SESSION['oLP'] = $oLP;
   $lp_found = true;
  }
 }
}

// Add the extra lp_id parameter to some links
$add_params_for_lp = '';
if (isset($_GET['lp_id'])) {
  $add_params_for_lp = "&lp_id=".$learnpath_id;
}

/****************************/
/*  stripslashes POST data  */
/****************************/

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	foreach($_POST as $key=>$val)
	{
		if(is_string($val))
		{
			$_POST[$key]=stripslashes($val);
		}
		elseif(is_array($val))
		{
			foreach($val as $key2=>$val2)
			{
				$_POST[$key][$key2]=stripslashes($val2);
			}
		}

		$GLOBALS[$key]=$_POST[$key];
	}
}

// get vars from GET
if ( empty ( $exerciseId ) ) {
    $exerciseId = Security::remove_XSS($_GET['exerciseId']);
}
if ( empty ( $newQuestion ) ) {
    $newQuestion = Security::remove_XSS($_GET['newQuestion']);
}
if ( empty ( $modifyAnswers ) ) {
    $modifyAnswers = Security::remove_XSS($_GET['modifyAnswers']);
}
if ( empty ( $editQuestion ) ) {    
	$editQuestion = Security::remove_XSS($_GET['editQuestion']);
}
if ( empty ( $modifyQuestion ) ) {
    $modifyQuestion = Security::remove_XSS($_GET['modifyQuestion']);
}
if ( empty ( $deleteQuestion ) ) {
    $deleteQuestion = Security::remove_XSS($_GET['deleteQuestion']);
}
if ( empty ( $questionId ) ) {
    $questionId = $_SESSION['questionId'];
}
if ( empty ( $modifyExercise ) ) {
    $modifyExercise = Security::remove_XSS($_GET['modifyExercise']);
}
if ( empty ( $viewQuestion ) ) {
    $viewQuestion = Security::remove_XSS($_GET['viewQuestion']);
}

$htmlHeadXtra[] ='<script type="text/javascript">
$(document).ready(function(){ 

	$(function() {
		$("#contentLeft ul").sortable({ opacity: 0.6, cursor: "move",cancel: ".nodrag", update: function() {
			var order = $(this).sortable("serialize") + "&action=updateQuizQuestion";
			var record = order.split("&");
			var recordlen = record.length;
			var disparr = new Array();
			for(var i=0;i<(recordlen-1);i++)
			{
			 var recordval = record[i].split("=");
			 disparr[i] = recordval[1];			 
			}
		
			// call ajax to save new position
                    $.ajax({
			type: "GET",
			url: "exercise.ajax.php?'.api_get_cidReq().'&action=updateQuizQuestion&exerciseId='.$exerciseId.'&disporder="+disparr,
			success: function(msg){
                            document.location="admin.php?exerciseId='.Security::remove_XSS($_GET['exerciseId']).'&' . api_get_cidreq() . '";
                        }
                    })
                  }
		});
	});

});
</script> ';

// get from session
$objExercise = $_SESSION['objExercise'];
$objQuestion = $_SESSION['objQuestion'];
$objAnswer   = $_SESSION['objAnswer'];

// document path
$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// picture path
$picturePath=$documentPath.'/images';

// audio path
$audioPath=$documentPath.'/audio';

// the 5 types of answers
$aType=array(get_lang('UniqueSelect'),get_lang('MultipleSelect'),get_lang('FillBlanks'),get_lang('Matching'),get_lang('freeAnswer'));

// tables used in the exercise tool
$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          = Database::get_course_table(TABLE_QUIZ_ANSWER);
$TBL_DOCUMENT          = Database::get_course_table(TABLE_DOCUMENT);

if($_GET['action'] == 'exportqti2' && !empty($_GET['questionId'])) {
	require_once('export/qti2/qti2_export.php');
	$export = export_question((int)$_GET['questionId'],true);
	$qid = (int)$_GET['questionId'];
	require_once(api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php');
	$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	$temp_dir_short = uniqid();
	$temp_zip_dir = $archive_path."/".$temp_dir_short;
	if(!is_dir($temp_zip_dir)) mkdir($temp_zip_dir);
	$temp_zip_file = $temp_zip_dir."/".md5(time()).".zip";
	$temp_xml_file = $temp_zip_dir."/qti2export_".$qid.'.xml';
	file_put_contents($temp_xml_file,$export);
	$zip_folder=new PclZip($temp_zip_file);
	$zip_folder->add($temp_xml_file, PCLZIP_OPT_REMOVE_ALL_PATH);
	$name = 'qti2_export_'.$qid.'.zip';

	DocumentManager::file_send_for_download($temp_zip_file,true,$name);
	unlink($temp_zip_file);
	unlink($temp_xml_file);
	rmdir($temp_zip_dir);
	//DocumentManager::string_send_for_download($export,true,'qti2export_q'.$_GET['questionId'].'.xml');
	exit(); //otherwise following clicks may become buggy
}

// intializes the Exercise object
if(!is_object($objExercise))
{
	// construction of the Exercise object
	$objExercise=new Exercise();

	// creation of a new exercise if wrong or not specified exercise ID
	if($exerciseId)
	{
	    $objExercise->read($exerciseId);
	}

	// saves the object into the session
	api_session_register('objExercise');
}

// doesn't select the exercise ID if we come from the question pool
if(!$fromExercise)
{
	// gets the right exercise ID, and if 0 creates a new exercise
	if(!$exerciseId=$objExercise->selectId())
	{
		$modifyExercise='yes';
	}
}

$nbrQuestions=$objExercise->selectNbrQuestions();

// intializes the Question object
if($editQuestion || $newQuestion || $modifyQuestion || $modifyAnswers)
{
	if($editQuestion || $newQuestion)
	{
		// reads question data
		if($editQuestion)
		{
			// question not found
			if(!$objQuestion = Question::read($editQuestion))
			{
				die(get_lang('QuestionNotFound'));
			}
			// saves the object into the session
			api_session_register('objQuestion');
		}
	}

	// checks if the object exists
	if(is_object($objQuestion))
	{
		// gets the question ID
		$questionId=$objQuestion->selectId();
	}
}

// if cancelling an exercise
if($cancelExercise)
{
	// existing exercise
	if($exerciseId)
	{
		unset($modifyExercise);
	}
	// new exercise
	else
	{
		// goes back to the exercise list
		header('Location: exercice.php');
		exit();
	}
}

// if cancelling question creation/modification
if($cancelQuestion)
{
	// if we are creating a new question from the question pool
	if(!$exerciseId && !$questionId)
	{
		// goes back to the question pool
		header('Location: question_pool.php');
		exit();
	}
	else
	{
		// goes back to the question viewing
		$editQuestion=$modifyQuestion;

		unset($newQuestion,$modifyQuestion);
	}
}

// if cancelling answer creation/modification
if($cancelAnswers)
{
	// goes back to the question viewing
	$editQuestion=$modifyAnswers;

	unset($modifyAnswers);
}

// modifies the query string that is used in the link of tool name
if($editQuestion || $modifyQuestion || $newQuestion || $modifyAnswers) {
	$nameTools=get_lang('QuestionManagement');
}

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));
$interbreadcrumb[]=array("url" => "admin.php?exerciseId=".$objExercise->id,"name" => $objExercise->exercise);

// shows a link to go back to the question pool
if(!$exerciseId && $nameTools != get_lang('ExerciseManagement')){
	$interbreadcrumb[]=array("url" => "question_pool.php?fromExercise=$fromExercise","name" => get_lang('QuestionPool'));
}

// if the question is duplicated, disable the link of tool name
if($modifyIn == 'thisExercise') {
	if($buttonBack)	{
		$modifyIn='allExercises';
	} else {
		$noPHP_SELF=true;
	}
}

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

$htmlHeadXtra[] = '<script type="text/javascript">function callsave(){document.question_admin_form.submitform.value=1;document.forms["question_admin_form"].submit();}</script>';

$htmlHeadXtra[] = '<script type="text/javascript">function callHotspotSave(){document.frm_exercise.submitform.value="1 ";alert(document.frm_exercise.submitform.value);document.forms["frm_exercise"].submit();}</script>';

if(isset($_REQUEST['fromTpl']))
{
	$_SESSION['fromTpl'] = '1';
}

if(isset($_REQUEST['fromlp']))
{
	$_SESSION['fromlp'] = '1';
}

if(isset($_REQUEST['editQn']))
{
	$_SESSION['editQn'] = '1';
}

if(isset($_REQUEST['popup'])) {
	$popup = Security::remove_XSS($_REQUEST['popup']); // Posible deprecated code
} else {
	$popup = Security::remove_XSS($_REQUEST['popup']);// Posible deprecated code
}

if(isset($_REQUEST['startPage'])) {
	$startPage = Security::remove_XSS($_REQUEST['startPage']);// Posible deprecated code
}

if(isset($_REQUEST['totTpl'])) {
	$totTpl = Security::remove_XSS($_REQUEST['totTpl']);// Posible deprecated code
}


Display::display_tool_header($nameTools,'Exercise');

if (!isset($feedbacktype)) $feedbacktype=0;
if ($feedbacktype==1) {
			$url = 'question_pool.php?type=1&fromExercise='.$exerciseId.'&'.  api_get_cidreq();
		} else {
			$url = 'question_pool.php?fromExercise='.$exerciseId.'&'.  api_get_cidreq();
		}

if(($_SESSION['editQn'] != '1')&&(!isset($_GET['hotspotadmin']))) {
$exercice_id = Security::remove_XSS($_REQUEST['exerciseId']);
if (!isset($_REQUEST['exerciseId']) && (isset($_REQUEST['fromExercise']) && $_REQUEST['fromExercise'] > 0)) {
  $exercice_id = Security::remove_XSS($_REQUEST['fromExercise']);
}

// Main buttons
echo '<div class="actions">';
	$author_lang_var = api_convert_encoding(get_lang('Author'), $charset, api_get_system_encoding());
	$content_lang_var = api_convert_encoding(get_lang('Content'), $charset, api_get_system_encoding());
	$scenario_lang_var = api_convert_encoding(get_lang('Scenario'), $charset, api_get_system_encoding());

	if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0)
	{
	  $lp_id = Security::remove_XSS($_GET['lp_id']);
	  echo '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', $author_lang_var, array('class' => 'toolactionplaceholdericon toolactionauthor')).$author_lang_var . '</a>';
	  echo '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&action=add_item&type=step">' . Display::return_icon('pixel.gif', $content_lang_var, array('class' => 'toolactionplaceholdericon toolactionauthorcontent')).$content_lang_var . '</a>';
	} else {
	  echo '<a href="exercice.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('List'), array('class' => 'toolactionplaceholdericon toolactionback'))  . get_lang('List') . '</a>';
	}
	echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('NewEx'), array('class' => 'toolactionplaceholdericon toolactionnewquiz'))  . get_lang('NewEx') . '</a>';	
	echo '<a href="admin.php?' . api_get_cidreq() . '&exerciseId='.$exercice_id.'">' . Display::return_icon('pixel.gif', get_lang('Questions'), array('class' => 'toolactionplaceholdericon toolactionquestion')). get_lang('Questions') . '</a>';
	echo '<a href="exercise_admin.php?scenario=yes&modifyExercise=yes&' . api_get_cidreq() . '&exerciseId='.$exercice_id.'">' . Display::return_icon('pixel.gif', $scenario_lang_var, array('class' => 'toolactionplaceholdericon toolactionscenario')) . $scenario_lang_var . '</a>';
	echo '<a href="exercice_submit.php?' . api_get_cidreq() . '&exerciseId='.$exercice_id.'&clean=true">' . Display::return_icon('pixel.gif', get_lang('ViewRight'), array('class' => 'toolactionplaceholdericon toolactionsearch')). get_lang('ViewRight') . '</a>';

echo '</div>';

}
else
{
	if(isset($viewQuestion))
	{
		$edit_question_id = $viewQuestion;
		$link_param_prev = "&viewQuestion=".($viewQuestion-1);
		$link_param_next = "&viewQuestion=".($viewQuestion+1);
	}
	else if(isset($editQuestion))
	{
		$edit_question_id = $editQuestion;
		$link_param_prev = "&viewQuestion=".($editQuestion-1);
		$link_param_next = "&viewQuestion=".($editQuestion+1);
	}
}

if (isset($_REQUEST['hotspotadmin'])) {
  //Display::display_tool_header();
  $exercice_id = Security::remove_XSS($_REQUEST['exerciseId']);
  
  // Main buttons
  echo '<div class="actions">';
	  $scenario_lang_var = api_convert_encoding(get_lang('Scenario'), $charset, api_get_system_encoding());
	  echo '<a href="exercice.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('List'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('List') . '</a>';
	  echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('NewEx'), array('class' => 'toolactionplaceholdericon toolactionnewquiz')) . get_lang('NewEx') . '</a>';
	  echo '<a href="admin.php?' . api_get_cidreq() . '&exerciseId='.$exercice_id.'">' . Display::return_icon('pixel.gif', get_lang('Questions'), array('class' => 'toolactionplaceholdericon toolactionquestion')) . get_lang('Questions') . '</a>';
	  echo '<a href="exercise_admin.php?scenario=yes&modifyExercise=yes&' . api_get_cidreq() . '&exerciseId='.$exercice_id.'">' . Display::return_icon('pixel.gif', $scenario_lang_var, array('class' => 'toolactionplaceholdericon toolactionscenario'))  . $scenario_lang_var . '</a>';
  echo '</div>';
}
if(isset($_GET['message'])){
	if (in_array($_GET['message'], array('ExerciseStored'))) {
	  Display::display_confirmation_message(get_lang($_GET['message']));
	}
}

if($newQuestion || $editQuestion || $viewQuestion) {
	// statement management
	if(isset($_REQUEST['answerType']) && !empty($_REQUEST['answerType'])) {
		$type = Security::remove_XSS($_REQUEST['answerType']);
	}
	if(isset($_REQUEST['type']) && !empty($_REQUEST['type'])) {
		$type = Security::remove_XSS($_REQUEST['type']);
	}
	?><input type="hidden" name="Type" value="<?php echo $type; ?>" />
	<?php
        
        if($type == 6) {
          $type_hotspost_delineation = $type;
          include('hotspot_one.inc.php');
	} 
	else if($type==9)
	{
		$type_hotspost_delineation = $type;
		include('hotspot_delineation.inc.php');
	}
	else {
	  include('question_admin.inc.php');
	}
//	include('question_admin.inc.php');
}

/*if(isset($_GET['hotspotadmin'])){
	include('hotspot_admin.inc.php');
}*/

if(!$newQuestion && !$modifyQuestion && !$editQuestion && !$viewQuestion && !isset($_GET['hotspotadmin']))
{
   // Question list management(nice buttons for questions here)
	include_once('question_list_admin.inc.php');
	include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
	$form = new FormValidator('exercise_admin', 'post', api_get_self().'?exerciseId='.Security::remove_XSS($_GET['exerciseId']));
	$form -> addElement ('hidden','edit','true');
	//$objExercise -> createForm ($form,'simple');

	if($form -> validate()) {
		$objExercise -> processCreation($form,'simple');
		if($form -> getSubmitValue('edit') == 'true')
			Display::display_confirmation_message(get_lang('ExerciseEdited'));
	}
	if(api_get_setting('search_enabled')=='true' && !extension_loaded('xapian')) {
			echo '<div class="confirmation-message">'.get_lang('SearchXapianModuleNotInstaled').'</div>';
	}
	$form -> display ();
	
}

api_session_register('objExercise');
api_session_register('objQuestion');
api_session_register('objAnswer');


if($popup == '1') {
//  echo '<script type="text/javascript">parent.TplWindow.hide()</script>';
//  echo '<script type="text/javascript">window.location.href="admin.php"</script>';
} else {
	if(($_SESSION['fromTpl'] == '')&&(!isset($_SESSION['fromlp']))){
		Display::display_footer();
	}
}
?>