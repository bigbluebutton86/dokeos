<?php // $Id: whoisonline.php 21549 2009-06-21 14:02:37Z iflorespaz $
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
* Who is online list
==============================================================================
*/

// language files that should be included
$language_file = array('index', 'registration', 'messages', 'userInfo');

if (!isset($_GET['cidReq'])) {
	$cidReset = true;
}

// including necessary files
require_once './main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
//social tab
$this_section = SECTION_SOCIAL;
// table definitions
$track_user_table = Database::get_main_table(TABLE_MAIN_USER);

$htmlHeadXtra[] = '<script type="text/javascript">
	function show_image(image,width,height) {
		width = parseInt(width) + 20;
		height = parseInt(height) + 20;
		window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
	}

</script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="./main/inc/lib/javascript/thickbox.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="./main/inc/lib/javascript/thickbox.css" type="text/css" media="projection, screen">';
$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready(function (){
	$("input#id_btn_send_invitation").bind("click", function(){
		if (confirm("'.get_lang('SendMessageInvitation', '').'")) {
			$("#form_register_friend").submit();
		}
	});
});
function change_panel (mypanel_id,myuser_id) {
		$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_content_panel").html("'.get_lang('Loading', '').'"); },
			type: "POST",
			url: "main/messages/send_message.php",
			data: "panel_id="+mypanel_id+"&user_id="+myuser_id,
			success: function(datos) {
			 $("div#id_content_panel_init").html(datos);
			 $("div#display_response_id").html("");
			}
		});
}
function action_database_panel(option_id,myuser_id) {

	if (option_id==5) {
		my_txt_subject=$("#txt_subject_id").val();
	} else {
		my_txt_subject="clear";
	}
	my_txt_content=$("#txt_area_invite").val();
	if (my_txt_content.length==0 || my_txt_subject.length==0) {
		$("#display_response_id").html("&nbsp;&nbsp;&nbsp;'.get_lang('MessageEmptyMessageOrSubject', '').'");
		setTimeout("message_information_display()",3000);
		return false;
	}
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#display_response_id").html("'.get_lang('Loading', '').'"); },
		type: "POST",
		url: "main/messages/send_message.php",
		data: "panel_id="+option_id+"&user_id="+myuser_id+"&txt_subject="+my_txt_subject+"&txt_content="+my_txt_content,
		success: function(datos) {
		 $("#display_response_id").html(datos);
		}
	});
}
function display_hide () {
		setTimeout("hide_display_message()",3000);
}
function message_information_display() {
	$("#display_response_id").html("");
}
function hide_display_message () {
	$("div#display_response_id").html("");
	try {
		$("#txt_subject_id").val("");
		$("#txt_area_invite").val("");
	}catch(e) {
		$("#txt_area_invite").val("");
	}
}
</script>';

if ($_GET['chatid'] != '') {
	//send out call request
	$time = time();
	$time = date("Y-m-d H:i:s", $time);
	$chatid = addslashes($_GET['chatid']);
	if ($_GET['chatid'] == strval(intval($_GET['chatid']))) {
		$sql = "update $track_user_table set chatcall_user_id = '".Database::escape_string($_user['user_id'])."', chatcall_date = '".Database::escape_string($time)."', chatcall_text = '' where (user_id = ".(int)Database::escape_string($chatid).")";
		$result = Database::query($sql, __FILE__, __LINE__);
		//redirect caller to chat
		header("Location: ".$_configuration['code_append']."chat/chat.php?".api_get_cidreq()."&origin=whoisonline&target=".Security::remove_XSS($chatid));
		exit;
	}
}

/**
 * Displays a sortable table with the list of online users.
 * @param array $user_list
 */
