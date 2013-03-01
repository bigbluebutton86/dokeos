<?php
/* For licensing terms, see /dokeos_license.txt */

// name of the language file that needs to be included
$language_file = array ('create_course', 'course_info', 'admin');


// setting the help
$help_content = 'coursesettings';

// including the global Dokeos file
include ('../inc/global.inc.php');

// include additional libraries
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(INCLUDE_PATH)."conf/course_info.conf.php");
require_once (api_get_path(INCLUDE_PATH)."lib/debug.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// section for the tabs
$this_section = SECTION_COURSES;


// variable initialisation
$TABLECOURSE 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$TABLEFACULTY 				= Database :: get_main_table(TABLE_MAIN_CATEGORY);
$TABLECOURSEHOME 			= Database :: get_course_table(TABLE_TOOL_LIST);
$TABLELANGUAGES 			= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
$TABLEBBCONFIG 				= Database :: get_course_table(TOOL_FORUM_CONFIG_TABLE);
$currentCourseID 			= $_course['sysCode'];
$currentCourseRepository 	= $_course["path"];
$is_allowedToEdit 			= $is_courseAdmin || $is_platformAdmin;
$course_setting_table 		= Database::get_course_table(TABLE_COURSE_SETTING);
/*
==============================================================================
		LOGIC FUNCTIONS
==============================================================================
*/
function is_settings_editable()
{
	return $GLOBALS["course_info_is_editable"];
}
$course_code = $_course["sysCode"];
$course_access_settings = CourseManager :: get_access_settings($course_code);

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

if (!$is_allowedToEdit)
{
	api_not_allowed(true);
}

$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
$tbl_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);
$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);

// Get all course categories
$sql = "SELECT code,name FROM ".$table_course_category." WHERE auth_course_child ='TRUE'  OR code = '".Database::escape_string($_course['categoryCode'])."'  ORDER BY tree_pos";
$res = Database::query($sql, __FILE__, __LINE__);

$s_select_course_tutor_name="SELECT tutor_name FROM $tbl_course WHERE code='$course_code'";
$q_tutor=Database::query($s_select_course_tutor_name, __FILE__, __LINE__);
$s_tutor=Database::result($q_tutor,0,"tutor_name");

$s_sql_course_titular="SELECT DISTINCT username, lastname, firstname FROM $tbl_user as user, $tbl_course_user as course_rel_user WHERE (course_rel_user.status='1') AND user.user_id=course_rel_user.user_id AND course_code='".$course_code."'";
$q_result_titulars=Database::query($s_sql_course_titular, __FILE__, __LINE__);

$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
if(Database::num_rows($q_result_titulars)==0){
	$sql="SELECT username, lastname, firstname FROM $tbl_user as user, $tbl_admin as admin WHERE admin.user_id=user.user_id ORDER BY ".$target_name." ASC";
	$q_result_titulars=Database::query($sql, __FILE__, __LINE__);
}

$a_profs[0] = '-- '.get_lang('NoManager').' --';
while ($a_titulars = Database::fetch_array($q_result_titulars)) {
	$s_username = $a_titulars['username'];
	$s_lastname = $a_titulars['lastname'];
	$s_firstname = $a_titulars['firstname'];

	if (api_get_person_name($s_firstname, $s_lastname) == $s_tutor) {
		$s_selected_tutor = api_get_person_name($s_firstname, $s_lastname);
	}
	$s_disabled_select_titular = '';
	if (!$is_courseAdmin) {
		$s_disabled_select_titular = 'disabled=disabled';
	}
	$a_profs[api_get_person_name($s_firstname, $s_lastname)] = api_get_person_name($s_lastname, $s_firstname).' ('.$s_username.')';
}

while ($cat = Database::fetch_array($res))
{
	$categories[$cat['code']] = '('.$cat['code'].') '.$cat['name'];
	ksort($categories);
}


$linebreak = '<div class="row"><div class="label"></div><div class="formw" style="border-bottom:1px dashed grey"></div></div>';

// Build the form
$form = new FormValidator('update_course');

