<?php
/* For licensing terms, see /dokeos_license.txt */

/**
*	@package dokeos.survey
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: question.php 21734 2009-07-02 17:12:41Z cvargas1 $
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once('survey.lib.php');

define('DOKEOS_SURVEY', true);

//$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script type="text/javascript">
						$(document).ready( function() {
							$("button").click(function() {
								$("#is_executable").attr("value",$(this).attr("name"));
							});
		 				} ); </script>';

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false,true)) {
		// Display header
		Display::display_tool_header();

		// start the content div
		echo '<div id="content">';
		
		Display :: display_error_message(get_lang('NotAllowed'), false);

		// close the content div
		echo '</div>';

		// Display the footer
		Display::display_footer();
		exit;
}

//Is valid request
$is_valid_request=$_REQUEST['is_executable'];
if ($request_index<>$is_valid_request) {
	if ($request_index=='save_question') {
		unset($_POST[$request_index]);
	} elseif ($request_index=='add_answer') {
		unset($_POST[$request_index]);
	} elseif($request_index=='remove_answer') {
		unset($_POST[$request_index]);
	}
}

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);

// getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
if (empty($survey_data)) {
		// Display header
		Display::display_tool_header();

		// start the content div
		echo '<div id="content">';
		
		Display :: display_error_message(get_lang('InvallidSurvey'), false);

		// close the content div
		echo '</div>';

		// Display the footer
		Display::display_footer();
		exit;
}


$urlname = api_substr(api_html_entity_decode($survey_data['title'],ENT_QUOTES,$charset), 0, 40);
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}

if($survey_data['survey_type']==1) {
	$sql = 'SELECT id FROM '.Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP).' WHERE survey_id = '.(int)$_GET['survey_id'].' LIMIT 1';
	$rs = Database::query($sql,__FILE__,__LINE__);
	if(Database::num_rows($rs)===0) {
		header('Location: survey.php?'.  api_get_cidreq().'&survey_id='.(int)$_GET['survey_id'].'&message='.'YouNeedToCreateGroups');
		exit;
	}
}

// breadcrumbs
$interbreadcrumb[] = array ("url" => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array ("url" => 'survey.php?survey_id='.Security::remove_XSS($_GET['survey_id']), 'name' => strip_tags($urlname));

// Tool name
if ($_GET['action'] == 'add') {
	$tool_name = get_lang('AddQuestion');
}
if ($_GET['action'] == 'edit') {
	$tool_name = get_lang('EditQuestion');
}

// the possible question types
$possible_types = array('personality','yesno', 'multiplechoice', 'multipleresponse', 'open', 'dropdown', 'comment', 'pagebreak', 'percentage', 'score');

// actions
$actions = '<div class="actions">';
$actions .= '<a href="survey.php?'.  api_get_cidreq().'&survey_id='.Security::remove_XSS($_GET['survey_id']).'">'.Display::return_icon('pixel.gif', get_lang('BackToSurvey'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('BackToSurvey').'</a>';
$actions .= '</div>';


// checking if it is a valid type
if (!in_array($_GET['type'], $possible_types))
{
	// Display header
	Display::display_tool_header();

	echo $actions;

	// start the content div
	echo '<div id="content">';

	Display :: display_error_message(get_lang('TypeDoesNotExist'), false);

	// close the content div
	echo '</div>';

	// display the footer
	Display::display_footer();
	exit;
}



// displaying the form for adding or editing the question
if (in_array($_GET['type'],$possible_types)) {
	
	
	$form = new $_GET['type'];
	
	if (isset($_SESSION['temp_sys_message'])) {
		$error_message=$_SESSION['temp_sys_message'];
		unset($_SESSION['temp_sys_message']);
	}
	
	// an action has been performed (for instance adding a possible answer, moving an answer, ...)
	if(!$_POST)
	{
		// The defaults values for the form
		$form_content['answers'] = array('', '');
	
		if ($_GET['type'] == 'yesno') {
			$form_content['answers'][0]=get_lang('Yes');
			$form_content['answers'][1]=get_lang('No');
		}
	
		if ($_GET['type'] == 'personality') {
			$form_content['answers'][0]=get_lang('1');
			$form_content['answers'][1]=get_lang('2');
			$form_content['answers'][2]=get_lang('3');
			$form_content['answers'][3]=get_lang('4');
			$form_content['answers'][4]=get_lang('5');
	
			$form_content['values'][0]=0;
			$form_content['values'][1]=0;
			$form_content['values'][2]=1;
			$form_content['values'][3]=2;
			$form_content['values'][4]=3;
		}
	
		// We are editing a question
		if (isset($_GET['question_id']) AND !empty($_GET['question_id'])) {
			$form_content = survey_manager::get_question($_GET['question_id']);
		}
	
		
	
		if ($error_message!='') {
			$form_content['question']=$_SESSION['temp_user_message'];
			$form_content['answers']=$_SESSION['temp_answers'];
			$form_content['values']=$_SESSION['temp_values'];
			$form_content['horizontalvertical'] = $_SESSION['temp_horizontalvertical'];
	
	
			unset($_SESSION['temp_user_message']);
			unset($_SESSION['temp_answers']);
			unset($_SESSION['temp_values']);
			unset($_SESSION['temp_horizontalvertical']);
		}
	}
	else {
		$form_content = $_POST;
		$form_content = $form->handle_action($form_content);
	}

	// Displaying the header
	Display::display_tool_header();

	echo $actions;

	$error_message='';

	// Displys message if exists
	if ($error_message=='PleaseEnterAQuestion' || $error_message=='PleasFillAllAnswer'|| $error_message=='PleaseChooseACondition'|| $error_message=='ChooseDifferentCategories') {
		Display::display_error_message(get_lang($error_message), true);
	}

	// start the content div
	echo '<div id="content">';
	


	$form->create_form($form_content);
	$form->render_form();
}

// close the content div
echo '</div>';

// Actions bar
echo '<div class="actions">';
echo '</div>';
// Display the footer
Display :: display_footer();
?>
