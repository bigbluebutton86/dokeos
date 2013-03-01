<?php

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.announcements
* 	@author Frederik Vermeire <frederik.vermeire@pandora.be>, UGent University Internship
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
==============================================================================
*/

/*
functionality that has been removed and will not be available in Dokeos 2.0
* survey announcement (badly coded)
* change the visibility of the announcement
* move announcement up or down

functionality that has been removed and has to be re-added for Dokeos 2.0
* send by email + configuration setting for the platform admin: never, always, let course admin decide
* configruation of the number of items that have to appear (jcarousel)
*/

// variables that will be converted into platform settings
// Maximum title messages to display
$maximum 	= '12';

// Language files that should be included
$language_file[] = 'announcements';
$language_file[] = 'group';
$language_file[] = 'survey';

// setting the help
$help_content = 'announcements';

// use anonymous mode when accessing this course tool
$use_anonymous = true;

// including the global Dokeos file
include('../inc/global.inc.php');

// including additional libraries
require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');
require_once(api_get_path(INCLUDE_PATH).'conf/mail.conf.php');
require_once(api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once(api_get_path(LIBRARY_PATH).'fckeditor/fckeditor.php');
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once (api_get_path ( LIBRARY_PATH ) . 'formvalidator/FormValidator.class.php');

// setting the tabs
$this_section=SECTION_COURSES;

// name of the tool
$nameTools = get_lang('Announcement');

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
event_access_tool(TOOL_ANNOUNCEMENT);

// Display the header
//Display::display_header($nameTools,"Announcements");

$htmlHeadXtra[] = '    
    <script type="text/javascript" language="javascript">
       $(function() {      
           var theRHeight = $("#announcements_list").innerHeight();
           var theLHeight = $(".announcement_contentdiv").innerHeight();
           if (theLHeight > theRHeight) {
             $("#announcements_list").css("height", theLHeight - 20);  
           } else {
             $(".announcement_contentdiv").css("height", theRHeight);
           }       
       });
    </script>
';

Display :: display_tool_header();

// setting the configuration for the announcements
if(!api_is_allowed_to_edit()){
	$fck_attribute['ToolbarSet'] = 'AgendaStudent';
}
else{
	$fck_attribute['ToolbarSet'] = 'Agenda';
}
$fck_attribute['Height'] = '200px;';
$fck_attribute['Width'] = '550px;';

// inserting an anchor (top) so one can jump back to the top of the page
echo '<a name="top"></a>';

// Display the tool title
//api_display_tool_title($nameTools);

// Display the tool introduction
Display::display_introduction_section(TOOL_ANNOUNCEMENT);

// MAIL 
/*
if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {

				if ($_POST['email_ann'] && empty($_POST['onlyThoseMails'])) {

				  	$sent_to=sent_to("announcement", $insert_id);
				    $userlist   = $sent_to['users'];
				    $grouplist  = $sent_to['groups'];

			        // groepen omzetten in users
			        if ($grouplist) {

						$grouplist = "'".implode("', '",$grouplist)."'";	//protect individual elements with surrounding quotes
						$sql = "SELECT user_id
								FROM $tbl_groupUser gu
								WHERE gu.group_id IN (".$grouplist.")";


						$groupMemberResult = Database::query($sql,__FILE__,__LINE__);


						if ($groupMemberResult) {
							while ($u = Database::fetch_array($groupMemberResult)) {
								$userlist [] = $u ['user_id']; // complete the user id list ...
							}
						}
					}


				    if (is_array($userlist)) {
				    	$userlist = "'".implode("', '", array_unique($userlist) )."'";

				    	// send to the created 'userlist'
					    $sqlmail = "SELECT user_id, lastname, firstname, email
						       					 FROM $tbl_user
						       					 WHERE user_id IN (".$userlist.")";
				    } else if (empty($_POST['not_selected_form'])) {
			    		if(empty($_SESSION['id_session']) || api_get_setting('use_session_mode')=='false') {
				    		// send to everybody
				    		$sqlmail = "SELECT user.user_id, user.email, user.lastname, user.firstname
					                     FROM $tbl_course_user, $tbl_user
					                     WHERE course_code='".Database::escape_string($_course['sysCode'])."'
					                     AND course_rel_user.user_id = user.user_id";
			    		} else {
			    			$sqlmail = "SELECT user.user_id, user.email, user.lastname, user.firstname
					                     FROM $tbl_user
										 INNER JOIN $tbl_session_course_user
										 	ON $tbl_user.user_id = $tbl_session_course_user.id_user
											AND $tbl_session_course_user.course_code = '".$_course['id']."'
											AND $tbl_session_course_user.id_session = ".intval($_SESSION['id_session']);

			    		}
			    	}

					if ($sqlmail!='') {
						$result = Database::query($sqlmail,__FILE__,__LINE__);


						$db_name = Database::get_course_table(TABLE_MAIN_SURVEY);
						while ($myrow = Database::fetch_array($result)) {


							$emailSubject = "[" . $_course['official_code'] . "] " . $emailTitle;

	                            // intro of the email: receiver name and subject
								$mail_body = api_get_person_name($myrow["lastname"], $myrow["firstname"], null, PERSON_NAME_EMAIL_ADDRESS)."<br />\n".stripslashes($emailTitle)."<br />";
								// make a change for absolute url
	        					$newContent = str_replace(array('src=\"../../', 'src="../../'),'src=\"'.api_get_path(WEB_PATH).'', $newContent);
	                            // main part of the email
	                            $mail_body .= trim(stripslashes($newContent));
	                            // signature of email: sender name and course URL after -- line
	                            $mail_body .= "<br />-- <br />";
	                            $mail_body .= api_get_person_name($_user['firstName'], $_user['lastName'], null, PERSON_NAME_EMAIL_ADDRESS)." \n";
	                            $mail_body .= "<br /> \n<a href=\"".api_get_path(WEB_COURSE_PATH).$_course['id']."\">";
	                            $mail_body .= $_course['official_code'].' '.$_course['name'] . "</a>";

								//set the charset and use it for the encoding of the email - small fix, not really clean (should check the content encoding origin first)
								//here we use the encoding used for the webpage where the text is encoded (ISO-8859-1 in this case)
								
								$recipient_name	= api_get_person_name($myrow["lastname"], $myrow["firstname"], null, PERSON_NAME_EMAIL_ADDRESS);
		                        $mailid = $myrow["email"];
		                        $sender_name = api_get_person_name($_SESSION['_user']['lastName'], $_SESSION['_user']['firstName'], null, PERSON_NAME_EMAIL_ADDRESS);
		                        $sender_email = $_SESSION['_user']['mail'];
								$data_file = array();
								if (!empty($_FILES['user_upload'])) {
									$courseDir = $_course['path'].'/upload/announcements/';
									$sys_course_path = api_get_path(SYS_COURSE_PATH);
									$sql = 'SELECT path, filename FROM '.$tbl_announcement_attachment.'
									  	    WHERE announcement_id  = "'.$insert_id.'"';
									$result = Database::query($sql, __FILE__, __LINE__);
									$row = Database::fetch_array($result);
									$data_file = array('path' => $sys_course_path.$courseDir.$row['path'],
													   'filename' => $row['filename']);
								}								
								api_mail_html($recipient_name, $mailid, stripslashes($emailSubject), $mail_body, $sender_name, $sender_email, null, $data_file);

					}

				} // $email_ann
			} // end condition token
		}	// isset

*/


// display the actions
display_announcements_actions();

switch ($_GET['action']){
	case 'delete': 
		delete_announcement($_GET['id']);
		break;
	//case 'edit': 
	//	$input_values = get_announcements($_GET['id']);
	//	$dest = get_announcement_dest($_GET['id']);
	//	$input_values['to_group_id'] = $dest['to_group_id'];
	//	$input_values['to_user_id'] = $dest['to_user_id'];
	//	display_announcement_form($input_values);
	//	break;
	case 'delete_all':
		delete_all_announcements();
		break;
	//case 'view':
	//	display_announcement();
	//	break;
	//case '':
		//if (api_is_allowed_to_edit(false,true)){
		//	display_announcement_form();
		//} else {
		//	display_announcement();
		//}
}


ob_start();
announcements_action_handling();
$content2 = ob_get_clean();
ob_end_flush();
ob_start();
display_announcements_list();
$content1 = ob_get_clean();
ob_end_flush();

// not the cleanest way but surely the easiest way
echo '<table width="100%">';
echo '<tr>';
echo '<td valign="top" width="250px">';
echo '<div id="announcements_list" class="rounded">';
echo $content1;
echo '</div>';
echo '</td>';
echo '<td valign="top">';
// start the content div
echo '<div id="content" class="announcement_contentdiv">';
// display the form, the announcements, ...
echo $content2;
//announcements_action_handling();
// close the content div
echo '</div>';
echo '</td>';
echo '</tr>';
echo '</table>';


// bottom actions bar
echo '<div class="actions">';
	if (api_is_allowed_to_edit() && number_of_announcements() > 1) {
		echo "<span>";
		echo '<div style="float:left;">';
		echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=delete_all\" onclick=\"javascript:if(!confirm('".get_lang("ConfirmYourChoice")."')) return false;\">".Display::return_icon('pixel.gif',get_lang('AnnouncementDeleteAll'),array('class'=>'actionplaceholdericon actiondelete')).get_lang('AnnouncementDeleteAll')."</a>\n";	
		echo '</div>';
		echo "</span>";		
	}	// if announcementNumber > 1
echo '</div>';



// display the footer
Display::display_footer();

/*
 * ========================================
 * end of HTML - only functions after there
 * ========================================
 */

function display_announcements_list(){
	$announcements = get_announcements();
	
	foreach ($announcements as $key=>$announcement){?>
<script type="text/javascript" language="javascript">
   $(function(){
      $("input[name='send_to[receivers]']").bind("click",function(){
         $miAncho=$(".announcement_contentdiv").height();   
         $("#announcements_list").css("height",$miAncho);
      });
   });
</script>
		<?php echo '<div id="announcement'.$announcement['id'].'" class="announcement_list_item">';
		echo '<a href="announcements.php?action=view&amp;ann_id='.$announcement['id'].'" title="'.$announcement['title'].'">';
		echo '<span class="announcements_list_date">'.$announcement['announcement_date'].'</span>';
		echo shorten($announcement['title'],25);
		echo '</a>';
		echo '</div>';
	}
}

function get_announcements($announcement_id = '', $returntop = false){
	global $_user, $_course;

	// Database table definition
	$table_announcement		= Database::get_course_table(TABLE_ANNOUNCEMENT);
	$table_item_property  	= Database::get_course_table(TABLE_ITEM_PROPERTY);
	$table_user          	= Database::get_main_table(TABLE_MAIN_USER);

	// teachers see all the announcements (even invisible ones)
	if(api_is_allowed_to_edit(false,true))  {
		$sql="SELECT
			announcement.*, toolitemproperties.*, DATE_FORMAT(insert_date,'%b %d, %Y') AS announcement_date, user.firstname, user.lastname
			FROM $table_announcement announcement, $table_item_property toolitemproperties, $table_user user
			WHERE announcement.id = toolitemproperties.ref
			AND toolitemproperties.tool='announcement'
			AND toolitemproperties.visibility!='2'
			AND toolitemproperties.insert_user_id = user.user_id
			$condition_session
			GROUP BY toolitemproperties.ref
			ORDER BY display_order DESC";				
	} else {
	// students only get to see the visible announcements
		// get all the groups of the user
		$group_memberships=GroupManager::get_group_ids($_course['dbName'], api_get_user_id());

		// if the user is allowed to edit their own announcement
		if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
			$cond_user_id = " AND (toolitemproperties.lastedit_user_id = '".api_get_user_id()."' OR ( toolitemproperties.to_user_id='".api_get_user_id()."'" .
					"OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships)."))) ";
		} else {
			$cond_user_id = " AND ( toolitemproperties.to_user_id='".api_get_user_id()."'" .
					"OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).")) ";
		}

		// the user is member of several groups => display personal announcements AND his group announcements AND the general announcements
		if (is_array($group_memberships) && count($group_memberships)>0) {
			$sql="SELECT
				announcement.*, toolitemproperties.*, DATE_FORMAT(insert_date,'%b %d, %Y') AS announcement_date, user.firstname, user.lastname
				FROM $table_announcement announcement, $table_item_property toolitemproperties, $table_user user
				WHERE announcement.id = toolitemproperties.ref
				AND toolitemproperties.tool='announcement'
				AND toolitemproperties.visibility='1'
				AND toolitemproperties.insert_user_id = user.user_id
				$cond_user_id
				$condition_session
				GROUP BY toolitemproperties.ref
				ORDER BY display_order DESC";			
		} else {
			// the user is not member of any group
			// this is an identified user => show the general announcements AND his personal announcements
			if ($_user['user_id']) {

				if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
					$cond_user_id = " AND (toolitemproperties.lastedit_user_id = '".api_get_user_id()."' OR ( toolitemproperties.to_user_id='".$_user['user_id']."' OR toolitemproperties.to_group_id='0')) ";
				} else {
					$cond_user_id = " AND ( toolitemproperties.to_user_id='".$_user['user_id']."' OR toolitemproperties.to_group_id='0') ";
				}


				$sql="SELECT
					announcement.*, toolitemproperties.*, DATE_FORMAT(insert_date,'%b %d, %Y') AS announcement_date, user.firstname, user.lastname
					FROM $table_announcement announcement, $table_item_property toolitemproperties, $table_user user
					WHERE announcement.id = toolitemproperties.ref
					AND toolitemproperties.tool='announcement'
					AND toolitemproperties.visibility='1'
					AND toolitemproperties.insert_user_id = user.user_id
					$cond_user_id
					$condition_session
					GROUP BY toolitemproperties.ref
					ORDER BY display_order DESC";
			} else {

				if (api_get_course_setting('allow_user_edit_announcement')) {
					$cond_user_id = " AND (toolitemproperties.lastedit_user_id = '".api_get_user_id()."' OR toolitemproperties.to_group_id='0') ";
				} else {
					$cond_user_id = " AND toolitemproperties.to_group_id='0' ";
				}

				// the user is not identiefied => show only the general announcements
				$sql="SELECT
					announcement.*, toolitemproperties.*, DATE_FORMAT(insert_date,'%b %d, %Y') AS announcement_date, user.firstname, user.lastname
					FROM $table_announcement announcement, $table_item_property toolitemproperties, $table_user user
					WHERE announcement.id = toolitemproperties.ref
					AND toolitemproperties.tool='announcement'
					AND toolitemproperties.visibility='1'
					AND toolitemproperties.to_group_id='0'
					AND toolitemproperties.insert_user_id = user.user_id
					$condition_session
					GROUP BY toolitemproperties.ref
					ORDER BY display_order DESC";					
			}
		}
	}
	$result = Database::query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result,ASSOC)){
		// if $returntop is true this means that we want the top (first) item only (the one with the highest display_order);
		if ($returntop) {
			return $row;
		} else {
			$return[$row['id']] = $row;
		}
	}

	// if a certain announcement id is asked we only return the information of this id
	if ($announcement_id <> '') {
				return $return[$announcement_id];
	} else {
		return $return;
	}
}

