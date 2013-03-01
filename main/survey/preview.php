<?php
/* For licensing terms, see /dokeos_license.txt */

/**
*	@package dokeos.survey
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
*	@author Julio Montoya Armas <gugli100@gmail.com>, Dokeos: Personality Test modifications
* 	@version $Id: survey_list.php 10680 2007-01-11 21:26:23Z pcool $
*
* 	@todo use quickforms for the forms
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once('survey.lib.php');

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);

$origin = '';
$origin_string='';
if (isset($_GET['origin'])) {
	$origin =  Security::remove_XSS($_GET['origin']);
	$origin_string = '&origin='.$origin;
}

// We exit here if ther is no valid $_GET parameter
if (!isset($_GET['survey_id']) OR !is_numeric($_GET['survey_id'])){
//	Display :: display_header(get_lang('SurveyPreview'));
	Display::display_tool_header();
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
//	Display :: display_footer();
	Display::display_tool_footer();
	exit;
}


// getting the survey information
$survey_id = Security::remove_XSS($_GET['survey_id']);
$survey_data = survey_manager::get_survey($survey_id);

if (empty($survey_data)) {
//	Display :: display_header(get_lang('SurveyPreview'));
	Display::display_tool_header();
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
//	Display :: display_footer();
	Display::display_tool_footer();
	exit;
}

$urlname = strip_tags(api_substr(api_html_entity_decode($survey_data['title'],ENT_QUOTES,$charset), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}

// breadcrumbs
$interbreadcrumb[] = array ("url" => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array ("url" => "survey.php?survey_id=".$survey_id, "name" => $urlname);

//$htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/jquery-1.3.2.min.js" language="javascript"></script>';
//$htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/customInput.jquery.js" language="javascript"></script>';

// Header
//Display :: display_header(get_lang('SurveyPreview'));
if ($origin == 'learnpath') {
	include_once api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
} else {
  Display::display_tool_header();

// actions bar
echo '<div class="actions">';
echo '<a href="survey.php?'.  api_get_cidreq().'&survey_id='.Security::remove_XSS($_GET['survey_id']).'">'.Display::return_icon('pixel.gif', get_lang('BackTo').' '.strtolower(get_lang('Survey')), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('BackTo').' '.strtolower(get_lang('Survey')).'</a>';
echo '</div>';
}
echo '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/customInput.jquery.js" language="javascript"></script>';
?>

<script type="text/javascript"> 
	// Run the script on DOM ready:
	$(function(){		
		$('input').customInput();
	});
	</script>	
<style>
.custom-checkbox label, 
.custom-radio label {
	display: block;
	position: relative;
	z-index: 1;	
	padding-right: 1em;
	line-height: 15px;
	padding: 0px 5px 25px 30px;
	padding-top:10px;
/*	margin: 0 0 .1em;*/
	cursor: pointer;
}
	</style>
<?php

// start the content div
echo '<div id="content">';
// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($survey_id, true);

// only a course admin is allowed to preview a survey: you are NOT a course admin => error message