// COURSE SETTINGS
$form->addElement('html','<div class="section">');
$form->addElement('html','<div class="sectiontitle"><a name="coursesettings" id="coursesettings"></a>'.Display::return_icon('pixel.gif', get_lang('CourseSettings'), array('class' => 'toolactionplaceholdericon toolsettings')).' '.get_lang('CourseSettings').'</div>');
$form->addElement('html','<div class="sectioncontent">');
$visual_code=$form->addElement('text','visual_code', get_lang('Code'));
	$visual_code->freeze();
$form->applyFilter('visual_code', 'strtoupper');
//$form->add_textfield('tutor_name', get_lang('Professors'), true, array ('size' => '60'));
$prof = &$form->addElement('select', 'tutor_name', get_lang('Professors'), $a_profs);
$form->applyFilter('tutor_name','html_filter');

$prof -> setSelected($s_selected_tutor);
$form->add_textfield('title', get_lang('Title'), true, array ('size' => '60'));
//$form->applyFilter('title','html_filter');
$form->applyFilter('title','trim');

$form->addElement('select', 'category_code', get_lang('Fac'), $categories);
$form->add_textfield('department_name', get_lang('Department'), false, array ('size' => '60'));
//$form->applyFilter('department_name','html_filter');
$form->applyFilter('department_name','trim');

$form->add_textfield('department_url', get_lang('DepartmentUrl'), false, array ('size' => '60'));
//$form->applyFilter('department_url','html_filter');

$form->addRule('tutor_name', get_lang('ThisFieldIsRequired'), 'required');
$form->addElement('select_language', 'course_language', get_lang('Ln'));
$form->addElement('static', null, '&nbsp;', get_lang('TipLang'));

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
$form->addElement('html','<div style="clear: both;">&nbsp;</div>');
$form->addElement('html','</div>');
$form->addElement('html','</div>');

// COURSE ACCESS
$form->addElement('html','<div class="section">');
$form->addElement('html','<div class="sectiontitle"><a name="coursesaccess" id="coursesaccess"></a>'.Display::return_icon('pixel.gif', get_lang('CourseAccess'), array('class' => 'toolactionplaceholdericon toolactionadmincourse')).' '.get_lang('CourseAccess').'</div>');
$form->addElement('html','<div class="sectioncontent">');
$form->addElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$form->addElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$form->addElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$form->addElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$form->addElement('static', null, null, get_lang("CourseAccessConfigTip"));
$form -> addElement('html',$linebreak);

$form->addElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$form->addElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form -> addElement('html',$linebreak);

$form->addElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$form->addElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form -> addElement('html',$linebreak);

$form->add_textfield('course_registration_password', get_lang('CourseRegistrationPassword'), false, array ('size' => '60'));

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
$form->addElement('html','<div style="clear: both;">&nbsp;</div>');
$form->addElement('html','</div>');
$form->addElement('html','</div>');


// EMAIL NOTIFICATIONS
$form->addElement('html','<div class="section">');
$form->addElement('html','<div class="sectiontitle"><a name="emailnotifications" id="emailnotifications"></a>'.Display::return_icon('pixel.gif', get_lang('EmailNotifications'), array('class' => 'toolactionplaceholdericon toolsettingsnotification')).' '.get_lang('EmailNotifications').'</div>');
$form->addElement('html','<div class="sectioncontent">');

$form->addElement('radio', 'email_alert_to_teacher_on_new_user_in_course', get_lang('NewUserEmailAlert'), get_lang('NewUserEmailAlertEnable'), 1);
$form->addElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null,get_lang('NewUserEmailAlertToTeacharAndTutor'),2);
$form->addElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null, get_lang('NewUserEmailAlertDisable'), 0);
$form -> addElement('html',$linebreak);

$form->addElement('radio', 'email_alert_manager_on_new_doc', get_lang('WorkEmailAlert'), get_lang('WorkEmailAlertActivate'), 1);
$form->addElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertDeactivate'), 0);
$form -> addElement('html',$linebreak);