function display_user_list($user_list, $_plugins) {
	global $charset;
	if ($_GET["id"]=='') {
		$extra_params = array();
		$course_url = '';
		if(strlen($_GET['cidReq']) > 0) {
			$extra_params['cidReq'] = Security::remove_XSS($_GET['cidReq']);
			$course_url = '&amp;cidReq='.Security::remove_XSS($_GET['cidReq']);
		}		
		foreach ($user_list as $user) {
			$uid=$user[0];
			$user_info = api_get_user_info($uid);
			$table_row = array();
			$url = '?id='.$uid.$course_url;
            $image_array=UserManager::get_user_picture_path_by_id($uid,'system',false,true);
            
            $friends_profile = SocialManager::get_picture_user($uid, $image_array['file'], 92, 'medium_', ' width="90" height="90" ');
            //reduce image
            $table_row[] = '<center><a href="'.$url.'"><img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' border="1"></a></center>';
			$table_row[] = '<a href="'.$url.'">'.$user_info['firstName'].' '.$user_info['lastName'].'</a>';
			
			//$table_row[] = '<a href="'.$url.'">'.$user_info['lastName'].'</a>';
			
			if (api_get_setting('show_email_addresses') == 'true') {
				$table_row[] = Display::encrypted_mailto_link($user_info['mail']);
			}
			$user_anonymous=api_get_anonymous_id();
			if (api_get_setting('allow_social_tool')=='true' && api_get_user_id()<>$user_anonymous && api_get_user_id()<>0) {
				if ($user_info['user_id'] != api_get_user_id() && !api_is_anonymous($user_info['user_id'])) {
					$user_relation=SocialManager::get_relation_between_contacts(api_get_user_id(),$user_info['user_id']);
					if ($user_relation==0 || $user_relation==6) {
						$table_row[] = '<a href="main/messages/send_message_to_userfriend.inc.php?view_panel=2&height=365&width=610&user_friend='.$user_info['user_id'].'" class="thickbox" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('add_multiple_users.gif', get_lang('SocialInvitationToFriends')).'&nbsp;'.get_lang('SendInvitation').'</a><br />
										<a href="main/messages/send_message_to_userfriend.inc.php?view_panel=1&height=365&width=610&user_friend='.$user_info['user_id'].'" class="thickbox" title="'.get_lang('SendAMessage').'">'.Display :: return_icon('mail_send.png', get_lang('SendAMessage')).'&nbsp;'.get_lang('SendAMessage').'</a>';
					} else {
						$table_row[] = '<a href="main/messages/send_message_to_userfriend.inc.php?view_panel=1&height=365&width=610&user_friend='.$user_info['user_id'].'" class="thickbox" title="'.get_lang('SendAMessage').'">'.Display :: return_icon('mail_send.png', get_lang('SendAMessage')).'&nbsp;'.get_lang('SendAMessage').'</a>';
					}				
				}
			}
			$table_data[] = $table_row;
		}
		$table_header[] = array(get_lang('UserPicture'),false,'width="90"');
		$table_header[] = array(get_lang('Name'),true);
		//$table_header[] = array(get_lang('LastName'),true);
		
		if (api_get_setting('show_email_addresses') == 'true') {
			$table_header[] = array(get_lang('Email'),true);
		}
		$user_anonymous=api_get_anonymous_id();
		if (api_get_setting('allow_social_tool')=='true' && api_get_user_id()<>$user_anonymous && api_get_user_id()<>0) {
			$table_header[] = array(get_lang('Friends'),false,'width="200"');
		}		
		/*this feature is deprecated
		if ( api_get_setting('allow_message_tool')=='true' && isset($_SESSION['_user'])) {
			$table_header[] = array(get_lang('SendMessage'),true);
		}
		*/
		$sorting_options['column'] = (isset ($_GET['column']) ? (int)$_GET['column'] : 2);
		/*if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true' ) {			
			//send_invitation_friend_user();
			echo '<div align="right"><input type="button" name="id_btn_send_invitation" id="id_btn_send_invitation" value="'.get_lang('SendInviteMessage').'"/></div>';			
			echo '<form action="whoisonline.php" name="form_register_friend" id="form_register_friend" method="post">';
		}*/
		
		Display::display_sortable_table($table_header,$table_data,$sorting_options,array('per_page_default'=>count($table_data)),$extra_params);		
		/*if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true' ) {
			echo '</form>';
		}*/
	}
}
/**
 * Displays the information of an individual user
 * @param int $user_id
 */
