<?php

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.group
==============================================================================
*/

// name of the language file that needs to be included
$language_file = array('group','userInfo','work');

// including the global Dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
require_once api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php';

// the section (for the tabs)
$this_section = SECTION_COURSES;

// current group
$current_group = GroupManager :: get_group_properties($_SESSION['_gid']);

// tracking
event_access_tool(TOOL_GROUP);

$nameTools = get_lang('GroupSpace');

// breadcrumbs
$interbreadcrumb[] = array ("url" => "group.php", "name" => get_lang("Groups"));

//Add new chat invitation when users of same group are online
if(!api_is_allowed_to_edit() && !api_is_grouptutor($_course,api_get_session_id(),api_get_user_id())){
$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
$users_list = GroupManager::get_users($current_group['id']);
foreach($users_list as $user_id){	
	$sql = "SELECT * FROM $track_online_table WHERE login_user_id = $user_id AND login_user_id <> ".api_get_user_id();
	$res = Database::query($sql,__FILE__,__LINE__);
	if(Database::num_rows($res) > 0){		
		MessageManager::add_new_chat_invitation(api_get_user_id(),$user_id);
	}
}
}


if(isset($_REQUEST['action'])){
	change_tool_visibility($_REQUEST['action'],$_REQUEST['tool'],$_REQUEST['group_id']);
        header('Location:'.api_get_self().'?'.api_get_cidReq().'&gidReq='.$_SESSION['_gid'].'&group_id='.Security::remove_XSS($_REQUEST['group_id']));
}

// display the header
Display::display_tool_header($nameTools.' '.$current_group['name'],'Group');


function change_tool_visibility($visible,$tool,$group_id){
	$table_group 		= Database::get_course_table(TABLE_GROUP);

	if($visible == 'invisible'){
		$visible = 0;
	}
	else {
		$visible = 1;
	}

	$sql = "UPDATE $table_group SET ".$tool." = $visible WHERE id = ".$group_id;
	Database::query($sql,__FILE__,__LINE__);
}

// Actions
display_actions($current_group);

/*
 * Edit the group
 */
/*if (api_is_allowed_to_edit(false,true) or GroupManager :: is_tutor($_user['user_id'])) {
	isset($origin)?$my_origin = $origin:$my_origin='';
	echo Display::return_icon('edit_link.png', get_lang("EditGroup"))."<a href=\"group_edit.php?".api_get_cidreq()."&origin=$my_origin\">".get_lang("EditGroup")."</a>";
}*/

echo '&nbsp;</div>';

if( isset($_GET['action'])) {
	switch( $_GET['action']) {
		case 'show_msg':
			Display::display_normal_message(Security::remove_XSS($_GET['msg']));
			break;
	}
}

//start the content div
echo '<div id="content">';

/*
-----------------------------------------------------------
	Main Display Area
-----------------------------------------------------------
*/
$course_code = $_course['sysCode'];
$is_course_member = CourseManager :: is_user_subscribed_in_real_or_linked_course($_SESSION['_user']['user_id'], $course_code);

/*
 * Group title and comment
 */
//api_display_tool_title($nameTools.' '.stripslashes($current_group['name']));

/*
 * list all the tutors of the current group
 */
$tutors = GroupManager::get_subscribed_tutors($current_group['id']);

$tutor_info = '';
if (count($tutors) == 0)
{
	$tutor_info = get_lang("GroupNoneMasc");
}
else
{
	isset($origin)?$my_origin = $origin:$my_origin='';
	foreach($tutors as $index => $tutor)
	{
		$image_path = UserManager::get_user_picture_path_by_id($tutor['user_id'],'web',false, true);
		$image_repository = $image_path['dir'];
		$existing_image = $image_path['file'];
		$photo= '<img src="'.$image_repository.$existing_image.'" align="absbottom" alt="'.api_get_person_name($tutor['firstname'], $tutor['lastname']).'"  width="32" height="32" title="'.api_get_person_name($tutor['firstname'], $tutor['lastname']).'" />';
		$tutor_info .= "<div style='margin-bottom: 5px;'><a href='../user/userInfo.php?origin=".$my_origin."&amp;uInfo=".$tutor['user_id']."'>".$photo."&nbsp;".api_get_person_name($tutor['firstname'], $tutor['lastname'])."</a></div>";
	}
}
// Group tutor always is empty due that this feature was removed by this changeset : 19453fe512c3 , see group_edit.php file