$form->addElement('radio', 'email_alert_on_new_doc_dropbox', get_lang('DropboxEmailAlert'), get_lang('DropboxEmailAlertActivate'), 1);
$form->addElement('radio', 'email_alert_on_new_doc_dropbox', null, get_lang('DropboxEmailAlertDeactivate'), 0);
$form -> addElement('html',$linebreak);

$form->addElement('radio', 'email_alert_manager_on_new_quiz', get_lang('QuizEmailAlert'), get_lang('QuizEmailAlertActivate'), 1);
$form->addElement('radio', 'email_alert_manager_on_new_quiz', null, get_lang('QuizEmailAlertDeactivate'), 0);

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
$form->addElement('html','<div style="clear: both;">&nbsp;</div>');
$form->addElement('html','</div>');
$form->addElement('html','</div>');


// USER RIGHTS
$form->addElement('html','<div class="section">');
$form->addElement('html','<div class="sectiontitle"><a name="userrights" id="userrights"></a>'.Display::return_icon('pixel.gif', get_lang('UserRights'), array('class' => 'toolactionplaceholdericon toolsettingsuser')).' '.get_lang('UserRights').'</div>');
$form->addElement('html','<div class="sectioncontent">');

$form->addElement('radio', 'allow_user_edit_agenda', get_lang('AllowUserEditAgenda'), get_lang('AllowUserEditAgendaActivate'), 1);
$form->addElement('radio', 'allow_user_edit_agenda', null, get_lang('AllowUserEditAgendaDeactivate'), 0);
$form -> addElement('html',$linebreak);

$form->addElement('radio', 'allow_user_edit_announcement', get_lang('AllowUserEditAnnouncement'), get_lang('AllowUserEditAnnouncementActivate'), 1);
$form->addElement('radio', 'allow_user_edit_announcement', null, get_lang('AllowUserEditAnnouncementDeactivate'), 0);
$form -> addElement('html',$linebreak);

$form->addElement('radio', 'allow_user_image_forum', get_lang('AllowUserImageForum'), get_lang('AllowUserImageForumActivate'), 1);
$form->addElement('radio', 'allow_user_image_forum', null, get_lang('AllowUserImageForumDeactivate'), 0);


$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
$form->addElement('html','<div style="clear: both;">&nbsp;</div>');
$form->addElement('html','</div>');
$form->addElement('html','</div>');

// CHAT SETTINGS
$form->addElement('html','<div class="section">');
$form->addElement('html','<div class="sectiontitle"><a name="chatsettings" id="chatsettings"></a>'.Display::return_icon('pixel.gif', get_lang('ConfigChat'), array('class' => 'toolactionplaceholdericon toolactionchat')).' '.get_lang('ConfigChat').'</div>');
$form->addElement('html','<div class="sectioncontent">');
$form->addElement('radio', 'allow_open_chat_window', get_lang('AllowOpenchatWindow'), get_lang('AllowOpenChatWindowActivate'), 1);
$form->addElement('radio', 'allow_open_chat_window', null, get_lang('AllowOpenChatWindowDeactivate'), 0);
$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
$form->addElement('html','<div style="clear: both;">&nbsp;</div>');
$form->addElement('html','</div>');
$form->addElement('html','</div>');

// COURSE THEME PICKER
if (api_get_setting('allow_course_theme') == 'true')
{
	$form->addElement('html','<div class="section">');
	$form->addElement('html','<div class="sectiontitle"><a name="theme" id="theme"></a>'.Display::return_icon('pixel.gif', get_lang('Theming'), array('class' => 'toolactionplaceholdericon toolsettingstheme')).' '.get_lang('Theming').'</div><div style="clear:both;"></div>');
	$form->addElement('html','<div class="sectioncontent">');

	//Allow Learning path
	$form->addElement('radio', 'allow_learning_path_theme', get_lang('AllowLearningPathTheme'), get_lang('AllowLearningPathThemeAllow'), 1);
	$form->addElement('radio', 'allow_learning_path_theme', null, get_lang('AllowLearningPathThemeDisallow'), 0);
	$form -> addElement('html',$linebreak);

	$form->addElement('select_theme', 'course_theme', get_lang('Theme'));
	$form->applyFilter('course_theme', 'trim');
	$form -> addElement('html',$linebreak);
	$form->addElement('html','</div>');
    
    // is it allowed to edit the course settings?
    if (is_settings_editable())	{
        $form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
        $form->addElement('html','<div style="clear: both;">&nbsp;</div>');
    }else{
        $disabled_output = "disabled";
        $form->freeze();
    }

	$form->addElement('html','</div>');
}