function display_individual_user($user_id) {
	global $interbreadcrumb;
	$safe_user_id = Database::escape_string($user_id);

	// to prevent a hacking attempt: http://www.dokeos.com/forum/viewtopic.php?t=5363
	$user_table = Database::get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT * FROM $user_table WHERE user_id='".$safe_user_id."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if (Database::num_rows($result) == 1) {
		$user_object = Database::fetch_object($result);
		$name = GetFullUserName($user_id).($_SESSION['_uid'] == $user_id ? '&nbsp;<strong>('.get_lang('Me').')</strong>' : '' );
		$alt = GetFullUserName($user_id).($_SESSION['_uid'] == $user_id ? '&nbsp;('.get_lang('Me').')' : '');
		$status = ($user_object->status == COURSEMANAGER ? get_lang('Teacher') : get_lang('Student'));
		$interbreadcrumb[] = array('url' => 'whoisonline.php', 'name' => get_lang('UsersOnLineList'));
		Display::display_header($alt);
        echo '<div id="content">';
		echo '<div class="actions-title">';
		echo $alt;
		echo '</div><br />';
		echo '<div style="text-align: center">';
		if (strlen(trim($user_object->picture_uri)) > 0) {
			$sysdir_array = UserManager::get_user_picture_path_by_id($safe_user_id, 'system');
			$sysdir = $sysdir_array['dir'];
			$webdir_array = UserManager::get_user_picture_path_by_id($safe_user_id, 'web');
			$webdir = $webdir_array['dir'];
			$fullurl = $webdir.$user_object->picture_uri;
			$system_image_path = $sysdir.$user_object->picture_uri;
			list($width, $height, $type, $attr) = @getimagesize($system_image_path);
			$resizing = (($height > 200) ? 'height="200"' : '');
			$height += 30;
			$width += 30;
			$window_name = 'window'.uniqid('');
			// get the path,width and height from original picture
			$big_image = $webdir.'big_'.$user_object->picture_uri;
			$big_image_size = api_getimagesize($big_image);
			$big_image_width = $big_image_size[0];
			$big_image_height = $big_image_size[1];
			$url_big_image = $big_image.'?rnd='.time();
			echo '<input type="image" src="'.$fullurl.'" alt="'.$alt.'" onclick="javascript: return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/><br />';
		} else {
			echo Display::return_icon('unknown.jpg', get_lang('Unknown'));
			echo '<br />';
		}
		
		echo '<br />'.$status.'<br />';
		
		global $user_anonymous;
		if (api_get_setting('allow_social_tool') == 'true' && api_get_user_id() <> $user_anonymous && api_get_user_id() <> 0) {
			echo '<br />';
			echo '<a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$safe_user_id.'">'.get_lang('ViewSharedProfile').'</a>';
			echo '<br />';
							
			$user_anonymous = api_get_anonymous_id();
			
			if ($safe_user_id != api_get_user_id() && !api_is_anonymous($safe_user_id)) {
				$user_relation = SocialManager::get_relation_between_contacts(api_get_user_id(), $safe_user_id);
				if ($user_relation == 0 || $user_relation == 6) {
					echo  '<a href="main/messages/send_message_to_userfriend.inc.php?view_panel=2&height=300&width=610&user_friend='.$safe_user_id.'" class="thickbox" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('add_multiple_users.gif', get_lang('SocialInvitationToFriends')).'&nbsp;'.get_lang('SendInvitation').'</a><br />
						   <a href="main/messages/send_message_to_userfriend.inc.php?view_panel=1&height=310&width=610&user_friend='.$safe_user_id.'" class="thickbox" title="'.get_lang('SendAMessage').'">'.Display :: return_icon('mail_send.png', get_lang('SendAMessage')).'&nbsp;'.get_lang('SendAMessage').'</a>';
				} else {
					echo  '<a href="main/messages/send_message_to_userfriend.inc.php?view_panel=1&height=310&width=610&user_friend='.$safe_user_id.'" class="thickbox" title="'.get_lang('SendAMessage').'">'.Display :: return_icon('mail_send.png', get_lang('SendAMessage')).'&nbsp;'.get_lang('SendAMessage').'</a>';
				}
			}
		}

		if (api_get_setting('show_email_addresses') == 'true') {
			echo Display::encrypted_mailto_link($user_object->email,$user_object->email).'<br />';
		}
		
		echo '</div>';
		if ($user_object->competences) {
			echo '<dt><div class="actions-message"><strong>'.get_lang('MyCompetences').'</strong></div></dt>';
			echo '<dd>'.$user_object->competences.'</dd>';
		}
		if ($user_object->diplomas) {
			echo '<dt><div class="actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div></dt>';
			echo '<dd>'.$user_object->diplomas.'</dd>';
		}
		if ($user_object->teach) {
			echo '<dt><div class="actions-message"><strong>'.get_lang('MyTeach').'</strong></div></dt>';
			echo '<dd>'.$user_object->teach.'</dd>';;
		}
		display_productions($user_object->user_id);
		if ($user_object->openarea) {
			echo '<dt><div class="actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div></dt>';
			echo '<dd>'.$user_object->openarea.'</dd>';
		}
	}
	else
	{
		Display::display_header(get_lang('UsersOnLineList'));
        echo '<div id="content">';
		echo '<div class="actions-title">';
		echo get_lang('UsersOnLineList');
		echo '</div>';
	}
}
/**
 * Display productions in whoisonline
 * @param int $user_id User id
 * @todo use the correct api_get_path instead of $clarolineRepositoryWeb
 */
