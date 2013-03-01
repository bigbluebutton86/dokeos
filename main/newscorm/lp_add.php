<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * Learning Path
 * @package dokeos.learnpath
 */

$this_section=SECTION_COURSES;

api_protect_course_script();

//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default
include('learnpath_functions.inc.php');
include('resourcelinker.inc.php');
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
// name of the language file that needs to be included
$language_file = 'learnpath';

/*
-----------------------------------------------------------
	Header and action code
-----------------------------------------------------------
*/
$currentstyle = api_get_setting('stylesheets');
//$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'css/'.$currentstyle.'/learnpath.css"/>';
//$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="learnpath.css" />'; //will be a merged with original learnpath.css
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="dtree.css" />'; //will be moved
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$is_allowed_to_edit = api_is_allowed_to_edit(null,true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
// using the resource linker as a tool for adding resources to the learning path
if ($action=="add" and $type=="learnpathitem")
{
	 $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ( (! $is_allowed_to_edit) or ($isStudentView) )
{
	error_log('New LP - User not authorized in lp_add.php');
	header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id";
$result=Database::query($sql_query);
$therow=Database::fetch_array($result);

/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students - always available in this case (page only shown to admin)
-----------------------------------------------------------
*/
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>"#", "name"=> get_lang("_add_learnpath"));

Display::display_tool_header(null,'Path');
echo	'<div class="actions">';
//echo	'<a href="lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('scorm.gif',get_lang('ReturnToLearningPaths')).' '.get_lang('ReturnToLearningPaths').'</a>';
echo		'<a href="lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('pixel.gif', get_lang('ReturnToLearningPaths'), array('class' => 'toolactionplaceholdericon toolactionback')).''.get_lang('ReturnToLearningPaths').'</a>';
echo	'</div>';

Display::display_normal_message(get_lang('AddLpIntro'),false);

echo '<div id="content" class="rel" style="height:390px; min-height:390px;">';

if ($_POST AND empty($_REQUEST['learnpath_name']))
{
	Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'), false);
}
echo '<form class="orange" method="post" style="margin:70px 0 0 20%;">';

//	echo '<div class="row"><div class="form_header">'.get_lang('AddLpToStart').'</div></div>';
//	echo '<label for="idTitle"><span class="form_required">*</span> '.get_lang('LPName').'</label>';
	echo '<label for="idTitle" class="title">'.get_lang('LPName').'</label>';
	echo '<input id="idTitle" name="learnpath_name" type="text" size="50" />';
	if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
            //TODO: include language file
            echo '<input type="hidden" name="index_document" value="1"/>'.
             '<input type="hidden" name="language" value="' . api_get_setting('platformLanguage') . '"/>';
            echo '<div class="label"><br/>'.get_lang('SearchKeywords').':</div>';
            echo '<div class="formw"><textarea cols="47" rows="2" name="search_terms"></textarea></div>';
        }
	echo '<button class="save" style="margin-top:15px;" type="submit"/>'.get_lang('CreateLearningPath').'</button>';
//	echo '<input type="submit" value="'.get_lang('CreateLearningPath').'" />';
	echo '<input name="post_time" type="hidden" value="' . time() . '" />';
	
echo '</form>';

//echo '<div class="formw"><span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small></div>';
echo Display::return_icon("navigation/instructor-projection.png", null, array('class'=>"abs", 'style'=>"right:100px; bottom:10px;"));

//ending div#content
echo '</div>';

// bottom actions bar
echo '<div class="actions">';
echo '</div>';

// footer
Display::display_footer();
?>