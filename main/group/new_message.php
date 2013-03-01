<?php
/* For licensing terms, see /license.txt */
/**
*	@package dokeos.messages
*/


/**
* This script shows a compose area (wysiwyg editor if supported, otherwise
* a simple textarea) where the user can type a message.
* There are three modes
* - standard: type a message, select a user to send it to, press send
* - reply on message (when pressing reply when viewing a message)
* - send to specific user (when pressing send message in the who is online list)
*/
/* 		INIT SECTION	*/
// name of the language file that needs to be included
$language_file= array('messages','userInfo','group');
$cidReset	= true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'text.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = api_xml_http_response_encode(get_lang('Messages'));
/*	Constants and variables */
$htmlHeadXtra[] = '<script type="text/javascript">

function show_icon_edit(element_html) {
	ident="#edit_image";
	$(ident).show();
}

function hide_icon_edit(element_html)  {
	ident="#edit_image";
	$(ident).hide();
}

</script>';

$htmlHeadXtra[] = '
	<style>
	div.row div.label{
		width: 10%;
	}
	div.row div.formw{
		width: 85%;
	}

	</style>
	';

$htmlHeadXtra[]='
<script language="javascript">
function validate(form,list) {
	if(list.selectedIndex<0)
	{
    	alert("Please select someone to send the message to.")
    	return false
	}
	else
    	return true
}

</script>';
//$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';

$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready(function (){

      		$("#users").fcbkcomplete({
	            json_url: "message.ajax.php?a=find_users",
	            cache: false,
	            filter_case: false,
	            filter_hide: true,	            
				firstselected: true,
	            //onremove: "testme",
				//onselect: "testme",
	            filter_selected: true,
	            newel: true
          	});
});

var counter_image = 1;
/*
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);
}
*/
function add_image_form() {
	// Multiple filepaths for image form
	var filepaths = document.getElementById("filepaths");
	if (document.getElementById("filepath_"+counter_image)) {
		counter_image = counter_image + 1;
	}  else {
		counter_image = counter_image;
	}
	var elem1 = document.createElement("div");
	elem1.setAttribute("id","filepath_"+counter_image);
	filepaths.appendChild(elem1);
	id_elem1 = "filepath_"+counter_image;
	id_elem1 = "\'"+id_elem1+"\'";
	//document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"'.api_get_path(WEB_CODE_PATH).'img/delete.gif\"></a>";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"28\" />&nbsp;<input type=\"text\" name=\"legend[]\" size=\"20\" /></a>";
	if (filepaths.childNodes.length == 6) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}

</script>';

$nameTools = get_lang('ComposeMessage');

/*
		FUNCTIONS
*/

/**
* Shows the compose area + a list of users to select from.
*/
function show_compose_to_any ($user_id) {
	$online_user_list = MessageManager::get_online_user_list($user_id);
	$default['user_list'] = 0;
	$online_user_list=null;
	manage_form($default, $online_user_list);
}

function show_compose_reply_to_message ($message_id, $receiver_id) {
	global $charset;
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	if(api_is_allowed_to_edit()){
	$query = "SELECT user_sender_id FROM $table_message WHERE id='".intval($message_id)."';";
	}
	else {
	$query = "SELECT user_sender_id FROM $table_message WHERE user_receiver_id=".intval($receiver_id)." AND id='".intval($message_id)."';";
	}
	$result = Database::query($query);
	$row = Database::fetch_array($result,'ASSOC');
	if (!isset($row['user_sender_id'])) {
		echo get_lang('InvalidMessageId');
		die();
	}

	$pre_html = '<div class="row">
				<div class="label">'.get_lang('Contact').'</div>
				<div class="formw">';
	$post = '</div></div>';
	$multi_select = '<select id="users" name="users">
					 </select>';
	echo $pre_html.'<strong>'.GetFullUserName($row['user_sender_id']).'</strong>'.$post;
	//echo get_lang('To').':&nbsp;<strong>'.	GetFullUserName($row['user_sender_id']).'</strong>';
	//$default['title'] = get_lang('EnterTitle');
	$default['users'] = array($row['user_sender_id']);
	manage_form($default);
}

function show_compose_to_user ($receiver_id) {
	global $charset;
//	echo get_lang('To').':&nbsp;<strong>'.	GetFullUserName($receiver_id).'</strong>';
//	$default['title'] = api_xml_http_response_encode(get_lang('EnterTitle'));
	$pre_html = '<div class="row">
				<div class="label">'.get_lang('Contact').'</div>
				<div class="formw">';
	$post = '</div></div>';
	$multi_select = '<select id="users" name="users">
					 </select>';
	echo $pre_html.'<strong>'.GetFullUserName($receiver_id).'</strong>'.$post;

	$default['users'] = array($receiver_id);
	manage_form($default);
}