function display_productions($user_id) {
	$sysdir_array = UserManager::get_user_picture_path_by_id($user_id, 'system', true);
	$sysdir = $sysdir_array['dir'].$user_id.'/';
	$webdir_array = UserManager::get_user_picture_path_by_id($user_id, 'web', true);
	$webdir = $webdir_array['dir'].$user_id.'/';
	if (!is_dir($sysdir)) {
		mkpath($sysdir);
	}
	/*
	$handle = opendir($sysdir);
	$productions = array();
	while ($file = readdir($handle)) {
		if ($file == '.' || $file == '..' || $file == '.htaccess') {
			continue;						// Skip current and parent directories
		}
		if (preg_match('/('.$user_id.'|[0-9a-f]{13}|saved)_.+\.(png|jpg|jpeg|gif)$/i', $file)) {
			// User's photos should not be listed as productions.
			continue;
		}
		$productions[] = $file;
	}
	*/
	$productions = UserManager::get_user_productions($user_id);

	if (count($productions) > 0) {
		echo '<dt><strong>'.get_lang('Productions').'</strong></dt>';
		echo '<dd><ul>';
		foreach ($productions as $index => $file) {
			// Only display direct file links to avoid browsing an empty directory
			if (is_file($sysdir.$file) && $file != $webdir_array['file']) {
				echo '<li><a href="'.$webdir.urlencode($file).'" target=_blank>'.$file.'</a></li>';
			}
			// Real productions are under a subdirectory by the User's id
			if (is_dir($sysdir.$file)) {
				$subs = scandir($sysdir.$file);
				foreach ($subs as $my => $sub) {
					if (substr($sub, 0, 1) != '.' && is_file($sysdir.$file.'/'.$sub)) {
						echo '<li><a href="'.$webdir.urlencode($file).'/'.urlencode($sub).'" target=_blank>'.$sub.'</a></li>';
					}
				}
			}
		}
		echo '</ul></dd>';
	}
}

// This if statement prevents users accessing the who's online feature when it has been disabled.
if ((api_get_setting('showonline', 'world') == 'true' && !$_user['user_id']) || ((api_get_setting('showonline', 'users') == 'true' || api_get_setting('showonline', 'course') == 'true') && $_user['user_id'])) {
	if(isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
		$user_list = Who_is_online_in_this_course($_user['user_id'], api_get_setting('time_limit_whosonline'), $_GET['cidReq']);
	} else {
		$user_list = WhoIsOnline($_user['user_id'], $_configuration['statistics_database'], api_get_setting('time_limit_whosonline'));
	}

	$total = count($user_list);
	if (!isset($_GET['id'])) {
		Display::display_header(get_lang('UsersOnLineList'));
        echo '<div id="content">';
		echo '<div class="actions-title">';
		echo get_lang('UsersOnLineList');
		echo '</div>';
		echo '<div class="actions-message">'.get_lang('TotalOnLine').' : '.$total.'</div>';
		if ($_GET['id'] == '') {
			echo '<p><a class="refresh" href="javascript:window.location.reload()">'.get_lang('Refresh').'</a></p>';
		} else {
			if (0) {
			// if ($_user['user_id'] && $_GET["id"] != $_user['user_id']) {
				echo '<a href="'.api_get_self().'?chatid='.Security::remove_XSS($_GET['id']).'">'.get_lang('SendChatRequest').'</a>';
			}
		}
	}

	if ($user_list) {
		if (!isset($_GET['id'])) {			
			display_user_list($user_list, $_plugins);
		} else {
			//individual user information - also displays header info
			display_individual_user(Security::remove_XSS($_GET['id']));
		}
	}
	elseif (isset($_GET['id'])) {
		Display::display_header(get_lang('UsersOnLineList'));
        echo '<div id="content">';
		echo '<div class="actions-title">';
		echo get_lang('UsersOnLineList');
		echo '</div>';
	}
} else {
	Display::display_header(get_lang('UsersOnLineList'));
    echo '<div id="content">';
	Display::display_error_message(get_lang('AccessNotAllowed'));
}

$referer = empty($_GET['referer']) ? 'index.php' : api_htmlentities(strip_tags($_GET['referer']), ENT_QUOTES);

if (isset($_GET['id'])) {
	
	if ($_GET['origin'] == 'user_list') {
		echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php" class="button_back">'.get_lang('Back').'</a>';		
	} else {
		echo '<a href="whoisonline.php" class="fake_button_back">'.get_lang('Back').'</a>';
	}

} else {
	echo '<a href="'.$referer.'" class="fake_button_back">'.get_lang('BackHome').'</a>';
}

echo '</div>'; // End main content
/*
==============================================================================
		FOOTER
==============================================================================
*/
/*echo '<div align="center"><a href="http://main.svndokeos.net/main/upload/users/4/4_49aeb3bb8bba5.jpg" class="thickbox">hola</a></div>';*/
Display::display_footer();
