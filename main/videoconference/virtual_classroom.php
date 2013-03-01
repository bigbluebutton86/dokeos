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
*                  Video Conference Virtual Class Room
*
*
*
*	@package dokeos.main.videoconference
==============================================================================
*/

/**
 *
 * @author Claudio Montoya <claudio.montoya@dokeos.com>, Ene al Cubo, Mexico
 * @since December 2010
 * @version 1.0
 */

// use anonymous mode when accessing this course tool
$use_anonymous = true;

// including the global Dokeos file
include('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once(api_get_path(LIBRARY_PATH).'display.lib.php');
require_once('bbb_api.php');
require_once('bbb_conf.php'); 

// setting the tabs
$this_section=SECTION_COURSES;

// name of the tool
//$nameTools = get_lang('Video Conference');

// setting the breadcrumbs
if (!empty($_SESSION['toolgroup'])){
	$_clean['toolgroup']=(int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
}

// access restrictions
api_protect_course_script();

// tracking
event_access_tool(TOOL_CONFERENCE);

$videoconference_type = 'CR';
$username = (api_get_user_info(api_get_user_id()));
$username = $username['firstname'].' '.$username['lastname'];
$meeting_id = $coursesurl['host'].'-'.$_SESSION["_course"]["name"].'-'.$videoconference_type;
$exit_url = parse_url($url);
$exit_url = $exit_url['scheme'].'://'.$exit_url['host'];

$password = '';

if (api_is_allowed_to_edit() == 1){
	$password = $moderator_password;
}else{
	$password = $viewer_password;
}

$meeting_info = BigBlueButton::getMeetingInfo( $meeting_id, $moderator_password, $url, $salt );

if (preg_match("/".$username."/i",$meeting_info)){
  echo "There is a username called ".$username." in use in this video conference room";
  exit;
}

$response = BigBlueButton::createMeetingArray($username, $meeting_id, null, $moderator_password, $viewer_password, $salt, $url, $exit_url);

//Analyzes the bigbluebutton server's response
if(!$response){//If the server is unreachable
	$msg = 'Unable to join the meeting. Please check the url of the video conference server AND check to see if the video conference server is running.';
}
else if( $response['returncode'] == 'FAILED' ) { //The meeting was not created
	if($response['messageKey'] == 'checksumError'){
		$msg =  'A checksum error occured. Make sure you entered the correct salt.';
	}
	else{
		$msg = $response['message'];
	}
}
else{ //The meeting was created, and the user will now be joined

  

	$bbb_joinURL = BigBlueButton::joinURL($meeting_id, $username,$password, $salt, $url);
  header("location: ".$bbb_joinURL);
	?>
	<!-- script type="text/javascript"> //window.location = "<?php echo $bbb_joinURL; ?>";</script --><?php
	return;
}