// OTHER SETTINGS (the platform settings that have been delegated)
$form->addElement('html','<div class="section">');
$form->addElement('html','<div class="sectiontitle"><!--<a href="#other" style="float:right;">'.Display::return_icon('top.gif',get_lang('Top')).'</a>--><a name="other" id="other"></a>'.Display::return_icon('pixel.gif', get_lang('Other'), array('class' => 'toolactionplaceholdericon toolsettingsother1')).' '.get_lang('Other').'</div>');
$form->addElement('html','<div class="sectioncontent">');
$form=platform_settings_for_course($form);
$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
$form->addElement('html','<div style="clear: both;">&nbsp;</div>');
$form->addElement('html','</div>');
$form->addElement('html','</div>');

// get all the course information
$all_course_information =  CourseManager::get_course_information($_course['sysCode']);


// Set the default values of the form

$values['title'] = $_course['name'];
$values['visual_code'] = $_course['official_code'];
$values['category_code'] = $_course['categoryCode'];
//$values['tutor_name'] = $_course['titular'];
$values['course_language'] = $_course['language'];
$values['department_name'] = $_course['extLink']['name'];
$values['department_url'] = $_course['extLink']['url'];
$values['visibility'] = $_course['visibility'];
$values['subscribe'] = $course_access_settings['subscribe'];
$values['unsubscribe'] = $course_access_settings['unsubscribe'];
$values['course_registration_password'] =  $all_course_information['registration_code'];
// get send_mail_setting (auth)from table
$values['email_alert_to_teacher_on_new_user_in_course'] = api_get_course_setting('email_alert_to_teacher_on_new_user_in_course');
// get send_mail_setting (work)from table
$values['email_alert_manager_on_new_doc'] = api_get_course_setting('email_alert_manager_on_new_doc');
// get send_mail_setting (dropbox) from table
$values['email_alert_on_new_doc_dropbox'] = api_get_course_setting('email_alert_on_new_doc_dropbox');
// get send_mail_setting (work)from table
$values['email_alert_manager_on_new_quiz'] = api_get_course_setting('email_alert_manager_on_new_quiz');
// get allow_user_edit_agenda from table
$values['allow_user_edit_agenda'] = api_get_course_setting('allow_user_edit_agenda');
// get allow_user_edit_announcement from table
$values['allow_user_edit_announcement'] = api_get_course_setting('allow_user_edit_announcement');
// get allow_user_image_forum from table
$values['allow_user_image_forum'] = api_get_course_setting('allow_user_image_forum');
// get allow_open_chat_window from table
$values['allow_open_chat_window'] = api_get_course_setting('allow_open_chat_window');
// get course_theme from table
$values['course_theme'] = api_get_course_setting('course_theme', null, true);
// get allow_learning_path_theme from table
$values['allow_learning_path_theme'] = api_get_course_setting('allow_learning_path_theme');