function announcements_action_handling(){
	switch ($_GET['action']){
		case 'add': 
			display_announcement_form();
			break;
		case 'delete': 
			delete_announcement($_GET['id']);
			display_announcement();
			//display_announcement_form();
			break;
		case 'edit': 
			$input_values = get_announcements($_GET['id']);
			$dest = get_announcement_dest($_GET['id']);
			$input_values['to_group_id'] = $dest['to_group_id'];
			$input_values['to_user_id'] = $dest['to_user_id'];
			display_announcement_form($input_values);
			break;
		case 'delete_all':
			delete_all_announcements();
			display_announcement_form();
			break;
		case 'view':
			display_announcement();
			break;
		case '':
			if (api_is_allowed_to_edit(false,true)){
				display_announcement_form();
			} else {
				display_announcement();
			}
	}
}


/** 
 * This function gets all the groups and users (or combination of these) that can see this announcement
 * This is mainly used when editing
 */
function get_announcement_dest($announcement_id){
	// Database table definition
	$table_item_property  		= Database::get_course_table(TABLE_ITEM_PROPERTY);

	$sql = "SELECT * FROM $table_item_property WHERE tool='".TOOL_ANNOUNCEMENT."' AND ref='".Database::escape_string($announcement_id)."'";
	$result = Database::query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result,ASSOC)){
		if ($row['to_group_id'] <> 0){
			$to_group_id[]=$row['to_group_id'];
		}
		if (!empty($row['to_user_id'])){
			$to_user_id[]=$row['to_user_id'];
		}
	}

	return array('to_group_id'=>$to_group_id, 'to_user_id'=>$to_user_id);
}

