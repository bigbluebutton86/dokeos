<?php
// $Id: add_course.php 20588 2009-05-13 12:34:18Z pcool $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
* This script allows professors and administrative staff to create course sites.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Roan Embrechts, refactoring
* @package dokeos.create_course
==============================================================================
*/

// name of the language file that needs to be included
$language_file = "create_course";

//delete the globals["_cid"] we don't need it here
$cidReset = true; // Flag forcing the 'current course' reset

// including the global file
include ('../inc/global.inc.php');

// help
$help_content = get_help('createcourse');

// section for the tabs
$this_section=SECTION_COURSES;

// include configuration file
include (api_get_path(CONFIGURATION_PATH).'add_course.conf.php');

// include additional libraries
include_once (api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(CONFIGURATION_PATH).'course_info.conf.php');

$interbreadcrumb[] = array('url'=>api_get_path(WEB_PATH).'user_portal.php', 'name'=> get_lang('MyCourses'));
// Displaying the header
$tool_name = get_lang('CreateSite');

if (api_get_setting('allow_users_to_create_courses')=='false' && !api_is_platform_admin()) {
	api_not_allowed(true);
}
$htmlHeadXtra[] = '<script>
$(document).ready(function (){
  $("#add_training_id").click(function() {
  var get_title = $("#training_title_id").val();
  get_title = $.trim(get_title);
  var title_length = get_title.length;
    if (title_length > 0) {
      return true;
    } else {
      $("#training_title_id").attr("value", "");
      $("#training_title_id").focus();
      return false;
    }
  });
});
</script>';

$htmlHeadXtra[]='<script>
 function redirect_page() {
      window.location="'.api_get_path(WEB_PATH).'user_portal.php";
    }
</script>';
//href="'.api_get_path(WEB_PATH).'user_portal.php"

// Display the header
Display :: display_header($tool_name);

// start the content div
echo '<div id="content" class="maxcontent">';

// Check access rights
if(!api_is_session_admin()){
	if (!api_is_allowed_to_create_course()) {
		Display :: display_error_message(get_lang("NotAllowed"));
		Display::display_footer();
		exit;
	}
}
// Get all course categories
$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);

$dbnamelength = strlen($_configuration['db_prefix']);
//Ensure the database prefix + database name do not get over 40 characters
$maxlength = 40 - $dbnamelength;

// Build the form
$categories = array();
$form = new FormValidator('add_course');
// form title
$form->addElement('header', '', $tool_name);
//title
$form->add_textfield('title',get_lang('CourseName'),false,array('size'=>'60','class'=>'focus', 'id'=>'training_title_id'));
$form->applyFilter('title', 'html_filter');
$form->addRule('title',get_lang('Required'),'required',$maxlength);

$form->addElement('static',null,null,get_lang('Ex'));
$categories_select = $form->addElement('select', 'category_code', get_lang('Fac'), $categories);
$form->applyFilter('category_code', 'html_filter');

CourseManager::select_and_sort_categories($categories_select);
$form->addElement('static',null,null, get_lang('TargetFac'));

$form->add_textfield('wanted_code', get_lang('Code'),false,array('size'=>'$maxlength','maxlength'=>$maxlength));
$form->applyFilter('wanted_code', 'html_filter');
$form->addRule('wanted_code',get_lang('Max'),'maxlength',$maxlength);
//$form->addElement('hidden','wanted_code','');

$titular= &$form->add_textfield('tutor_name', get_lang('Professors'),null,array('size'=>'60','disabled'=>'disabled'));
$form->addElement('static',null,null,get_lang('ExplicationTrainers'));
//$form->applyFilter('tutor_name', 'html_filter');

$form->addElement('select_language', 'course_language', get_lang('Ln'));
$form->applyFilter('select_language', 'html_filter');

$form->addElement('style_submit_button', null, get_lang('CreateCourseArea'), 'class="add" id="add_training_id"');
$form->add_progress_bar();

// Set default values
if (isset($_user["language"]) && $_user["language"]!="") {
	$values['course_language'] = $_user["language"];
} else {
	$values['course_language'] = api_get_setting('platformLanguage');
}

$values['tutor_name'] = api_get_person_name($_user['firstName'], $_user['lastName'], null, null, $values['course_language']);
$form->setDefaults($values);
// Validate the form
if ($form->validate()) {
	$course_values = $form->exportValues();
	$wanted_code = $course_values['wanted_code'];
	$tutor_name = $course_values['tutor_name'];
	$category_code = $course_values['category_code'];
	$title = $course_values['title'];
	$course_language = $course_values['course_language'];

	if (trim($wanted_code) == '') {
		$wanted_code = generate_course_code(api_substr($title,0,$maxlength));
	}

	$keys = define_course_keys($wanted_code, "", $_configuration['db_prefix']);

	$sql_check = sprintf('SELECT * FROM '.$table_course.' WHERE visual_code = "%s"',Database :: escape_string($wanted_code));
	$result_check = Database::query($sql_check,__FILE__,__LINE__); //I don't know why this api function doesn't work...
	if ( Database::num_rows($result_check)<1 ) {
		if (sizeof($keys)) {
			$visual_code = $keys["currentCourseCode"];
			$code = $keys["currentCourseId"];
			$db_name = $keys["currentCourseDbName"];
			$directory = $keys["currentCourseRepository"];
			$expiration_date = time() + $firstExpirationDelay;
			prepare_course_repository($directory, $code);
			update_Db_course($db_name);
			$pictures_array=fill_course_repository($directory);
			fill_Db_course($db_name, $directory, $course_language,$pictures_array);
			register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, api_get_user_id(), $expiration_date);
		}
        $link = api_get_path(WEB_COURSE_PATH).$directory.'/';
		$message = get_lang('JustCreated');
		$message .= ' <a href="'.$link.'"><b>'.$title."</b></a>";
	/*	$message .= "<br /><br /><br />";
		$message .= '<a class="bottom-link" href="'.api_get_path(WEB_PATH).'user_portal.php">'.get_lang('Enter').'</a>';
		Display :: display_confirmation_message($message,false);
		echo '<div style="float:right; margin:0px; padding:0px;">' .
				'<a class="bottom-link" href="'.api_get_path(WEB_PATH).'user_portal.php">'.get_lang('Enter').'</a>' .
			 '</div>';*/
		echo '<div class="actions"><div style="float: left; text-align: left; width: 60%;height:200px;" class="quiz_content_actions"><br/>'.$message.'</div>';
                // Display image
                echo '<div style="float: right; text-align: right; width: 30%;margin-right:20px;">';
                echo Display::return_icon('KnockOnWood.png',get_lang('CreateSite'));
                echo '</div><div class="clear"></div></div>';
               
		echo '<button onclick="redirect_page()" class="back"  type="button"  name="submit_save" id="submit_save">'.get_lang('Enter').'</button><br/><br/>';
                
	} else {
		//Display :: display_error_message(get_lang('CourseCodeAlreadyExists'),false);
		echo get_lang('CourseCodeAlreadyExists');
		$form->display();
		//echo '<p>'.get_lang('CourseCodeAlreadyExistExplained').'</p>';
	}

} else {
	// Display the form
	$form->display();
	Display::display_normal_message(get_lang('Explanation'));
}

// close the content div
echo '</div>';

// display the footer
Display :: display_footer();
?>