$form->setDefaults($values);
// Validate form
if ($form->validate() && is_settings_editable()) {
	$update_values = $form->exportValues();

	// STORING THE OTHER SETTINGS (the platform settings that have been delegated)
	// we do this before we escape all the values (below) because this destroys the arrays
	// idealiter this code would not been there and the escaping would happen in the SQL statement. 
	save_platform_settings_for_course($update_values);	
	
	// escaping all the values
	foreach ($update_values as $index => $value) {
		$update_values[$index] = Database::escape_string($value);
	}

	// Database table definition
	$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);

	// updating the settings that are stored in the dokeos_main.course table
	$sql = "UPDATE $table_course SET title 			= '".Security::remove_XSS($update_values['title'])."',
										 visual_code 	= '".$update_values['visual_code']."',
										 course_language = '".$update_values['course_language']."',
										 category_code  = '".$update_values['category_code']."',
										 department_name  = '".Security::remove_XSS($update_values['department_name'])."',
										 department_url  = '".Security::remove_XSS($update_values['department_url'])."',
										 visibility  = '".$update_values['visibility']."',
										 subscribe  = '".$update_values['subscribe']."',
										 unsubscribe  = '".$update_values['unsubscribe']."',
										 tutor_name     = '".$update_values['tutor_name']."',
										 registration_code = '".$update_values['course_registration_password']."'
									WHERE code = '".$course_code."'";
	
	Database::query($sql, __FILE__, __LINE__);

        
        
        
	//update course_settings table - this assumes those records exist, otherwise triggers an error
	$table_course_setting = Database::get_course_table(TABLE_COURSE_SETTING);
	if($update_values['email_alert_to_teacher_on_new_user_in_course'] != $values['email_alert_to_teacher_on_new_user_in_course']){
		$sql = "UPDATE $table_course_setting SET value = ".(int)$update_values['email_alert_to_teacher_on_new_user_in_course']." WHERE variable = 'email_alert_to_teacher_on_new_user_in_course' ";
		Database::query($sql,__FILE__,__LINE__);
	}
	if($update_values['email_alert_manager_on_new_doc'] != $values['email_alert_manager_on_new_doc']){
		$sql = "UPDATE $table_course_setting SET value = ".(int)$update_values['email_alert_manager_on_new_doc']." WHERE variable = 'email_alert_manager_on_new_doc' ";
		Database::query($sql,__FILE__,__LINE__);
	}
	if($update_values['email_alert_on_new_doc_dropbox'] != $values['email_alert_on_new_doc_dropbox']){
		$sql = "UPDATE $table_course_setting SET value = ".(int)$update_values['email_alert_on_new_doc_dropbox']." WHERE variable = 'email_alert_on_new_doc_dropbox' ";
		Database::query($sql,__FILE__,__LINE__);
	}
	if($update_values['email_alert_manager_on_new_quiz'] != $values['email_alert_manager_on_new_quiz']){
		$sql = "UPDATE $table_course_setting SET value = ".(int)$update_values['email_alert_manager_on_new_quiz']." WHERE variable = 'email_alert_manager_on_new_quiz' ";
		Database::query($sql,__FILE__,__LINE__);
	}
        
	/*if($update_values['allow_user_edit_agenda'] != $values['allow_user_edit_agenda']){
		$sql = "UPDATE $table_course_setting SET value = '".$update_values['allow_user_edit_agenda']."' WHERE variable = 'allow_user_edit_agenda' ";
		Database::query($sql,__FILE__,__LINE__);
	}*/
	if($update_values['allow_user_edit_announcement'] != $values['allow_user_edit_announcement']){
		$sql = "UPDATE $table_course_setting SET value = '".$update_values['allow_user_edit_announcement']."' WHERE variable = 'allow_user_edit_announcement' ";
		Database::query($sql,__FILE__,__LINE__);
	}
	if($update_values['allow_user_image_forum'] != $values['allow_user_image_forum']){
		$sql = "UPDATE $table_course_setting SET value = '".$update_values['allow_user_image_forum']."' WHERE variable = 'allow_user_image_forum' ";
		Database::query($sql,__FILE__,__LINE__);
	}
	if($update_values['allow_open_chat_window'] != $values['allow_open_chat_window']){
		$sql = "UPDATE $table_course_setting SET value = '".$update_values['allow_open_chat_window']."' WHERE variable = 'allow_open_chat_window' ";
		Database::query($sql,__FILE__,__LINE__);
	}
	if($update_values['course_theme'] != $values['course_theme']){
		$sql = "UPDATE $table_course_setting SET value = '".$update_values['course_theme']."' WHERE variable = 'course_theme' ";
		Database::query($sql,__FILE__,__LINE__);
	}
	if($update_values['allow_learningpath_theme'] != $values['allow_learning_path_theme']){
		$sql = "UPDATE $table_course_setting SET value = '".$update_values['allow_learning_path_theme']."' WHERE variable = 'allow_learning_path_theme' ";
		Database::query($sql,__FILE__,__LINE__);
	}

	$cidReset = true;
	$cidReq = $course_code;
	unset($_SESSION['_cid']);
	header('Location: infocours.php?action=show_message&cidReq='.$course_code);
	exit;
}