function delete_all_announcements(){
	// Database table definition
	$table_item_property  		= Database::get_course_table(TABLE_ITEM_PROPERTY);

	if (api_is_allowed_to_edit(false,true)){
		$sql = "UPDATE $table_item_property SET visibility='2' WHERE tool='".TOOL_ANNOUNCEMENT."'";
		Database::query($sql,__FILE__,__LINE__);
		Display::display_confirmation_message (get_lang('AnnouncementDeleted'));
	}
}

function delete_announcement ($announcement_id){
	// Database table definition
	$table_item_property  		= Database::get_course_table(TABLE_ITEM_PROPERTY);

	if (is_numeric($announcement_id)){
		$sql = "UPDATE $table_item_property SET visibility='2' WHERE tool='".TOOL_ANNOUNCEMENT."' and ref='".Database::escape_string($announcement_id)."'";
		Database::query($sql,__FILE__,__LINE__);
		Display::display_confirmation_message (get_lang('AnnouncementDeleted'));
	}
}

function display_announcement_form($input_values){
	if (api_is_allowed_to_edit(false,true) || api_get_course_setting('allow_user_edit_announcement')){
		// initiate the object
		$form = new FormValidator ( 'announcement_form', 'post', $_SERVER ['REQUEST_URI'] );
		$renderer = & $form->defaultRenderer();
	
		// the header for the form
		if ($_GET ['action'] == 'add') {
			//$form->addElement ( 'header', '', get_lang ( 'AnnouncementAdd' ) );
		}
		if ($_GET ['action'] == 'edit') {
			$form->addElement ( 'header', '', get_lang ( 'EditAnnouncement' ) );
		}
		else
		{
			$form->addElement ( 'header', '', get_lang ( 'AnnouncementAdd' ) );
		}
	
		// a hidden form with the id of the announcement we are editing
		if ($input_values and is_numeric ( $input_values ['id'] )) {
			$form->addElement ( 'hidden', 'announcement_id', $input_values['id'] );
		}

		if (api_is_allowed_to_edit(false,true) OR api_is_allowed_to_session_edit(false,true)){
			// The receivers: groups
			$course_groups = CourseManager::get_group_list_of_course(api_get_course_id(), intval($_SESSION['id_session']));
			foreach ( $course_groups as $key => $group ) {
				$receivers ['G' . $key] = '-G- ' . $group ['name'];
			}
			// The receivers: users
			$course_users = CourseManager::get_user_list_from_course_code(api_get_course_id(), intval($_SESSION['id_session']) == 0 , intval($_SESSION['id_session']));
			foreach ( $course_users as $key => $user ) {
				$receivers ['U' . $key] = $user ['lastname'] . ' ' . $user ['firstname'];
			}
		} else {
			// if group members can add events we have to retrieve the groups of the user
			if (api_get_setting('user_manage_group_agenda')=='true' AND !api_is_allowed_to_edit(false,true)){
				// Additional libraries
				require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

				// The receivers: groups (the user belongs to)
				$group_memberships		= GroupManager::get_selected_group_info_of_subscribed_groups($_course['dbName'],$_user['user_id'],array('g.id','name'));
				foreach ( $group_memberships as $key => $group ) {
					$receivers ['G' . $group['id']] = '-G- ' . $group['name'];
				}

				// The receivers: other users of the groups (the user belongs to)
			}
		}

		$renderer->setElementTemplate('<div class="row"><div style="width:100%;float:right;">'.get_lang ( 'VisibleFor' ).'&nbsp;&nbsp;{element}</div></div>', 'send_to');
		$form->addElement ( 'receivers', 'send_to', get_lang ( 'VisibleFor' ), array ('receivers' => $receivers, 'receivers_selected' => ''));

		// The title
		$renderer->setElementTemplate('<div class="row"><div style="width:100%;float:right;"><!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->'.get_lang ( 'Announcement' ).'&nbsp;&nbsp;{element}</div></div>', 'title');

		$form->addElement ( 'text', 'title', get_lang ( 'Announcement' ), array ('maxlength' => '250', 'width' => '100%', 'style' => 'cursor: text;') );
		
		// default values
		$defaults ['send_to'] ['receivers'] = 0;
		if (!empty($input_values)){
			$defaults['title'] = $input_values['title'];
			//$defaults['content'] = ereg_replace("<br />","\r\n",$input_values['content']);
			$defaults['content'] = $input_values['content'];
			
			if (!empty($input_values['to_group_id']) OR !empty($input_values['to_user_id']))
			{
				$defaults['send_to'] ['receivers'] = 1;
			}
			foreach ($input_values['to_group_id'] as $key=>$group_id){
				$defaults['send_to']['to'][] = 'G'.$group_id;
			}
			foreach ($input_values['to_user_id'] as $key=>$user_id){
				$defaults['send_to']['to'][] = 'U'.$user_id;
			}
		}
		
		// The message

		$renderer->setElementTemplate('<div class="row"><div style="width:100%;float:right;">{element}</div></div>', 'content');
		if ($defaults['send_to']['receivers'] == 1){
			if (ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
				$form->add_html_editor('content', get_lang('Message'), false, false, array('ToolbarSet' => 'Announcements', 'Width' => '100%', 'Height' => '68'));
			}else{
				$form->add_html_editor('content', get_lang('Message'), false, false, array('ToolbarSet' => 'Announcements', 'Width' => '100%', 'Height' => '132'));
			}
			
		}else{
			if (ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
				$form->add_html_editor('content', get_lang('Message'), false, false, array('ToolbarSet' => 'Announcements', 'Width' => '100%', 'Height' => '176'));
			}else{
				$form->add_html_editor('content', get_lang('Message'), false, false, array('ToolbarSet' => 'Announcements', 'Width' => '100%', 'Height' => '255'));
			}
			
		}
		

		$form->addElement ( 'style_submit_button', 'submit_announcement', get_lang ( 'SaveAnnouncement' ) ,'class="save" style="float: right; cursor: default;"');



		// The rules (required fields)
		$form->addRule ( 'title', get_lang ( 'ThisFieldIsRequired' ), 'required' );
		// The validation or display
		if ($form->validate ()) {
			$values = $form->exportValues ();

			if (isset ( $_POST ['submit_announcement'] )) {
				$id = store_announcement ( $values );
				Display::display_confirmation_message ( get_lang ( 'AnnouncementStored' ) );
			}
	
		} else {
			$form->setDefaults ($defaults);
			$form->display ();
		
			if ($defaults ['send_to'] ['receivers'] == 0 OR $defaults ['send_to'] ['receivers'] == '-1') {
				$js = "<script type=\"text/javascript\">/* <![CDATA[ */ receivers_hide('receivers_to'); if (document.announcement_form.title) document.announcement_form.title.focus();  /* ]]> */ </script>\n";
			} else {
				$js = "<script type=\"text/javascript\">/* <![CDATA[ */ receivers_show('receivers_to'); if (document.announcement_form.title) document.announcement_form.title.focus();  /* ]]> */ </script>\n";
			}
			echo $js;
		}
	}
}

