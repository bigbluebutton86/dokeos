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

// we are not inside a course, so we reset the course id
$cidReset = true;

// including the global file that gets the general configuration, the databases, the languages, ...
include ('../inc/global.inc.php');

// include additional libraries
require (api_get_path ( LIBRARY_PATH ) . 'groupmanager.lib.php');
require_once (api_get_path ( LIBRARY_PATH ) . 'formvalidator/FormValidator.class.php');
require ('functions.php');

// the toolbar set that has to be used
$fck_attribute['ToolbarSet'] = 'Agenda';
$fck_attribute['Height'] = '200px;';

// Setting the section of this file (for the tabs)
$this_section = SECTION_MYAGENDA;

// access control
api_block_anonymous_users();

// add additional javascript and css
Display::javascript(api_get_path ( WEB_CODE_PATH ) . 'inc/lib/javascript/fullcalendar-1.4.5/fullcalendar.min.js');
Display::css(api_get_path ( WEB_CODE_PATH ) . 'inc/lib/javascript/fullcalendar-1.4.5/fullcalendar-dokeos.css');

if ($_GET['action']<>'myadd' AND $_GET['action']<>'myedit'){
	$htmlHeadXtra [] = mycalendar_javascript();
}

// breadcrumbs
$interbreadcrumb[] = array ("url" => "myagenda.php", "name" => get_lang('Agenda'));
switch ($_GET['action']){
	case 'myadd':
		$interbreadcrumb[] = array ("url" => "myagenda.php?action=myadd", "name" => get_lang('AgendaAdd'));
		break;
	case 'myedit':
		$interbreadcrumb[] = array ("url" => "agenda.php?action=myedit&", "name" => get_lang('AgendaEdit'));
		breack;
}

// showing the header
Display::display_header();

// Actions
echo '<div class="actions fc-header">';
echo mycalendar_actions ();
echo '</div>';

echo '<div id="content">';
// Action handling
handle_mycalendar_actions ();

echo '<div id="calendar"></div>';
echo '</div>';

// Displaying the footer
Display :: display_footer();
?>