// display the header
Display :: display_tool_header(get_lang('ModifInfo'));

// display the tool title
//api_display_tool_title(get_lang('ModifInfo'));


if (isset ($_GET['action']) && $_GET['action'] == 'show_message'){
	Display :: display_normal_message(get_lang('ModifDone'));
}

// actions bar
echo '<div class="actions">';
echo '<a href="#coursesettings">'.Display::return_icon('pixel.gif', get_lang('CourseSettings'), array('class' => 'toolactionplaceholdericon toolsettings')).' '.get_lang('CourseSettings').'</a>';
echo '<a href="#coursesaccess">'.Display::return_icon('pixel.gif', get_lang('CourseAccess'), array('class' => 'toolactionplaceholdericon toolactionadmincourse')).' '.get_lang('CourseAccess').'</a>';
echo '<a href="#emailnotifications">'.Display::return_icon('pixel.gif', get_lang('EmailNotifications'), array('class' => 'toolactionplaceholdericon toolsettingsnotification')).' '.get_lang('EmailNotifications').'</a>';
echo '<a href="#userrights">'.Display::return_icon('pixel.gif', get_lang('UserRights'), array('class' => 'toolactionplaceholdericon toolsettingsuser')).' '.get_lang('UserRights').'</a>';
echo '<a href="#chatsettings">'.Display::return_icon('pixel.gif', get_lang('ConfigChat'), array('class' => 'toolactionplaceholdericon toolactionchat')).' '.get_lang('ConfigChat').'</a>';
if (api_get_setting('allow_course_theme') == 'true'){
	echo '<a href="#theme">'.Display::return_icon('pixel.gif', get_lang('Theming'), array('class' => 'toolactionplaceholdericon toolsettingstheme')).' '.get_lang('Theming').'</a>';
}
// also display an action (internal anchor) to the other settings if some platform settings have been delegated
// this $_settingdelegation variable is filled in global.inc.php
if (!empty($_settingdelegation))
{
	echo '<a href="#other">'.Display::return_icon('pixel.gif', get_lang('Other'), array('class' => 'toolactionplaceholdericon toolsettingsother1')).' '.get_lang('Other').'</a>';
}
echo '</div>';

// start the content div
echo '<div id="content">';

// Display the form
$form->display();
	if ($showDiskQuota && $currentCourseDiskQuota != "")
	{
?>
<table>
	<tr>
	<td><?php echo get_lang("DiskQuota"); ?>&nbsp;:</td>
	<td><?php echo $currentCourseDiskQuota; ?> <?php echo $byteUnits[0] ?></td>
	</tr>
	<?php

	}
	if ($showLastEdit && $currentCourseLastEdit != "" && $currentCourseLastEdit != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('LastEdit'); ?>&nbsp;:</td>
	<td><?php echo format_locale_date($dateTimeFormatLong,strtotime($currentCourseLastEdit)); ?></td>
	</tr>
	<?php

	}
	if ($showLastVisit && $currentCourseLastVisit != "" && $currentCourseLastVisit != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('LastVisit'); ?>&nbsp;:</td>
	<td><?php echo format_locale_date($dateTimeFormatLong,strtotime($currentCourseLastVisit)); ?></td>
	</tr>
	<?php

	}
	if ($showCreationDate && $currentCourseCreationDate != "" && $currentCourseCreationDate != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('CreationDate'); ?>&nbsp;:</td>
	<td><?php echo format_locale_date($dateTimeFormatLong,strtotime($currentCourseCreationDate)); ?></td>
	</tr>
	<?php

	}
	if ($showExpirationDate && $currentCourseExpirationDate != "" && $currentCourseExpirationDate != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('ExpirationDate'); ?>&nbsp;:</td>
	<td>
	<?php

		echo format_locale_date($dateTimeFormatLong, strtotime($currentCourseExpirationDate));
		echo "<br />".get_lang('OrInTime')." : ";
		$nbJour = (strtotime($currentCourseExpirationDate) - time()) / (60 * 60 * 24);
		$nbAnnees = round($nbJour / 365);
		$nbJour = round($nbJour - $nbAnnees * 365);
		switch ($nbAnnees)
		{
			case "1" :
				echo $nbAnnees, " an ";
				break;
			case "0" :
				break;
			default :
				echo $nbAnnees, " ans ";
		};
		switch ($nbJour)
		{
			case "1" :
				echo $nbJour, " jour ";
				break;
			case "0" :
				break;
			default :
				echo $nbJour, " jours ";
		}
		if ($canReportExpirationDate)
		{
			echo " -&gt; <a href=\"".$urlScriptToReportExpirationDate."\">".get_lang('PostPone')."</a>";
		}
?>
</td>
</tr>
</table>
<?php

	}