function store_announcement($values){
	// Database table definition
	$table_announcement		= Database::get_course_table(TABLE_ANNOUNCEMENT);
	$table_item_property  		= Database::get_course_table(TABLE_ITEM_PROPERTY);

	if (api_is_allowed_to_edit(false,true) || api_get_course_setting('allow_user_edit_announcement')) {
		// adding a new announcement
		if (! $values ['announcement_id'] or ! is_numeric ( $values ['announcement_id'] )) {
			// first we calculate the max display order
			$sql = "SELECT max(display_order) as max FROM $table_announcement";
			$result = Database::query($sql,__FILE__,__LINE__);
			$row = Database::fetch_array($result);
			$max = (int)$row['max'] + 1;
		
			// create the SQL statement to add the 
			$sql = "INSERT INTO $table_announcement (title, content, end_date, display_order, email_sent, session_id) VALUES (
					'".Database::escape_string($values['title'])."',
					'".Database::escape_string($values['content'])."',
					NOW(),
					'".$max."',
					1,
					'".api_get_session_id()."'
				)";
			$result = Database::query($sql,__FILE__,__LINE__);
			$last_id = mysql_insert_id ();
			// store in item_property (visibility, insert_date, target users/groups, visibility timewindow, ...)
			store_item_property ( $values, $last_id, 'AnnouncementAdded' );
		} else {
		// editing an announcement
			$last_id = $values['announcement_id'];

			// create the SQL statement to edit the announcement
			$sql = "UPDATE $table_announcement SET 
					title 		= '".Database::escape_string($values['title'])."',
					content 	= '".Database::escape_string($values['content'])."'
					WHERE id = '".Database::escape_string($values['announcement_id'])."'";
			$result = Database::query($sql,__FILE__,__LINE__);

			// first delete all the information in item_property
			$sql = "DELETE FROM $table_item_property WHERE tool='".TOOL_ANNOUNCEMENT."' AND ref='".Database::escape_string($values['announcement_id'])."'";
			$result = Database::query($sql,__FILE__,__LINE__);

			// store in item_property (visibility, insert_date, target users/groups, visibility timewindow, ...)
			store_item_property ( $values, $values['announcement_id'], 'AnnouncementEdited' );
		}
	}

	send_announcement_email($values);

	// finally we display the (edited) or added announcement
	display_announcement($last_id);
}


