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
*	@package dokeos.calendar
==============================================================================
*/

// setting the language file
$language_file = 'agenda';

// including the global file that gets the general configuration, the databases, the languages, ...
include ('../inc/global.inc.php');

// additional libraries
require (api_get_path ( LIBRARY_PATH ) . 'groupmanager.lib.php');
require_once (api_get_path ( LIBRARY_PATH ) . 'formvalidator/FormValidator.class.php');
require ('functions.php');

// including additional javascripts
$htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path ( WEB_CODE_PATH ) . 'inc/lib/javascript/fullcalendar-1.4.5/fullcalendar.min.js" language="javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path ( WEB_CODE_PATH ) . 'inc/lib/javascript/jquery.expander.js" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="' . api_get_path ( WEB_CODE_PATH ) . 'inc/lib/javascript/fullcalendar-1.4.5/fullcalendar-dokeos.css" />';

// javascript code for teachers... and students
if ($_GET['action'] <> 'detail' AND $_GET['action'] <> 'add' AND $_GET['action'] <> 'edit')
{
	$htmlHeadXtra[] = calendar_javascript();
}
// google calendar import
if (api_get_setting('calendar_google_import')=='true'){
	$htmlHeadXtra[] = google_calendar_additional_js_libraries();
}

// the toolbar set that has to be used
if(!api_is_allowed_to_edit()){
	$fck_attribute['ToolbarSet'] = 'AgendaStudent';
}
else{
	$fck_attribute['ToolbarSet'] = 'Agenda';
}
$fck_attribute['Height'] = '200px;';
$fck_attribute['Width'] = '650px;';

// Setting the section of this file (for the tabs)
$this_section = SECTION_COURSES;

// access rights
api_protect_course_script ();

// breadcrumbs
$interbreadcrumb[] = array ("url" => "agenda.php", "name" => get_lang('Agenda'));
switch ($_GET['action']){
	case 'add':
		$interbreadcrumb[] = array ("url" => "agenda.php", "name" => get_lang('AgendaAdd'));
		break;
	case 'edit':
		$interbreadcrumb[] = array ("url" => "agenda.php", "name" => get_lang('AgendaEdit'));
		break;
	case 'detail':
		$interbreadcrumb[] = array ("url" => "agenda.php", "name" => get_lang('AgendaDetail'));
		break;
}

// action handling before anything is displayed
handle_header_calendar_actions();

// Displaying the header
Display::display_tool_header ();
//Display::display_tool_header ();

// Tool introduction
Display::display_introduction_section ( TOOL_CALENDAR_EVENT );

// Actions
echo '<div class="actions fc-header">';
echo calendar_actions ();
echo '</div>';

// start the content div
echo '<div id="content" style="width:938px;">'; // style definition unfortunately needed because content is added through javascript

// Action handling
handle_calendar_actions ();

echo '<div id="calendar"></div>';

// Close the content div
echo '</div>';


// secondary actions
echo '<div class="actions">';
echo '</div>';

// Displaying the footer
Display::display_footer ();
?>