// close the content div
echo '</div>';

// display the footer
Display::display_footer();


/**
 * This function displays the quickform lines for the platform settings that were passed to the course admin
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version november 2009
 * @todo other setting form elements (input fields, dropdown lists, ...)
*/
function platform_settings_for_course($form)
{
	global $_settingdelegation, $values;
	
	// first we get all the options for the settings that are delegated to the course
	$delegated_settings_variables=array();
	foreach ($_settingdelegation as $key=>$setting_info){
		$delegated_settings_variables[] = $key;
	}
	
	// database table definition
	$table_setting_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
	
	// we select all the options for the delegated settings and add them to the options 
	$sql = "SELECT * FROM $table_setting_options WHERE variable IN ('".implode("','",$delegated_settings_variables)."')";
	$result = Database::query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result, 'ASSOC')) {
		$_settingdelegation[$row['variable']]['options'][] = array('value'=>$row['value'],'display_text'=>$row['display_text']);
	}	
	

	// the settings that the platformadmin decided that the teacher could set them
	foreach ($_settingdelegation as $key=>$row_course_settings)
	{
		$label = get_lang($row_course_settings['title']);
		
		switch ($row_course_settings['type'])
		{
			case 'radio':
			default:	
			{
				// if there are no options we should not display it here
				if(count($row_course_settings['options']) < 1)
				{
					continue;
				}			
				
				// we add every option as a radio button and we display all the radio buttons as a group
				$group = array();
				foreach ($row_course_settings['options'] as $key=>$option)
				{
					$group[] =& $form->createElement('radio', 'delegated_platform_setting_'.$row_course_settings['variable'], '', get_lang($option['display_text']), $option['value']);
				}
				$form->addGroup($group, 'course_setting_'.$row_course_settings['variable'], $label, '<br/>'. "\n", false);
				
				// setting the default value for this delegated setting (this can be either the platform setting if the course admin has not changed anythign yet or 
				// the course setting if the course admin has selected a different option
				// $values is set global
				$values['delegated_platform_setting_'.$row_course_settings['variable']] = api_get_setting($row_course_settings['variable']);
				break;
			}
			case 'checkbox';
			{
				$group = array ();
				$row_defaults = api_get_setting($row_course_settings['variable']);
				foreach ($row_course_settings['subkey'] as $key=>$option)
				{
					$element = & $form->createElement('checkbox', $option, '', get_lang($row_course_settings['subkeytext'][$key]));
					if ($row_defaults[$option] == 'true' && !$form->isSubmitted())
					{
						$element->setChecked(true);
					}
					$group[] = $element;
				}
				$form->addGroup($group, 'delegated_platform_setting_'.$row_course_settings['variable'], $label, '<br />'."\n", true);
				break;
			}
		}
	}

	return $form;
}