function manage_form ($default, $select_from_user_list = null) {

	global $charset;
	$table_message = Database::get_main_table(TABLE_MESSAGE);

	$group_id 	= intval($_REQUEST['group_id']);
	$message_id = intval($_GET['message_id']);
	$param_f = isset($_GET['f'])?Security::remove_XSS($_GET['f']):'';
	$current_group = GroupManager :: get_group_properties($group_id);


	if(api_is_allowed_to_edit()){
		$users = GroupManager::get_group_users($group_id);
		$tutors = GroupManager::get_group_tutorslist($group_id);
		$users = array_merge($users,$tutors);
	}
	elseif(api_is_grouptutor($_course,api_get_session_id(),api_get_user_id())){
		$users = GroupManager::get_group_users($group_id);
		$tutors = GroupManager::get_othergroup_tutors($group_id);
		$users = array_merge($users,$tutors);
	}
	else {
		if($current_group['category_id'] == 1){
			$users = GroupManager::get_group_tutorslist($group_id);
		}
		else {
			$tutors = GroupManager::get_group_tutorslist($group_id);
			$users = GroupManager::get_group_users($group_id);
			$users = array_merge($users,$tutors);
		}
	}

	$form = new FormValidator('compose_message',null,api_get_self().'?'.api_get_cidReq().'&id_session='.api_get_session_id(),null,array('enctype'=>'multipart/form-data'));
	if (!empty($group_id)) {
		if (isset($select_from_user_list)) {			
		/*	$form->add_textfield('id_text_name', get_lang('Contact'),true,array('size' => 30,'id'=>'id_text_name','onkeyup'=>'send_request_and_search()','autocomplete'=>'off','style'=>'padding:0px'));
			$form->addRule('id_text_name', get_lang('ThisFieldIsRequired'), 'required');
			$form->addElement('html','<div id="id_div_search" style="padding:0px" class="message-select-box" >&nbsp;</div>');*/
			$form->addElement('select','users',get_lang('Contact'),$select_from_user_list);
			$form->addElement('hidden','user_list',0,array('id'=>'user_list'));
		} else {
			if (empty($default['users'])) {				
				//the magic should be here
			/*	$pre_html = '<div class="row">
							<div class="label">'.get_lang('Contact').'</div>
							<div class="formw">';
				$post = '</div></div>';
				$multi_select = '<select id="users" name="users">
	      						 </select>';
				$form->addElement('html',$pre_html.$multi_select.$post );*/
				if(sizeof($users) == 0){
				$form->addElement('static','users',get_lang('Contact'),get_lang('NoContacts'));
				}
				else {
				$form->addElement('select','users',get_lang('Contact'),$users,array('multiple' => 'multiple','size' => '5'));
				}

			} else {
				$form->addElement('hidden','hidden_user',$default['users'][0],array('id'=>'hidden_user'));
			}
		}
	} 
	/*else {
		$group_info = GroupPortalManager::get_group_data($group_id);
		$form->addElement('html','<div class="row"><div class="label">'.get_lang('ToGroup').'</div><div class="formw">'.api_xml_http_response_encode($group_info['name']).'</div></div>');
		$form->addElement('hidden','group_id',$group_id);
		$form->addElement('hidden','parent_id',$message_id);
	}*/

	$form->add_textfield('title', get_lang('Subject'),true ,array('size' => 55));

	$form->add_html_editor('content', get_lang('Message'), false, false, array('ToolbarSet' => 'Messages', 'Width' => '100%', 'Height' => '250'));
	//$form->addElement('textarea','content', get_lang('Message'), array('cols' => 75,'rows'=>8));

	if (isset($_GET['re_id'])) {
		$message_reply_info = MessageManager::get_message_by_id($_GET['re_id']);
		$form->addElement('hidden','re_id',Security::remove_XSS($_GET['re_id']));
		$form->addElement('hidden','save_form','save_form');
		
		//adding reply mail
		$user_reply_info = UserManager::get_user_info_by_id($message_reply_info['user_sender_id']);
		$default['content']='<p></p>'.api_get_person_name($user_reply_info['firstname'],$user_reply_info['lastname']).' '.get_lang('Wrote').' :<i> <br />'.api_html_entity_decode($message_reply_info['content'],ENT_QUOTES,$charset).'</i>';
		
	}
	//if (empty($group_id)) {
		$form->addElement('html','<div class="row"><div class="label">'.get_lang('FilesAttachment').'</div><div class="formw">
				<span id="filepaths">
				<div id="filepath_1">
				<input type="file" name="attach_1"  size="28" />
				<input type="text" name="legend[]" size="20" />
				</div></span></div></div>');
		$form->addElement('html','<div class="row"><div class="formw"><span id="link-more-attach"><a href="javascript://" onclick="return add_image_form()">'.get_lang('AddOneMoreFile').'</a></span>&nbsp;('.sprintf(get_lang('MaximunFileSizeX'),format_file_size(api_get_setting('message_max_upload_filesize'))).')</div></div>');
	//}

	$form->addElement('style_submit_button','compose',api_xml_http_response_encode(get_lang('SendMessage')),'class="save"');
	if (!empty($group_id) && !empty($message_id)) {
		$message_info = MessageManager::get_message_by_id($message_id);
		$default['title']=get_lang('Re:').api_html_entity_decode($message_info['title'],ENT_QUOTES,$charset);
	}
	$form->setDefaults($default);

	if ($form->validate()) {		
		$check = Security::check_token('post');
		if ($check) {
			$values 		= $default;
			$user_list		= $values['users'];
			$file_comments	= $_POST['legend'];
			$title 			= $values['title'];
			$content 		= $values['content'];

			$group_id		= $values['group_id'];
			$parent_id 		= $values['parent_id'];

			if (is_array($user_list) && count($user_list)> 0) {
				//all is well, send the message
				foreach ($user_list as $user) {					
					$res = MessageManager::send_message($user, $title, $content, $_FILES, $file_comments, $group_id, $parent_id);
					if ($res) {
						if (is_string($res)) {
							Display::display_error_message($res);
						} else {
							MessageManager::display_success_message($user);
						}
					}
				}
			}
		}
		Security::clear_token();
	} else {
		$token = Security::get_token();
		$form->addElement('hidden','sec_token');
		$form->setConstants(array('sec_token' => $token));
		$form->display();
	}
}
// the section (for the tabs)
$this_section = SECTION_COURSES;

// current group
$current_group = GroupManager :: get_group_properties($_SESSION['_gid']);

// tracking
event_access_tool(TOOL_GROUP);

$nameTools = get_lang('GroupSpace');

// breadcrumbs
$interbreadcrumb[] = array ("url" => "group.php", "name" => get_lang("Groups"));


// display the header
Display::display_header($nameTools.' '.$current_group['name'],'Group');


// Display actions
echo '<div class="actions">';
echo '<a href="group.php?'.api_get_cidReq().'">'.Display::return_icon('pixel.gif',get_lang('MyGroup'),array('class'=>'toolactionplaceholdericon toolactiongroupimage')).get_lang('MyGroup').'</a>';
echo '<a href="inbox.php?'.api_get_cidReq().'">'.Display::return_icon('pixel.gif', get_lang('Inbox'),array('class'=>'toolactionplaceholdericon toolactioninbox')).get_lang('Inbox').'</a>';
echo '<a href="new_message.php?'.api_get_cidReq().'">'.Display::return_icon('pixel.gif', get_lang('ComposeMessage'), array('class'=>'toolactionplaceholdericon toolactionsinvite')).get_lang('ComposeMessage').'</a>';
echo '<a href="outbox.php?'.api_get_cidReq().'">'.Display::return_icon('pixel.gif', get_lang('Outbox'),array('class'=>'toolactionplaceholdericon toolactionoutbox')).get_lang('Outbox').'</a>';
$group_id = intval($_REQUEST['group_id']);

echo '</div>';
// Start content
echo '<div id="content">';

echo '<div id="social-content" >';
	$id_content_right = '';
	//LEFT COLUMN
	if (api_get_setting('allow_social_tool') != 'true') {
    //
	} else {
		require_once api_get_path(LIBRARY_PATH).'social.lib.php';
		/*echo '<div id="social-content-left">';
			//this include the social menu div
			SocialManager::show_social_menu('messages_compose');
		echo '</div>';*/
		$id_content_right = 'social-content-all';
	}

	echo '<div id="'.$id_content_right.'">';

		//MAIN CONTENT
		if (!isset($_POST['compose'])) {
			if(isset($_GET['re_id'])) {
				show_compose_reply_to_message($_GET['re_id'], api_get_user_id());
			} elseif(isset($_GET['send_to_user'])) {
				show_compose_to_user($_GET['send_to_user']);
			} else {
				show_compose_to_any($_user['user_id']);
		  	}
		} else {

			$restrict = false;
			if (isset($_POST['users'])) {
				$restrict = true;
			} elseif (isset($_POST['group_id'])) {
				$restrict = true;
			} elseif(isset($_POST['hidden_user'])) {
				$restrict = true;
			}

			$default['title']	= $_POST['title'];
			$default['content'] = $_POST['content'];

			// comes from a reply button
			if (isset($_GET['re_id'])) {
				manage_form($default);
			} else {
				// post
				if ($restrict) {
					if (!isset($_POST['group_id'])) {
						$default['users']	 = $_POST['users'];
					} else {
						$default['group_id'] = $_POST['group_id'];
					}
					if (isset($_POST['hidden_user'])) {
						$default['users']	 = array($_POST['hidden_user']);
					}
					manage_form($default);
				} else {
					echo get_lang('ErrorSendingMessage');
				}
			}
		}
	echo '</div>';

echo '</div>';

// End content
echo '</div>';

// Actions
echo '<div class="actions">';
echo '</div>';

/*
		FOOTER
*/
Display::display_footer();

?>