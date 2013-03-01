<?php // $Id: $
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

// Additional libraries
require ('functions.php');
require_once (api_get_path ( LIBRARY_PATH ) . 'formvalidator/FormValidator.class.php');

/**
 * @todo: consider moving all these actions into the handle_calendar_actions function and only call handle_calendar_actions() here once
 */
$id = str_replace("course_events","", $_GET['id']);
$id = str_replace("platform","", $id);
$id = str_replace("personal","", $id);

switch ($_GET['action']){
	case 'delete':
		delete_agenda_item($id);
		break;
	case 'visibility':
		change_visibility_agenda_item($id,$_GET['status']);
		break;
	case 'move':
		move_agenda_item($id,$_GET['daydelta'],$_GET['minutedelta']);
		break;
	case 'mymove':
		mymove_agenda_item($id,$_GET['daydelta'],$_GET['minutedelta']);
		break;
	case 'resize':
		resize_agenda_item($id,$_GET['daydelta'],$_GET['minutedelta']);
		break;		
	case 'myresize':
		myresize_agenda_item($id,$_GET['daydelta'],$_GET['minutedelta']);
		break;	
	case 'getevents':
		$events = get_all_course_agenda_items($_GET['coursedb']);
		create_agenda_output($events,$_GET['output']);
		break;
	case 'getallevents':
		$events = get_all_agenda_items();
		create_agenda_output($events,$_GET['output']);
		break;
	case 'getplatformevents':
		$events = get_platform_agenda_items(false, true);
		create_agenda_output($events,$_GET['output']);
		break;
	case 'mydelete':
		delete_myagenda_item($id);
		break;
	case 'platformdelete':
		delete_platformagenda_item($id);
		break;
        case 'platformmove':
		move_platformagenda_item($id,$_GET['daydelta'],$_GET['minutedelta']);
		break;
        case 'platformmoveresize':
		resize_platformagenda_item($id,$_GET['daydelta'],$_GET['minutedelta']);
		break;
}

if (api_get_setting('calendar_google_import')=='true'){
	google_calendar_action_handling();
}
?>