function save_platform_settings_for_course($values)
{
	global $_settingdelegation;
	
	// Database table definition
	$table_course_setting = Database::get_course_table(TABLE_COURSE_SETTING);
	
	// as a first step we delete all the previously saved delegated platform settings
	// these have category = platform
	$sql = "DELETE FROM $table_course_setting WHERE category='platform'";
	$result = Database::query($sql, __FILE__, __LINE__);

	// secondly we add all the settings that are delegated and that are of the type = checkbox
	// and that do not appear in the $values array as unchecked
	foreach ($_settingdelegation as $key=>$tempsetting){
		if ($tempsetting['type'] == 'checkbox'){
			foreach ($tempsetting['subkey'] as $index=>$subkey){
				if (!array_key_exists($subkey,$values['delegated_platform_setting_'.$key])){
					// we have to add this one because apparently the checkbox was not checked

                                                // clean old variables
                                                $check = Database::query("SELECT id FROM $table_course_setting WHERE variable = '".Database::escape_string($key)."'");
                                                if (Database::num_rows($check) > 0) {
                                                    Database::query("DELETE FROM $table_course_setting WHERE variable = '".Database::escape_string($key)."'");                                                    
                                                }
                                    
						$sql = "INSERT INTO $table_course_setting (variable,subkey,type,category,value) VALUES (
								'".Database::escape_string($key)."',
								'".Database::escape_string($subkey)."',
								'checkbox',
								'platform',
								'false'
								)";
						$result = Database::query($sql, __FILE__, __LINE__);
				}
			}
		}
	}
	
	// now we loop through all the values of the form and search for the fields that start with 'delegated_platform_setting_'
	foreach ($values as $key=>$value){
		// it is a delegated platform setting, so we save it as such in the table $table_course_setting
		if (strstr($key,'delegated_platform_setting_')){
		
			// we recover the variable of the delegated platform setting
			$variable = str_replace('delegated_platform_setting_','',$key);
			
                        // clean old variables
                        $check = Database::query("SELECT id FROM $table_course_setting WHERE variable = '".Database::escape_string($variable)."'");
                        if (Database::num_rows($check) > 0) {
                            Database::query("DELETE FROM $table_course_setting WHERE variable = '".Database::escape_string($variable)."'");                                                    
                        }
                        
			// if the type is a radio button then we can simply insert it (because the form only contains one value)
			if ($_settingdelegation[$variable]['type'] == 'radio'){
				$sql = "INSERT INTO $table_course_setting (variable,subkey,type,category,value,title,comment,subkeytext) VALUES (
						'".Database::escape_string($variable)."',
						null,
						'".Database::escape_string($_settingdelegation[$variable]['type'])."',
						'platform',
						'".Database::escape_string($value)."',
						'".Database::escape_string($_settingdelegation[$variable]['title'])."',
						'".Database::escape_string($_settingdelegation[$variable]['comment'])."',
						null
						)";
				$result = Database::query($sql, __FILE__, __LINE__);				
			} elseif($_settingdelegation[$variable]['type'] == 'checkbox') {
				// if the type is a checkbox then we have to take all the existing subkeys into account. The $value will in this case be an array
				if (!empty($value)){
					foreach ($value as $subkey=>$subkeyvalue){
						// the value of checkboxes are stgred as true or false
						if ($subkeyvalue == 0){
							$subkeyvalue_sql = 'false';
						} else {
							$subkeyvalue_sql = 'true';
						}
					
						$sql = "INSERT INTO $table_course_setting (variable,subkey,type,category,value,title,comment,subkeytext) VALUES (
								'".Database::escape_string($variable)."',
								'".Database::escape_string($subkey)."',
								'".Database::escape_string($_settingdelegation[$variable]['type'])."',
								'platform',
								'".Database::escape_string($subkeyvalue_sql)."',
								'".Database::escape_string($_settingdelegation[$variable]['title'])."',
								'".Database::escape_string($_settingdelegation[$variable]['comment'])."',
								null
								)";
						$result = Database::query($sql, __FILE__, __LINE__);
					}		
				}
			}
		} else {
			// it is not a delegated platform setting so we do nothing
			continue;
		}
	}
	
}
?>

