<?php
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
*                  HOME PAGE FOR EACH COURSE
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
$language_file = 'course_home';

// include the global Dokeos file
require '../../main/inc/global.inc.php';

// includ the functions
require 'course_home_functions.php';

switch($_GET['action']){
	case 'make_visible':
		change_tool_visibility($_GET['id'],1);
		break;
	case 'make_invisible':
		change_tool_visibility($_GET['id'],0);
		break;
}
?>
