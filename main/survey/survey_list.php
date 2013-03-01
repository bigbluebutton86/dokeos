<?php
/* For licensing terms, see /dokeos_license.txt */

/**
*	@package dokeos.survey
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
*	@author Julio Montoya Armas <gugli100@gmail.com>, Dokeos: Personality Test modification and rewriting large parts of the code
* 	@version $Id: survey_list.php 21933 2009-07-09 06:08:22Z ivantcholakov $
*
* 	@todo use quickforms for the forms
*/

// Language files that should be included
$language_file = 'survey';

// WTF is this and why this kind of hackery? 
if (!isset ($_GET['cidReq'])){
    $_GET['cidReq']='none'; // prevent sql errors
    $cidReset = true;
}
// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once('survey.lib.php');
require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
require_once api_get_path(LIBRARY_PATH)  . 'searchengine.lib.php';
$htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/jquery-1.4.2.min.js" language="javascript"></script>';
$add_lp_param = "";
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
  $add_lp_param = "&lp_id=".$lp_id;
}
//Tracking
event_access_tool(TOOL_SURVEY);
	
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false,true)) //coach can see this
{
	// Display header
	Display::display_tool_header();

	// start the content div
	echo '<div id="content">';

	SurveyUtil::survey_list_user($_user['user_id']);

	// close the content div
	echo '</div>';

if(api_is_allowed_to_edit())
	{
echo '<div class="actions">';
echo '&nbsp;';
echo '</div>';
	}
	// Display the footer
	Display::display_footer();
	exit;
}

$extend_rights_for_coachs = api_get_setting('extend_rights_for_coach_on_survey');

// Database table definitions
$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 			= Database :: get_main_table(TABLE_MAIN_USER);

// language variables
if (isset ($_GET['search']) && $_GET['search'] == 'advanced')
{
	$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
	$tool_name = get_lang('SearchASurvey');
}
else
{
	$tool_name = get_lang('SurveyList');
}


// Display header
Display::display_tool_header();

// Display toot title
//api_display_tool_title($tool_name);

// Tool introduction
Display::display_introduction_section('survey', 'left');

// Action handling: deleting a survey
if (isset($_GET['action']) AND $_GET['action'] == 'delete' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
        
	// getting the information of the survey (used for when the survey is shared)
	$survey_data = survey_manager::get_survey($_GET['survey_id']);
	if(api_is_course_coach() && intval($_SESSION['id_session']) != $survey_data['session_id'])
	{ // the coach can't delete a survey not belonging to his session
		api_not_allowed();
		exit;
	}
	// if the survey is shared => also delete the shared content
	if (is_numeric($survey_data['survey_share']))
	{
		survey_manager::delete_survey($survey_data['survey_share'], true);
	}
	$return = survey_manager :: delete_survey($_GET['survey_id']);
	if ($return)
	{
		Display :: display_confirmation_message(get_lang('SurveyDeleted'), false);
	}
	else
	{
		Display :: display_error_message(get_lang('ErrorOccurred'), false);
	}
}

if(isset($_GET['action']) && $_GET['action'] == 'empty')
{
	$mysession = api_get_session_id();
	if ( $mysession != 0 ) {
		if(!((api_is_course_coach() || api_is_platform_admin()) && api_is_element_in_the_session(TOOL_SURVEY,intval($_GET['survey_id'])))) {
			// the coach can't empty a survey not belonging to his session
			api_not_allowed();
			exit;
		}
	} else {
		if (!(api_is_course_admin() || api_is_platform_admin())) {
			api_not_allowed();
			exit;
		}
	}
	$return = survey_manager::empty_survey(intval($_GET['survey_id']));
	if ($return)
	{
		Display :: display_confirmation_message(get_lang('SurveyEmptied'), false);
	}
	else
	{
		Display :: display_error_message(get_lang('ErrorOccurred'), false);
	}
}

// Action handling: performing the same action on multiple surveys
if ($_POST['action'])
{
	if (is_array($_POST['id']))
	{
		foreach ($_POST['id'] as $key=>$value)
		{
			// getting the information of the survey (used for when the survey is shared)
			$survey_data = survey_manager::get_survey($value);
			// if the survey is shared => also delete the shared content
			if (is_numeric($survey_data['survey_share']))
			{
				survey_manager::delete_survey($survey_data['survey_share'], true);
			}
                        
                        if(api_get_setting('search_enabled') == true) {
                            //delete search engine keyword
                            $searchkey = new SearchEngineManager();
                            $searchkey->idobj = $value;
                            $searchkey->course_code = api_get_course_id();
                            $searchkey->tool_id = TOOL_SURVEY;
                            $searchkey->deleteKeyWord();
                            
                            survey_manager::search_engine_delete($value);
                        }
			// delete the actual survey
			survey_manager::delete_survey($value);
                        
		}
		Display :: display_confirmation_message(get_lang('SurveysDeleted'), false);
	}
	else
	{
		Display :: display_error_message(get_lang('NoSurveysSelected'), false);
	}
}
echo $extended_rights_for_coachs;
echo '<div class="actions">';
if (!api_is_course_coach() || $extend_rights_for_coachs=='true')
{
	// Action links
	echo  '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=add">'.Display::return_icon('pixel.gif', get_lang('CreateNewSurvey'),array("class" => "toolactionplaceholdericon toolactionSurveyAddNew")).get_lang('CreateNewSurvey').'</a> ';
}
//echo '<a href="survey_all_courses.php">'.get_lang('CreateExistingSurvey').'</a> ';
echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;search=advanced">'.Display::return_icon('pixel.gif', get_lang('Search'),array("class" => "toolactionplaceholdericon toolactionSurveySearch")) .get_lang('Search').'</a>';
echo '</div>';

// start the content div
echo '<div id="content">';

// Action handling: searching
if (isset ($_GET['search']) AND $_GET['search'] == 'advanced')
{
	SurveyUtil::display_survey_search_form();
	echo '<br />';
}

//Load main content
if (api_is_course_coach() && $extend_rights_for_coachs=='false')
	SurveyUtil::display_survey_list_for_coach();
else
	SurveyUtil::display_survey_list();

// close the content div
echo '</div>';
if(api_is_allowed_to_edit())
	{
echo '<div class="actions">';
echo '&nbsp;';
echo '</div>';
	}

// Display the footer
Display::display_footer();

/* Bypass functions to make direct use from SortableTable possible */

/** 
 *
 */
function get_number_of_surveys()
{
	return SurveyUtil::get_number_of_surveys();
}

/** 
 * @param
 * @param
 * @param
 * @param
 * @author
 */
function get_survey_data($from, $number_of_items, $column, $direction)
{
	return SurveyUtil::get_survey_data($from, $number_of_items, $column, $direction);
}

/** 
 * @param integer $survey_id the id of the survey
 * @author
 */
function modify_filter($survey_id)
{
	return SurveyUtil::modify_filter($survey_id);
}

/** 
 * @author
 */
function get_number_of_surveys_for_coach()
{
	return SurveyUtil::get_number_of_surveys_for_coach();
}

/** 
 * @param
 * @param
 * @param
 * @param
 * @author
 */
function get_survey_data_for_coach($from, $number_of_items, $column, $direction)
{
	return SurveyUtil::get_survey_data_for_coach($from, $number_of_items, $column, $direction);
}

/** 
 * @param integer $survey_id the id of the survey
 * @author
 */
function modify_filter_for_coach($survey_id)
{
	return SurveyUtil::modify_filter_for_coach($survey_id);
}

function anonymous_filter($anonymous)
{
	return SurveyUtil::anonymous_filter($anonymous);
}