/*
if (!api_is_allowed_to_edit(false,true))
{
	Display :: display_error_message(get_lang('NotAllowed'), false);
}*/
// only a course admin is allowed to preview a survey: you are a course admin
if (api_is_platform_admin() || api_is_course_admin() || (api_is_course_admin() && $_GET['isStudentView']=='true') || api_is_allowed_to_session_edit(false,true)) {
	// survey information
	echo '<div class="actions" style="height:auto;min-height:250px;">';
	echo '<div id="survey_title">'.$survey_data['survey_title'].'</div>';
	echo '<div id="survey_subtitle">'.$survey_data['survey_subtitle'].'</div>';

	// displaying the survey introduction
	if (!isset($_GET['show']))
	{
		echo '<div id="survey_content" class="survey_content">'.$survey_data['survey_introduction'].'</div>';
		$limit = 0;
	}

	// displaying the survey thanks message
	if(isset($_POST['finish_survey']))
	{
		echo '<div id="survey_content" class="survey_content"><strong>'.get_lang('SurveyFinished').' </strong>'.$survey_data['survey_thanks'].'</div>';
	//	Display :: display_footer();
             echo '</div></div><div class="actions"></div>';

            exit();
	}

	$sql = "SELECT * FROM $table_survey_question
			WHERE survey_id = '".Database::escape_string($survey_id)."'
			AND	type <> '".Database::escape_string('pagebreak')."' ORDER BY sort ASC";
		$result = Database::query($sql, __FILE__, __LINE__);
		$numrows = Database::num_rows($result);

	if (isset($_GET['show']))
	{
		// Getting all the questions for this page and add them to a multidimensional array where the first index is the page.
		// as long as there is no pagebreak fount we keep adding questions to the page		
		$paged_questions = array();		
		
		$show = $_GET['show'];
		while ($row = Database::fetch_array($result))
		{		
			$paged_questions[] = $row['question_id'];
		}
		
		$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type, survey_question.max_value,
							survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
					FROM $table_survey_question survey_question
					LEFT JOIN $table_survey_question_option survey_question_option
					ON survey_question.question_id = survey_question_option.question_id
					WHERE survey_question.survey_id = '".Database::escape_string($survey_id)."'
					AND survey_question.question_id = ".$paged_questions[$show]."
					ORDER BY survey_question.sort, survey_question_option.sort ASC";
		$result = Database::query($sql, __FILE__, __LINE__);		
		$questions = array();
		while ($row = Database::fetch_array($result))
		{
			// if the type is not a pagebreak we store it in the $questions array
			if($row['type'] <> 'pagebreak')
			{
				$questions[$row['sort']]['question_id'] = $row['question_id'];
				$questions[$row['sort']]['survey_id'] = $row['survey_id'];
				$questions[$row['sort']]['survey_question'] = $row['survey_question'];
				$questions[$row['sort']]['display'] = $row['display'];
				$questions[$row['sort']]['type'] = $row['type'];
				$questions[$row['sort']]['options'][intval($row['option_sort'])] = $row['option_text'];
				$questions[$row['sort']]['maximum_score'] = $row['max_value'];
			}
			// if the type is a pagebreak we are finished loading the questions for this page
			else
			{
				break;
			}			
		}
	}
	// Displaying the form with the questions
	if (isset($_GET['show']))
	{
		$show = (int)$_GET['show'] + 1;
	}
	else
	{
		$show = 0;
	}
	echo '<form id="question" name="question" method="post" action="'.api_get_self().'?'.  api_get_cidreq().'&survey_id='.Security::remove_XSS($survey_id).'&show='.$show.$origin_string.'">';
	if(is_array($questions) && count($questions)>0)
	{
		foreach ($questions as $key=>$question)
		{
			echo '<div class="quiz_content_actions" style="width:95%;margin-left:10px;">';	
			$display = new $question['type'];
			$display->render_question($question);
			echo '</div>';	
		}
	}

	echo '</div>'; // close the action div
	echo '<div style="padding-right:20px;">';
	if (($show < $numrows))
	{
		//echo '<a href="'.api_get_self().'?survey_id='.$survey_id.'&amp;show='.$limit.'">NEXT</a>';
		echo '<br /><button type="submit" name="next_survey_page" class="next">'.get_lang('Validate').'   </button>';
	}
	if ($show >= $numrows)
	{
		echo '<button type="submit" name="finish_survey" class="next">'.get_lang('FinishSurvey').'  </button>';
	}
	echo '</div>';
	echo '</form>';

	/*if (isset($_GET['show']))
	{
		// Getting all the questions for this page and add them to a multidimensional array where the first index is the page.
		// as long as there is no pagebreak fount we keep adding questions to the page
		$questions_displayed = array();
		$paged_questions = array();
		$counter = 0;
		$sql = "SELECT * FROM $table_survey_question
			WHERE survey_id = '".Database::escape_string($survey_id)."'
				ORDER BY sort ASC";
		$result = Database::query($sql, __FILE__, __LINE__);

		while ($row = Database::fetch_array($result))
		{
			if($row['type'] == 'pagebreak') {
				$counter++;
			} else {
				$paged_questions[$counter][] = $row['question_id'];
					}
		}

		if (array_key_exists($_GET['show'], $paged_questions))
		{
			$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type, survey_question.max_value,
							survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
					FROM $table_survey_question survey_question
					LEFT JOIN $table_survey_question_option survey_question_option
					ON survey_question.question_id = survey_question_option.question_id
					WHERE survey_question.survey_id = '".Database::escape_string($survey_id)."'
					AND survey_question.question_id IN (".Database::escape_string(implode(',',$paged_questions[$_GET['show']])).")
					ORDER BY survey_question.sort, survey_question_option.sort ASC";

			$result = Database::query($sql, __FILE__, __LINE__);
			$question_counter_max = Database::num_rows($result);
			$counter = 0;
			$limit=0;
			$questions = array();
			while ($row = Database::fetch_array($result))
			{
				// if the type is not a pagebreak we store it in the $questions array
				if($row['type'] <> 'pagebreak')
				{
					$questions[$row['sort']]['question_id'] = $row['question_id'];
					$questions[$row['sort']]['survey_id'] = $row['survey_id'];
					$questions[$row['sort']]['survey_question'] = $row['survey_question'];
					$questions[$row['sort']]['display'] = $row['display'];
					$questions[$row['sort']]['type'] = $row['type'];
					$questions[$row['sort']]['options'][intval($row['option_sort'])] = $row['option_text'];
					$questions[$row['sort']]['maximum_score'] = $row['max_value'];
				}
				// if the type is a pagebreak we are finished loading the questions for this page
				else
				{
					break;
				}
				$counter++;
			}
		}
	}
	// selecting the maximum number of pages
	$sql = "SELECT * FROM $table_survey_question WHERE type='".Database::escape_string('pagebreak')."' AND survey_id='".Database::escape_string($survey_id)."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	$numberofpages = Database::num_rows($result) + 1;
	// Displaying the form with the questions
	if (isset($_GET['show']))
	{
		$show = (int)$_GET['show'] + 1;
	}
	else
	{
		$show = 0;
	}
	echo '<form id="question" name="question" method="post" action="'.api_get_self().'?survey_id='.Security::remove_XSS($survey_id).'&show='.$show.$origin_string.'">';
	if(is_array($questions) && count($questions)>0)
	{
		foreach ($questions as $key=>$question)
		{
			echo '<div class="quiz_content_actions" style="width:95%;margin-left:10px;">';	
			$display = new $question['type'];
			$display->render_question($question);
			echo '</div>';	
		}
	}

	echo '</div>'; // close the action div
	echo '<div style="padding-right:20px;">';
	if (($show < $numberofpages))
	{
		//echo '<a href="'.api_get_self().'?survey_id='.$survey_id.'&amp;show='.$limit.'">NEXT</a>';
		echo '<br /><button type="submit" name="next_survey_page" class="next">'.get_lang('Validate').'   </button>';
	}
	if ($show >= $numberofpages)
	{
		echo '<button type="submit" name="finish_survey" class="next">'.get_lang('FinishSurvey').'  </button>';
	}
	echo '</div>';
	echo '</form>';*/


} else {
	Display :: display_error_message(get_lang('NotAllowed'), false);
}

// close the content div
echo '</div>';

 // bottom actions bar
echo '<div class="actions">';
echo '</div>';
// Footer
if ($origin != 'learnpath') {
Display::display_tool_footer(); 
}
?>
