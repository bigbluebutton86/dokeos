<?php // $Id: course_home.php 22294 2009-07-22 19:27:47Z iflorespaz $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	For licensing terms, see "dokeos_license.txt"

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	http://www.dokeos.com
==============================================================================
*/

/**
==============================================================================
*         HOME PAGE FOR EACH COURSE
*
*	This page, included in every course's index.php is the home
*	page. To make administration simple, the teacher edits his
*	course from the home page. Only the login detects that the
*	visitor is allowed to activate, deactivate home page links,
*	access to the teachers tools (statistics, edit forums...).
*
*	@package dokeos.course_home
==============================================================================
*/

// Name of the language file that needs to be included.
$language_file[] = 'course_home';
$language_file[] = 'widgets';

$use_anonymous = true;

// Inlcuding the global initialization file.
require_once '../../main/inc/global.inc.php';
$css_name = api_get_setting('allow_course_theme') == 'true'?(api_get_course_setting('course_theme', null, true)?api_get_course_setting('course_theme', null, true):api_get_setting('stylesheets')):api_get_setting('stylesheets');

// Check if we have a CSS with tablet support
$css_info = array();
$css_info = api_get_css_info($css_name);
$css_type = !is_null($css_info['type']) ? $css_info['type'] : '';

// everywhere inside the course the banner can be hidden but we have to display this always when we are on the course homepage. 
if ($css_type == 'tablet') { // Display header 2 - - for 2.1 stylesheets
$htmlHeadXtra[]='<script>jQuery(document).ready( function($) { $("#header2").show(); });</script>';
} else { // Display header1, header 2 - for 2.0 stylesheets
$htmlHeadXtra[]='<script>jQuery(document).ready( function($) { $("#header1, #header2").show(); });</script>';
}
// include additional libraries
require 'course_home_functions.php';
include_once(api_get_path(LIBRARY_PATH) . 'course.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'debug.lib.inc.php');

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    //Get information of path
    $info = explode('courses',api_get_self());
    $path_work = substr($info[0],0,strlen($info[0])-1);
} else {
    $path_work = "";
} 

if (!isset($cidReq)) {
	$cidReq = api_get_course_id(); // To provide compatibility with previous systems.
	global $error_msg,$error_no;
	$classError = "init";
	$error_no[$classError][] = "2";
	$error_level[$classError][] = "info";
	$error_msg[$classError][] = "[".__FILE__."][".__LINE__."] cidReq was Missing $cidReq take $dbname;";
}

if (isset($_SESSION['_gid'])) {
	unset($_SESSION['_gid']);
}

// The section for the tabs
$this_section = SECTION_COURSES;


/*
-----------------------------------------------------------
	Constants
-----------------------------------------------------------
*/
define ('TOOL_PUBLIC', 'Public');
define ('TOOL_PUBLIC_BUT_HIDDEN', 'PublicButHide');
define ('TOOL_COURSE_ADMIN', 'courseAdmin');
define ('TOOL_PLATFORM_ADMIN', 'platformAdmin');
define ('TOOL_AUTHORING', 'toolauthoring');
define ('TOOL_INTERACTION', 'toolinteraction');
define ('TOOL_ADMIN', 'tooladmin');
define ('TOOL_ADMIN_PLATEFORM', 'tooladminplatform');
// ('TOOL_ADMIN_PLATFORM_VISIBLE', 'tooladminplatformvisible');
//define ('TOOL_ADMIN_PLATFORM_INVISIBLE', 'tooladminplatforminvisible');
//define ('TOOL_ADMIN_COURS_INVISIBLE', 'tooladmincoursinvisible');
define ('TOOL_STUDENT_VIEW', 'toolstudentview');
define ('TOOL_ADMIN_VISIBLE', 'tooladminvisible');

// variables
$user_id = api_get_user_id();
$course_code = $_course['sysCode'];
$course_info = Database::get_course_info($course_code);

$return_result = CourseManager::determine_course_title_from_course_info($_user['user_id'], $course_info);
$course_title = $return_result['title'];
$course_code = $return_result['code'];

$_course['name'] = $course_title;
$_course['official_code'] = $course_code;

api_session_unregister('toolgroup');

// Is the user allowed here?
if($is_allowed_in_course == false) 
{
	api_not_allowed(true);
}

/*
-----------------------------------------------------------
	SWITCH TO A DIFFERENT HOMEPAGE VIEW
	the setting homepage_view is adjustable through
	the platform administration section
-----------------------------------------------------------
*/

if (api_get_setting('homepage_view') == 'activity' && $css_type != 'tablet') {
	require_once 'activity.php';
} elseif(api_get_setting('homepage_view') == '2column' && $css_type != 'tablet') {
	require_once '2column.php';
} elseif($css_type == 'tablet') {
	require_once 'tablet.php';
} elseif(api_get_setting('homepage_view') == '3column' && $css_type != 'tablet') {
	require_once '3column.php';
} elseif(api_get_setting('homepage_view') == "widget" && $css_type != 'tablet') {
	require_once 'widget.php';
}

// Display the footer
Display::display_footer();
?>