/*echo '<div class="actions-message" style="margin-bottom:4px;"><b>'.get_lang("GroupTutors").':</b></div>';
if (!empty($tutor_info)) {
	echo '<div style="margin-left:5px;">'.$tutor_info.'</div>';
}*/
//echo '<br/>';

/*
 * list all the members of the current group
 */

echo '<div class="row"><div class="form_header">'.get_lang("GroupTutors").'</div></div>';

$table = new SortableTable('group_tutors', 'get_number_of_group_tutors', 'get_group_tutor_data', (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);
$my_cidreq=isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$my_origin=isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';
$my_gidreq=isset($_GET['gidReq']) ? Security::remove_XSS($_GET['gidReq']) : '';
$parameters = array('cidReq' => $my_cidreq, 'origin'=> $my_origin, 'gidReq' => $my_gidreq);
$table->set_additional_parameters($parameters);
$table->set_header(0, '');
if (api_is_western_name_order()) {
	$table->set_header(1, get_lang('FirstName'));
	$table->set_header(2, get_lang('LastName'));
} else {
	$table->set_header(1, get_lang('LastName'));
	$table->set_header(2, get_lang('FirstName'));
}
$table->set_header(3, get_lang('Email'),false);
$table->set_header(4, get_lang('Correction'),false);
$table->set_header(5, get_lang('Messages'),false);
$table->set_column_filter(0, 'user_icon_filter');
$table->set_column_filter(3, 'email_filter');
$table->set_column_filter(4, 'assignment_filter');
$table->set_column_filter(5, 'message_filter');
$table->display();

if(api_is_allowed_to_edit() || api_is_grouptutor($_course,api_get_session_id(),api_get_user_id()) || $current_group['category_id'] <> 1){

echo '<div class="row"><div class="form_header">'.get_lang("GroupMembers").'</div></div>';

$table = new SortableTable('group_users', 'get_number_of_group_users', 'get_group_user_data', (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);
$my_cidreq=isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$my_origin=isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';
$my_gidreq=isset($_GET['gidReq']) ? Security::remove_XSS($_GET['gidReq']) : '';
$parameters = array('cidReq' => $my_cidreq, 'origin'=> $my_origin, 'gidReq' => $my_gidreq);
$table->set_additional_parameters($parameters);
$table->set_header(0, '');
if (api_is_western_name_order()) {
	$table->set_header(1, get_lang('FirstName'));
	$table->set_header(2, get_lang('LastName'));
} else {
	$table->set_header(1, get_lang('LastName'));
	$table->set_header(2, get_lang('FirstName'));
}
$table->set_header(3, get_lang('Email'),false);
$table->set_header(4, get_lang('Papers'),false);
$table->set_header(5, get_lang('Messages'),false);
$table->set_column_filter(3, 'email_filter');
$table->set_column_filter(4, 'paper_filter');
$table->set_column_filter(5, 'mess_filter');
$table->set_column_filter(0, 'user_icon_filter');
$table->display();
}

// close the content div
echo '</div>';

// Actions bar
echo '<div class="actions">';
echo '</div>';
// footer
Display::display_footer();

/**
 * Get the number of subscribed users to the group
 *
 * @return integer
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_number_of_group_users()
{
	global $current_group;

	// Database table definition
	$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);

	// query
	$sql = "SELECT count(id) AS number_of_users
				FROM ".$table_group_user."
				WHERE group_id='".Database::escape_string($current_group['id'])."'";
	$result = Database::query($sql,__FILE__,__LINE__);
	$return = Database::fetch_array($result,'ASSOC');
	return $return['number_of_users'];
}

/**
 * Get the details of the users in a group
 *
 * @param integer $from starting row
 * @param integer $number_of_items number of items to be displayed
 * @param integer $column sorting colum
 * @param integer $direction sorting direction
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_group_user_data($from, $number_of_items, $column, $direction)
{
	global $current_group;

	// Database table definition
	$table_group_user 	= Database :: get_course_table(TABLE_GROUP_USER);
	$table_user 		= Database :: get_main_table(TABLE_MAIN_USER);

	// query

	$sql = "SELECT
						user.user_id 	AS col0,
						".(api_is_western_name_order() ?
						"user.firstname 	AS col1,
						user.lastname 	AS col2,"
						:
						"user.lastname 	AS col1,
						user.firstname 	AS col2,"
						)."
						user.user_id		AS col3,
						user.user_id	AS col4,
						user.user_id	AS col5
						FROM ".$table_user." user, ".$table_group_user." group_rel_user
						WHERE group_rel_user.user_id = user.user_id
						AND group_rel_user.group_id = '".Database::escape_string($current_group['id'])."'";
			$sql .= " ORDER BY col$column $direction ";
			$sql .= " LIMIT $from,$number_of_items";

	$return = array ();
	$result = Database::query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_row($result))
	{
		$return[] = $row;
	}
	return $return;
}

/**
 * Get the number of subscribed users to the group
 *
 * @return integer
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_number_of_group_tutors()
{
	global $current_group;

	// Database table definition
	$table_group_tutor = Database :: get_course_table(TABLE_GROUP_TUTOR);

	// query
	$sql = "SELECT count(id) AS number_of_tutors
				FROM ".$table_group_tutor."
				WHERE group_id='".Database::escape_string($current_group['id'])."'";
	$result = Database::query($sql,__FILE__,__LINE__);
	$return = Database::fetch_array($result,'ASSOC');
	return $return['number_of_tutors'];
}

/**
 * Get the details of the users in a group
 *
 * @param integer $from starting row
 * @param integer $number_of_items number of items to be displayed
 * @param integer $column sorting colum
 * @param integer $direction sorting direction
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_group_tutor_data($from, $number_of_items, $column, $direction)
{
	global $current_group;

	// Database table definition
	$table_group_tutor 	= Database :: get_course_table(TABLE_GROUP_TUTOR);
	$table_user 		= Database :: get_main_table(TABLE_MAIN_USER);

	// query

	$sql = "SELECT
						user.user_id 	AS col0,
						".(api_is_western_name_order() ?
						"user.firstname 	AS col1,
						user.lastname 	AS col2,"
						:
						"user.lastname 	AS col1,
						user.firstname 	AS col2,"
						)."
						user.user_id		AS col3,
						group_rel_tutor.group_id		AS col4,
						user.user_id		AS col5
						FROM ".$table_user." user, ".$table_group_tutor." group_rel_tutor
						WHERE group_rel_tutor.user_id = user.user_id
						AND group_rel_tutor.group_id = '".Database::escape_string($current_group['id'])."'";
			$sql .= " ORDER BY col$column $direction ";
			$sql .= " LIMIT $from,$number_of_items";	
		

	$return = array ();
	$result = Database::query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_row($result))
	{
		$return[] = $row;
	}
	return $return;
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function email_filter($user_id)
{
	$table_user 		= Database :: get_main_table(TABLE_MAIN_USER);

	$sql = "SELECT email FROM $table_user WHERE user_id = ".$user_id;
	$rs = Database::query($sql,__FILE__,__LINE__);
	$row = Database::fetch_array($rs);
//	return Display :: encrypted_mailto_link($email, $email);
	return '<a href="new_message.php?'.api_get_cidReq().'&id_session='.api_get_session_id().'&send_to_user='.$user_id.'">'.$row['email'].'</a>';
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function assignment_filter($group_id)
{
	$tbl_work 	  = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$tbl_item_property 	  = Database::get_course_table(TABLE_ITEM_PROPERTY);
		
	$users = GroupManager :: get_subscribed_users($group_id);
	foreach($users as $user){
		$userids[] = $user['user_id'];		
	}
	$users_list = implode(",",$userids);	
	$sql = "SELECT * FROM $tbl_work wrk,$tbl_item_property ip where wrk.id = ip.ref and wrk.filetype='file' and wrk.session_id = ".api_get_session_id()." and ip.insert_user_id IN (".$users_list.")";
	$result = Database::query($sql,__FILE__,__LINE__);
	$num_papers = Database::num_rows($result);
	$corrected = 0;
	while($row = Database::fetch_array($result)){
		if($row['qualificator_id'] == '1'){
			$corrected++;
		}
	}
	
	if(empty($num_papers))$num_papers = 0;
	$assignment = $corrected.'/'.$num_papers;

	return '<center>'.$assignment.'</center>';
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function message_filter($user_id)
{
	// Database table definition
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$tbl_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);

	$sql = "SELECT * FROM $table_message WHERE user_sender_id = ".$user_id." AND msg_status = 4";
	$res = Database::query($sql,__FILE__,__LINE__);
	$num_messages = Database::num_rows($res);

	$forums_of_groups = get_forums_of_group($_SESSION['_gid']);
	foreach ($forums_of_groups as $key => $value) {
		$forum_id = $value['forum_id'];
		$sql = "SELECT * FROM $tbl_forum_thread WHERE forum_id = $forum_id AND thread_poster_id = $user_id";
		$res = Database::query($sql,__FILE__,__LINE__);
		$num_threads = Database::num_rows($res);
	}
	$num_messages = $num_messages + $num_threads;

	return '<center>'.$num_messages.'</center>';	
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function paper_filter($user_id)
{
	$tbl_work 	  = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$tbl_item_property 	  = Database::get_course_table(TABLE_ITEM_PROPERTY);
	
	$sql = "SELECT * FROM $tbl_work WHERE filetype='folder' and session_id = ".api_get_session_id();
	$result = Database::query($sql,__FILE__,__LINE__);
	$num_assignment = Database::num_rows($result);	

	$sql = "SELECT * FROM $tbl_work wrk,$tbl_item_property ip where wrk.id = ip.ref and wrk.filetype='file' and wrk.session_id = ".api_get_session_id()." and ip.insert_user_id = ".$user_id;	
	$result = Database::query($sql,__FILE__,__LINE__);
	$paper_submitted = Database::num_rows($result);
	
	$papers = $paper_submitted.'/'.$num_assignment;

	return '<center>'.$papers.'</center>';
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function mess_filter($user_id)
{
	// Database table definition
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$tbl_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);

	$sql = "SELECT * FROM $table_message WHERE user_sender_id = ".$user_id." AND msg_status = 4";
	$res = Database::query($sql,__FILE__,__LINE__);
	$num_messages = Database::num_rows($res);

	$forums_of_groups = get_forums_of_group($_SESSION['_gid']);
	foreach ($forums_of_groups as $key => $value) {
		$forum_id = $value['forum_id'];
		$sql = "SELECT * FROM $tbl_forum_thread WHERE forum_id = $forum_id AND thread_poster_id = $user_id";
		$res = Database::query($sql,__FILE__,__LINE__);
		$num_threads = Database::num_rows($res);
	}
	$num_messages = $num_messages + $num_threads;

	return '<center>'.$num_messages.'</center>';
}

/**
 * Display a user icon that links to the user page
 *
 * @param integer $user_id the id of the user
 * @return html code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function user_icon_filter($user_id)
{
	global $origin;

	$userinfo=Database::get_user_info_from_id($user_id);
	$image_path = UserManager::get_user_picture_path_by_id($user_id,'web',false, true);
	$image_repository = $image_path['dir'];
	$existing_image = $image_path['file'];
	$photo= '<center><img src="'.$image_repository.$existing_image.'" alt="'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'"  width="22" height="22" title="'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'" /></center>';
	return "<a href='../user/userInfo.php?origin=".$origin."&amp;uInfo=".$user_id."'>".$photo;
}

function display_actions($current_group){
	echo '<div class="actions">';
	echo '<a href="group.php?'.api_get_cidreq().'">'.Display::return_icon('pixel.gif', get_lang('List'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('List').'</a>';
	display_work_action($current_group);
	display_message_action($current_group);
	if($current_group['category_id'] <> 1){
	display_document_action($current_group);
	display_forum_action($current_group);
	display_wiki_action($current_group);
	display_chat_action($current_group);
	}	
}

function display_work_action($current_group){
	if($current_group['work_state'] == 1){
		if(api_is_allowed_to_edit()){
//			echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible','onclick' => 'change_visibility(\'invisible\',\'work_state\')'));
                   echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=invisible&tool=work_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible'))."</a>";
		}
		echo '<a href="../work/work.php?'.api_get_cidreq().'">'.Display::return_icon('pixel.gif',get_lang('Work'),array('class' => 'toolactionplaceholdericon toolactionassignment')).get_lang('Work').'</a>';
	}
	else {
		if(api_is_allowed_to_edit()){
//			echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible','onclick' => 'change_visibility(\'visible\',\'work_state\')'));
                   echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=visible&tool=work_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible'))."</a>";
			echo '<a href="../work/work.php?'.api_get_cidreq().'">'.Display::return_icon('pixel.gif',get_lang('Work'),array('class' => 'toolactionplaceholdericon toolactionassignment invisible')).get_lang('Work').'</a>';
		}
	}
}

function display_message_action($current_group){
	if($current_group['calendar_state'] == 1){
		if(api_is_allowed_to_edit()){
                echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=invisible&tool=calendar_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible'))."</a>";
		}
		//else {
//		echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible','onclick' => 'change_visibility(\'visible\',\'calendar_state\')'));
             echo '<a href="inbox.php?'.api_get_cidreq().'&group_id='.$current_group['id'].'">'.Display::return_icon('pixel.gif',get_lang('Messages'),array('class' => 'toolactionplaceholdericon toolactionmessages')).get_lang('Messages').'</a>';
		//}
	}
    else{
	if(api_is_allowed_to_edit() || GroupManager::is_user_in_group(api_get_user_id(),$current_group['id'])){
        echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=visible&tool=calendar_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible'))."</a>";
	echo '<a href="javascript:">'.Display::return_icon('pixel.gif',get_lang('Messages'),array('class' => 'toolactionplaceholdericon toolactionmessages invisible')).get_lang('Messages').'</a>';
	}

    }
    
}
function display_document_action($current_group){
	if($current_group['doc_state'] == 1){
		if(api_is_allowed_to_edit()){
//			echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible','onclick' => 'change_visibility(\'invisible\',\'doc_state\')'));
                         echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=invisible&tool=doc_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible'))."</a>";
		}
	echo '<a href="../document/document.php?'.api_get_cidreq().'&amp;gidReq='.$current_group['id'].'">'.Display::return_icon('pixel.gif',get_lang('Documents'),array('class' => 'toolactionplaceholdericon toolactiondocument')).get_lang('Documents').'</a>';	
	}
	else {
		if(api_is_allowed_to_edit()){
//		echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible','onclick' => 'change_visibility(\'visible\',\'doc_state\')'));
              echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=visible&tool=doc_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible'))."</a>";
		echo '<a href="../document/document.php?'.api_get_cidreq().'&amp;gidReq='.$current_group['id'].'">'.Display::return_icon('pixel.gif',get_lang('Documents'),array('class' => 'toolactionplaceholdericon toolactiondocument invisible')).get_lang('Documents').'</a>';
		}
	}
}

function display_forum_action($current_group){
	if($current_group['forum_state'] == 1){
	$forums_of_groups = get_forums_of_group($current_group['id']);
	foreach ($forums_of_groups as $key => $value) {

		if(api_is_allowed_to_edit()){
//			echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible','onclick' => 'change_visibility(\'invisible\',\'forum_state\')'));
                         echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=invisible&tool=forum_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible'))."</a>";
		}
	echo '<a href="../forum/viewforum.php?forum='.$value['forum_id'].'&gidReq='.Security::remove_XSS($current_group['id']).'">'.Display::return_icon('pixel.gif',get_lang('Forum'),array('class' => 'toolactionplaceholdericon toolactionforum')).get_lang('Forum').'</a>';
	}
	}
	else {
		if(api_is_allowed_to_edit()){
			$forums_of_groups = get_forums_of_group($current_group['id']);
			foreach ($forums_of_groups as $key => $value) {
//			echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible','onclick' => 'change_visibility(\'visible\',\'forum_state\')'));
                           echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=visible&tool=forum_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible'))."</a>";
			echo '<a href="../forum/viewforum.php?forum='.$value['forum_id'].'&gidReq='.Security::remove_XSS($current_group['id']).'">'.Display::return_icon('pixel.gif',get_lang('Forum'),array('class' => 'toolactionplaceholdericon toolactionforum invisible')).get_lang('Forum').'</a>';			}
		}
	}
}

function display_wiki_action($current_group){
	if($current_group['wiki_state'] == 1){
		if(api_is_allowed_to_edit()){
//	echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible','onclick' => 'change_visibility(\'invisible\',\'wiki_state\')'));
        echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=invisible&tool=wiki_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible'))."</a>";
		}
	echo '<a href="../wiki/index.php?'.api_get_cidreq()."&amp;toolgroup=".$current_group['id'].'">'.Display::return_icon('pixel.gif',get_lang('Wiki'),array('class' => 'toolactionplaceholdericon toolactionwiki')).get_lang('Wiki').'</a>';
	}
	else {
		if(api_is_allowed_to_edit()){
//		echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible','onclick' => 'change_visibility(\'visible\',\'wiki_state\')'));
                     echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=visible&tool=wiki_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible'))."</a>";
		echo '<a href="../wiki/index.php?'.api_get_cidreq()."&amp;toolgroup=".$current_group['id'].'">'.Display::return_icon('pixel.gif',get_lang('Wiki'),array('class' => 'toolactionplaceholdericon toolactionwiki invisible')).get_lang('Wiki').'</a>';
		}
	}
}

function display_chat_action($current_group){
	if($current_group['chat_state'] == 1){
		if(api_is_allowed_to_edit()){
//		echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible','onclick' => 'change_visibility(\'invisible\',\'chat_state\')'));
                
                  echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=invisible&tool=chat_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible'))."</a>";
		}
		if(api_get_course_setting('allow_open_chat_window')==true) {

		echo '<a href="javascript: void(0);" onclick="window.open(\'../chat/chat.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'\',\'window_chat_group_'.$_SESSION['_cid'].'_'.$_SESSION['_gid'].'\',\'height=500, width=930, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')">'.Display::return_icon('pixel.gif',get_lang('Chat'),array('class' => 'toolactionplaceholdericon toolactionchat')).get_lang('Chat').'</a>';
		}
		else {
			echo '<a href="../chat/chat.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'">'.Display::return_icon('pixel.gif',get_lang('Chat'),array('class' => 'toolactionplaceholdericon toolactionchat')).get_lang('Chat').'</a>';
		}
	}
	else {
		if(api_is_allowed_to_edit()){
//		echo Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible','onclick' => 'change_visibility(\'visible\',\'chat_state\')'));
                     echo "<a class='eyes' href='".api_get_self()."?".api_get_cidReq()."&gidReg=".$_SESSION['_gid']."&group_id=".$current_group['id']."&action=visible&tool=chat_state'>".Display::return_icon('pixel.gif','',array('class' => 'actionplaceholdericon actiongrouptoolvisible invisible'))."</a>";
	if(api_get_course_setting('allow_open_chat_window')==true) {
		echo '<a href="javascript: void(0);" onclick="window.open(\'../chat/chat.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'\',\'window_chat_group_'.$_SESSION['_cid'].'_'.$_SESSION['_gid'].'\',\'height=500, width=930, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')">'.Display::return_icon('pixel.gif',get_lang('Chat'),array('class' => 'toolactionplaceholdericon toolactionchat invisible')).get_lang('Chat').'</a>';
	}
	else {
		echo '<a href="../chat/chat.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'">'.Display::return_icon('pixel.gif',get_lang('Chat'),array('class' => 'toolactionplaceholdericon toolactionchat invisible')).get_lang('Chat').'</a>';
	}
		}
	}
}

?>