function send_announcement_email($form_values){
	
	global $_user, $_course;
	
	$from_name = ucfirst($_user['firstname']).' '.strtoupper($_user['lastname']);
	$from_email = $_user['mail'];
	$subject = $form_values['title'];
	$message = $form_values['content'];

	// create receivers array
	if($form_values['send_to']['receivers'] == 0)
	{ // full list of users
		$receivers = CourseManager::get_user_list_from_course_code(api_get_course_id(), intval($_SESSION['id_session']) != 0, intval($_SESSION['id_session']));
	}
	else if($form_values['send_to']['receivers'] == 1) {
		$users_ids = array();
		foreach($form_values['send_to']['to'] as $to)
		{
			if(strpos($to, 'G') === false)
			{
				$users_ids[] = intval(substr($to, 1));
			}
			else
			{
				$groupId = intval(substr($to, 1));
				$users_ids = array_merge($users_ids, GroupManager::get_users($groupId));
			}	
			$users_ids = array_unique($users_ids);
		}
		if(count($users_ids) > 0)
		{
			$sql = 'SELECT lastname, firstname, email 
					FROM '.Database::get_main_table(TABLE_MAIN_USER).'
					WHERE user_id IN ('.implode(',', $users_ids).')';
			$rsUsers = Database::query($sql, __FILE__, __LINE__);
			while($userInfos = Database::fetch_array($rsUsers))
			{
				$receivers[] = $userInfos;
			}
		}
	}
	else if($form_values['send_to']['receivers'] == -1) {
		$receivers[] = array(
						'lastname' => $_user['lastName'],
						'firstname' => $_user['firstName'],
						'email' => $_user['mail']
						);
	}
	
	foreach($receivers as $receiver)
	{
		$to_name = ucfirst($receiver['firstname']).' '.strtoupper($receiver['lastname']);
		$to_email = $receiver['email'];
		api_mail_html($to_name, $to_email, $subject, $message, $from_name, $from_email);
	}
}

