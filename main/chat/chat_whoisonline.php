<?php // $Id: chat_whoisonline.php,v 1.12.2.1 2005/09/02 08:18:31 pcool Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt"
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Shows the list of connected users
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

define('FRAME','online');
$language_file = array ('chat');

include('../inc/global.inc.php');
include('../inc/lib/course.lib.php');
include('../inc/lib/usermanager.lib.php');

$course = api_get_course_id();
$group_id = intval($_SESSION['_gid']);
$session_id = intval($_SESSION['id_session']);
$session_condition = api_get_session_condition($session_id);
$group_condition = " AND to_group_id = '$group_id'";

$extra_condition = '';
if (!empty($group_id)) {
	$extra_condition = $group_condition;
} else {
	$extra_condition = $session_condition;
}

if (!empty($course))
{
	$showPic=intval($_GET['showPic']);
	$tbl_course_user	= Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_course_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_course	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_chat_connected	= Database::get_course_table(CHAT_CONNECTED_TABLE,$_course['dbName']);

	$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";	
	$result=Database::query($query,__FILE__,__LINE__);

	list($pseudoUser)=Database::fetch_array($result);

	$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
	$isMaster=$is_courseAdmin?true:false;

	$date_inter=date('Y-m-d H:i:s',time()-120);

	$Users = array();

	if(empty($session_id))
	{
		$query="SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri,t3.status FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_course_user t3 WHERE t1.user_id=t2.user_id AND t3.user_id=t2.user_id AND t3.course_code = '".$_course['sysCode']."' AND t2.last_connection>'".$date_inter."' $extra_condition ORDER BY username";		
		$result=Database::query($query,__FILE__,__LINE__);
		$Users=Database::store_result($result);		
	}
	else
	{
		// select learners
		$query="SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session_course_user t3 WHERE t1.user_id=t2.user_id AND t3.id_user=t2.user_id AND t3.id_session = '".$session_id."' AND t3.course_code = '".$_course['sysCode']."' AND t2.last_connection>'".$date_inter."' $extra_condition ORDER BY username";
		$result=Database::query($query,__FILE__,__LINE__);
		while($learner = Database::fetch_array($result))
		{
			$Users[$learner['user_id']] = $learner;
		}

		// select session coach
		$query="SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session t3 WHERE t1.user_id=t2.user_id AND t3.id_coach=t2.user_id AND t3.id = '".$session_id."' AND t2.last_connection>'".$date_inter."' $extra_condition ORDER BY username";
		$result=Database::query($query,__FILE__,__LINE__);
		if($coach = Database::fetch_array($result))
			$Users[$coach['user_id']] = $coach;

		// select session course coach
		$query="SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri 
				FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session_course_user t3 
				WHERE t1.user_id=t2.user_id 
				AND t3.id_user=t2.user_id AND t3.status=2 
				AND t3.id_session = '".$session_id."' 
				AND t3.course_code = '".$_course['sysCode']."' 
				AND t2.last_connection>'".$date_inter."' $extra_condition ORDER BY username";

		$result=Database::query($query,__FILE__,__LINE__);		
		$course_coachs = array();
		while ($coachs = Database::fetch_array($result)) {
			//$course_coachs[] = $coachs['user_id'];	
			$Users[$coachs['user_id']] = $coachs;
		}

		//if($coach = Database::fetch_array($result))
			//$Users[$coach['user_id']] = $coach;
	}

	$user_id=$enreg['user_id'];
	include('header_frame.inc.php');
	?>
	<div style="padding-left:0px;<?php if (empty($open_chat_window))
	{echo 'padding-top:0px;';}?>">
	<!--<div class="actions" style="width:230px;height:370px;overflow:auto;">-->
	<?php
	if (!ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {	
	echo '<div class="actions" style="margin-left:10px;width:235px;height:458px;overflow:auto;">';
	}
	else
	{
	echo '<div class="actions" style="margin-left:10px;width:235px;height:428px;overflow:auto;>';
	}
	?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" class="data_table">
	<tr><th colspan="2"><?php echo get_lang("Connected"); ?></th></tr>
	<?php
	foreach($Users as $enreg)
	{
		
		if(empty($session_id))
		{
			$status=$enreg['status'];
		}
		else
		{
			if(CourseManager::is_course_teacher($enreg['user_id'],$_SESSION['_course']['id'])) $status=1; else $status=5;
		}

	$user_image=UserManager::get_user_picture_path_by_id($enreg['user_id'],'web',false,true);
	$file_url=$user_image['dir'].$user_image['file'];

	?>
    <tr>
	  <td width="1%" valign="top">&nbsp;</td>
	  <td width="99%"><p style="padding-top:17px;">
	  <?php /* if($status == 1) echo Display::return_icon('teachers.gif', get_lang('Teacher'),array('height' => '14')).' '; else echo Display::return_icon('students.gif', get_lang('Student'), array('height' => '14')); */?>
	  <a <?php if($status == 1) echo 'class="master"';// ?> name="user_<?php echo $enreg['user_id']; ?>" href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq();?>&showPic=<?php if($showPic == $enreg['user_id']) echo '0'; else echo $enreg['user_id']; ?>#user_<?php echo $enreg['user_id']; ?>"><img src="<?php echo $file_url;?>" border="0" width="50" alt="" /><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']); ?></a></p></td>
	</tr>
	<?php

	if($showPic == $enreg['user_id']): ?>
	<tr>
	  <td colspan="2" align="center"><img src="<?php echo $file_url;?>" border="0" width="100" alt="" /></td>
	</tr>
	<?php endif; ?>
	<?php
	}
	unset($Users);
	?>
	</table>
	</div></div>
	<?php
	}
	include('footer_frame.inc.php');
?>
