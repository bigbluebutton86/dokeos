<?php
/* For licensing terms, see /license.txt */
/**
*	@package dokeos.messages
*/

// name of the language file that needs to be included
$language_file = array('registration','messages','userInfo');
$cidReset=true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

api_block_anonymous_users();

if (isset($_GET['messages_page_nr'])) {
	if (api_get_setting('allow_social_tool')=='true' &&  api_get_setting('allow_message_tool')=='true') {
		$social_link = '';
		if ($_REQUEST['f']=='social') {
			$social_link = '&f=social';
		}
		header('Location:outbox.php?pager='.Security::remove_XSS($_GET['messages_page_nr']).$social_link.'');
		exit;
	}
}

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
//$htmlHeadXtra[] = '<script type="text/javascript" src="/main/inc/lib/javascript/jquery.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';

/*
		MAIN CODE
*/

//$nameTools = get_lang('Messages');

//api_display_tool_title(api_xml_http_response_encode(get_lang('Inbox')));
if ($_GET['f']=='social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/social/home.php','name' => get_lang('Social'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Outbox'));	
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/auth/profile.php','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Inbox'));
}

Display::display_header('');

// Display actions
echo '<div class="actions">';
if (api_get_setting('allow_social_tool') == 'true') {
  echo '<a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.Display::return_icon('pixel.gif',get_lang('Home'),array('class' => 'toolactionplaceholdericon toolactionshome')).get_lang('Home').'</a>';
} else {
  $social_parameter = '';
  if ($_GET['f']=='social' || api_get_setting('allow_social_tool') == 'true') {
    $social_parameter = '?f=social';
  } else {
    echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php?type=reduced">'.Display::return_icon('pixel.gif', get_lang('EditNormalProfile'),array('class'=>'actionplaceholdericon actionedit')).'&nbsp;'.get_lang('EditNormalProfile').'</a>';
  }
}
echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('pixel.gif',get_lang('Inbox'), array('class' => 'toolactionplaceholdericon toolactioninbox')).get_lang('Inbox').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php?f=social">'.Display::return_icon('pixel.gif',get_lang('Inbox'), array('class' => 'toolactionplaceholdericon toolactionsinvite')).get_lang('ComposeMessage').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php?f=social">'.Display::return_icon('pixel.gif',get_lang('Outbox'), array('class' => 'toolactionplaceholdericon toolactionoutbox')).get_lang('Outbox').'</a>';
echo '</div>';
// Start content
echo '<div id="content">';

if ($_GET['f']=='social') {

} else {
	
	if (api_get_setting('extended_profile') == 'true') {	
		if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'&nbsp;'.get_lang('ViewSharedProfile').'</a>';
		}
		if (api_get_setting('allow_message_tool') == 'true') {
			echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png').' '.get_lang('Messages').'</a>';
		}	
		echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php?type=reduced">'.Display::return_icon('pixel.gif', get_lang('EditNormalProfile'),array('class'=>'actionplaceholdericon actionedit')).'&nbsp;'.get_lang('EditNormalProfile').'</a>';
	}
}

$info_delete_outbox=array();
$info_delete_outbox=explode(',',$_GET['form_delete_outbox']);
$count_delete_outbox=(count($info_delete_outbox)-1);

if( trim($info_delete_outbox[0])=='delete' ) {
	for ($i=1;$i<=$count_delete_outbox;$i++) {
		MessageManager::delete_message_by_user_sender(api_get_user_id(),$info_delete_outbox[$i]);
	}
		$message_box=get_lang('SelectedMessagesDeleted').
			'&nbsp
			<br><a href="../social/index.php?#remote-tab-3">'.
			get_lang('BackToOutbox').
			'</a>';
		Display::display_normal_message(api_xml_http_response_encode($message_box),false);
	    exit;
}

$table_message = Database::get_main_table(TABLE_MESSAGE);

$user_sender_id=api_get_user_id();

echo '<div id="social-content">';
	$id_content_right = '';
	//LEFT COLUMN	
	if (api_get_setting('allow_social_tool') != 'true') { 
		$id_content_right = 'outbox';
	} else {
		require_once api_get_path(LIBRARY_PATH).'social.lib.php';
		$id_content_right = 'social-content-right';
		echo '<div id="social-content-left">';	
			//this include the social menu div
			SocialManager::show_social_menu('messages_outbox');
		echo '</div>';			
	}
	
	echo '<div id="'.$id_content_right.'">';
			//MAIN CONTENT
			if ($_REQUEST['action']=='delete') {
				$delete_list_id=array();
				if (isset($_POST['out'])) {
					$delete_list_id=$_POST['out'];
				}
				if (isset($_POST['id'])) {
					$delete_list_id=$_POST['id'];
				}
				for ($i=0;$i<count($delete_list_id);$i++) {
					MessageManager::delete_message_by_user_sender(api_get_user_id(), $delete_list_id[$i]);
				}
                //exit;
				$delete_list_id=array();
				
				
				outbox_display();
				
			} elseif ($_REQUEST['action']=='deleteone') {
				$delete_list_id=array();
				$id=Security::remove_XSS($_GET['id']);
				MessageManager::delete_message_by_user_sender(api_get_user_id(),$id);
				$delete_list_id=array();
				outbox_display();
			}else {
				outbox_display();
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