/**
 * Enter description here...
 *
 * @param unknown_type $values
 * @param unknown_type $id
 * @param unknown_type $action_string
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function store_item_property($values, $id, $action_string) {
	global $_course;
	global $_user;
	
	if ($values ['send_to'] ['receivers'] == 0) {
		api_item_property_update ( $_course, TOOL_ANNOUNCEMENT, $id, $action_string, $_user ['user_id'], '', '', $start_visible, $end_visible );
	}
	if ($values ['send_to'] ['receivers'] == 1) {
		foreach ( $values ['send_to'] ['to'] as $key => $target ) {
			if (substr ( $target, 0, 1 ) == 'U') {
				$user = substr ( $target, 1 );
				api_item_property_update ( $_course, TOOL_ANNOUNCEMENT, $id, $action_string, $_user ['user_id'], '', $user, $start_visible, $end_visible );
			}
			if (substr ( $target, 0, 1 ) == 'G') {
				$group = substr ( $target, 1 );
				api_item_property_update ( $_course, TOOL_ANNOUNCEMENT, $id, $action_string, $_user ['user_id'], $group, '', $start_visible, $end_visible );
			}
		}
	}
	if ($values ['send_to'] ['receivers'] == '-1') {
		// adding to everybody
		api_item_property_update ( $_course, TOOL_ANNOUNCEMENT, $id, $action_string, $_user ['user_id'], '', '', $start_visible, $end_visible );
		// making it invisible
		api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'invisible');
	}
}


function display_announcements_actions(){
	echo '<div class="actions">';
	if ((api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) and (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath')) {
		echo '<span>';
		echo "<a href='".api_get_self()."?".api_get_cidreq()."&action=add&origin=".(empty($_GET['origin'])?'':Security::remove_XSS($_GET['origin']))."'>".Display::return_icon('pixel.gif',get_lang('AddAnnouncement'), array('class' => 'toolactionplaceholdericon toolactionannoucement')).get_lang('AddAnnouncement')."</a>";
		echo '</span>';		
	}

	echo '</div>';	
}

function number_of_announcements(){
	return 5;
}

function display_announcement($id=''){
	$announcementHTML = "";
	
	// to know which announcement we have to display we first chekc
	// if there is an explicit mention of which id we must display (the $id parameter)
	// then we check if an id is passed through the $_GET parameter
	// and finally we display the most recent (or the one with highest display_order)
	if ($id <> ''){
		$announcements = get_announcements();
		$announcement = $announcements[$id];		
	} elseif (empty($_GET['ann_id'])){
		$announcement = get_announcements('',true);
	} else {
		$announcements = get_announcements();
		$announcement = $announcements[$_GET['ann_id']];
	}

	echo '<div class="announcement_title"><h2>'.$announcement['title'].'</h2></div>';

	echo  '<div class="announcement_body">';
	echo  '	<div class="announcement_metadata">';
	echo  '		<div class="announcement_date">'.$announcement['announcement_date'].'</div>';
	echo  '		<div class="announcement_sender"><a href="../user/userInfo.php?user_id='.$announcement['insert_user_id'].'">'.$announcement['firstname'].' '.$announcement['lastname'].'</a></div>';	
	echo  '	</div>';
	if (ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
		echo  '	<div class="announcement_content" style="height: 333px;overflow: auto;">'.nl2br($announcement['content']).'</div>';
	} else {
		echo  '	<div class="announcement_content" style="height: 322px;overflow: auto;">'.nl2br($announcement['content']).'</div>';	
	}	
	echo  '</div>';
	if(api_is_allowed_to_edit() || (api_get_course_setting('allow_user_edit_announcement') && api_get_user_id() == $announcement['insert_user_id'])){
		echo  '<div class="announcements_actions" style="padding-top:0px">';
		echo  '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=edit&id='.$announcement['id'].'">'.Display::return_icon('pixel.gif',get_lang('EditAnnouncement'),array('align' => 'absmiddle','class'=>'actionplaceholdericon actionedit')).' '.get_lang('EditAnnouncement').'</a>';
		echo  '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';		
		echo  '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete&id='.$announcement['id'].'" onclick="javascript:if(!confirm(\''.get_lang("ConfirmYourChoice").'\')) return false;">'.Display::return_icon('delete.png',get_lang('DeleteAnnouncement'),array('align' => 'absmiddle')).' '.get_lang('DeleteAnnouncement').'</a>';
		echo  '</div>';
	}
	
}
?>
