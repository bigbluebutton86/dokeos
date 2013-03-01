<?php // $Id: chat_message.php,v 1.11 2005/05/18 13:58:20 bvanderkimpen Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

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
*	Allows to type the messages that will be displayed on chat_chat.php
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
define('FRAME','message');

$language_file = array ('chat');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
$course=api_get_course_id();
$session_id = intval($_SESSION['id_session']);
$group_id 	= intval($_SESSION['_gid']);

/////
// Juan Carlos Raña insert smileys and self-closing window
////
?>
<script language="javascript" type="text/javascript">
function insert_smile(text) {
	if (text.createTextRange) {
    text.smile = document.selection.createRange().duplicate();
   	}
}

function insert(text) {
	var chat = document.formMessage.message;
   	if (chat.createTextRange && chat.smile) {
    	var smile = chat.smile;
    	smile.text = smile.text.charAt(smile.text.length - 1) == ' ' ? text + ' ' : text;
   	}
   	else chat.value += text;
   	chat.focus(smile)
}

function close_chat_window() {
	var chat_window = top.window.self;
	chat_window.opener = top.window.self;
	chat_window.top.close();
}


</script>

<?php

// mode open in a new window: close the window when there isn't an user login

if(empty($_user['user_id']))
{
	echo '<script languaje="javascript"> close_chat_window() </script>';
}
else
{
	api_protect_course_script();
}

// if we have the session set up
if (!empty($course) && !empty($_user['user_id']))
{
	include_once(api_get_path(LIBRARY_PATH).'document.lib.php');
	include_once(api_get_path(LIBRARY_PATH).'text.lib.php');
	include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

	/*
	-----------------------------------------------------------
		Constants and variables
	-----------------------------------------------------------
	*/
	$tbl_user	= Database::get_main_table(TABLE_MAIN_USER);
	$sent = $_REQUEST['sent'];
    
	/*
	==============================================================================
			MAIN CODE
	==============================================================================
	*/
	$query="SELECT lastname, firstname, username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
	$result=Database::query($query,__FILE__,__LINE__);

	list($pseudoUser)=Database::fetch_row($result);

	$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
	$isMaster=$is_courseAdmin?true:false;

	$firstname=Database::result($result,0,'firstname');
	$lastname=Database::result($result,0,'lastname');

	$dateNow=date('Y-m-d');
	
	$basepath_chat = '';		
	$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
	if (!empty($group_id)) {
		$group_info = GroupManager :: get_group_properties($group_id);
		$basepath_chat = $group_info['directory'].'/chat_files';		
	} else {
		$basepath_chat = '/chat_files';				
	}
	$chatPath=$documentPath.$basepath_chat.'/';
	
	$TABLEITEMPROPERTY= Database::get_course_table(TABLE_ITEM_PROPERTY);

	if(!is_dir($chatPath)) {
		if(is_file($chatPath)) {
			@unlink($chatPath);
		}
		if (!api_is_anonymous()) {
			$perm = api_get_setting('permissions_for_new_directories');
			$perm = octdec(!empty($perm)?$perm:'0770');
			@mkdir($chatPath,$perm);
			@chmod($chatPath,$perm);
			// save chat files document for group into item property	
			if (!empty($group_id)) {
				$doc_id=add_document($_course,$basepath_chat,'folder',0,'chat_files');
				$sql = "INSERT INTO $TABLEITEMPROPERTY (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
						VALUES ('document',1,NOW(),NOW(),$doc_id,'FolderCreated',1,$group_id,NULL,0)";
				Database::query($sql,__FILE__,__LINE__);		
			}			
		}
	}

	include('header_frame.inc.php');
	$chat_size=0;

	//define emoticons
	$emoticon_text1=':-)';
	$emoticon_img1= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_smile.png" alt="'.get_lang('Smile').'" title="'.get_lang('Smile').'" />';
	$emoticon_text2=':-D';
	$emoticon_img2= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_smile_big.png" alt="'.get_lang('BigGrin').'" title="'.get_lang('BigGrin').'" />';
	$emoticon_text3=';-)';
	$emoticon_img3= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_wink.png" alt="'.get_lang('Wink').'" title="'.get_lang('Wink').'" />';
	$emoticon_text4=':-P';
	$emoticon_img4= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_raspberry.png" alt="'.get_lang('Rasp').'" title="'.get_lang('Rasp').'" />';
	$emoticon_text5='8-)';
	$emoticon_img5= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_cool.png" alt="'.get_lang('Cool').'" title="'.get_lang('Cool').'" />';
	$emoticon_text6=':-o)';
	$emoticon_img6= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_surprise.png" alt="'.get_lang('Surprised').'" title="'.get_lang('Surprised').'" />';
	$emoticon_text7='=;';
	$emoticon_img7= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_uncertain.png" alt="'.get_lang('Uncertain').'" title="'.get_lang('Uncertain').'" />';
	$emoticon_text8='=8-o';
	$emoticon_img8= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_angry.png" alt="'.get_lang('Angry').'" title="'.get_lang('Angry').'" />';
	$emoticon_text9=':-|)';
	$emoticon_img9= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_laugh.png" alt="'.get_lang('Laugh').'" title="'.get_lang('Laugh').'" />';
	$emoticon_text10=':-k';
	$emoticon_img10= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_sad.png" alt="'.get_lang('Sad').'" title="'.get_lang('Sad').'" />';
	$emoticon_text11=':-?';
	$emoticon_img11= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_embarrassed.png" alt="'.get_lang('Embarrassed').'" title="'.get_lang('Embarrassed').'" />';
	$emoticon_text12=':-8';
	$emoticon_img12= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_tired.png" alt="'.get_lang('Tired').'" title="'.get_lang('Tired').'" />';
	$emoticon_text13=':-=';
	$emoticon_img13= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_worried.png" alt="'.get_lang('Worried').'" title="'.get_lang('Worried').'" />';
	$emoticon_text14=':-#)';
	$emoticon_img14= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_crying.png" alt="'.get_lang('Cry').'" title="'.get_lang('Cry').'" />';
	$emoticon_text15=':-(';
	$emoticon_img15= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_plain.png" alt="'.get_lang('Plain').'" title="'.get_lang('Plain').'" />';
	$emoticon_text16=':-[8';
	$emoticon_img16= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_angel.png" alt="'.get_lang('Angel').'" title="'.get_lang('Angel').'" />';
	$emoticon_text17='--)';
	$emoticon_img17= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/new_smileys/icon_devilish.png" alt="'.get_lang('Devil').'" title="'.get_lang('Devil').'" />';
	$emoticon_text18=':!:';
/*	$emoticon_img18= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/icon_exclaim.gif" alt="'.get_lang('Exclamation').'" title="'.get_lang('Exclamation').'" />';
	$emoticon_text19=':?:';
	$emoticon_img19= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/icon_question.gif" alt="'.get_lang('Question').'" title="'.get_lang('Question').'" />';
	$emoticon_text20='0-';
	$emoticon_img20= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/icon_idea.gif" alt="'.get_lang('Idea').'" title="'.get_lang('Idea').'" />';
  //
	$emoticon_text201='*';
	$emoticon_img201= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/waiting.gif" alt="'.get_lang('AskPermissionSpeak').'" title="'.get_lang('AskPermissionSpeak').'" />';
	$emoticon_text202=':speak:';
	$emoticon_img202= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/flag_green_small.gif" alt="'.get_lang('GiveTheFloorTo').'" title="'.get_lang('GiveTheFloorTo').'" />';
	$emoticon_text203=':pause:';
	$emoticon_img203= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/flag_yellow_small.gif" alt="'.get_lang('Pause').'" title="'.get_lang('Pause').'" />';
	$emoticon_text204=':stop:';
	$emoticon_img204= '<img src="'.api_get_path(WEB_IMG_PATH).'smileys/flag_red_small.gif" alt="'.get_lang('Stop').'" title="'.get_lang('Stop').'" />';*/
	if(($sent || (isset($_SESSION['set_chat_message']) && $_SESSION['set_chat_message'] >=1)))
	{   
		$message=trim(htmlspecialchars(stripslashes($_POST['message']),ENT_QUOTES,$charset));
		if (isset($_SESSION['set_chat_message']) && $_SESSION['set_chat_message'] == 1) {
			$message = api_get_person_name($firstname).' '.get_lang('IsInTheChatRightNow');
		} elseif (isset($_SESSION['set_chat_message']) && $_SESSION['set_chat_message'] == 2) {
			$message = get_lang('AnInvitationHasBeenSentTo').' '.$_SESSION['to_user'];
		}
		
		$message=str_replace($emoticon_text1, $emoticon_img1, $message);
		$message=str_replace($emoticon_text2, $emoticon_img2, $message);
		$message=str_replace($emoticon_text3, $emoticon_img3, $message);
		$message=str_replace($emoticon_text4, $emoticon_img4, $message);
		$message=str_replace($emoticon_text5, $emoticon_img5, $message);
		$message=str_replace($emoticon_text6, $emoticon_img6, $message);
		$message=str_replace($emoticon_text7, $emoticon_img7, $message);
		$message=str_replace($emoticon_text8, $emoticon_img8, $message);
		$message=str_replace($emoticon_text9, $emoticon_img9, $message);
		$message=str_replace($emoticon_text10, $emoticon_img10, $message);
		$message=str_replace($emoticon_text11, $emoticon_img11, $message);
		$message=str_replace($emoticon_text12, $emoticon_img12, $message);
		$message=str_replace($emoticon_text13, $emoticon_img13, $message);
		$message=str_replace($emoticon_text14, $emoticon_img14, $message);
		$message=str_replace($emoticon_text15, $emoticon_img15, $message);
		$message=str_replace($emoticon_text16, $emoticon_img16, $message);
		$message=str_replace($emoticon_text17, $emoticon_img17, $message);
	/*	$message=str_replace($emoticon_text18, $emoticon_img18, $message);
 		$message=str_replace($emoticon_text19, $emoticon_img19, $message);
		$message=str_replace($emoticon_text20, $emoticon_img20, $message);
		//
		$message=str_replace($emoticon_text201, $emoticon_img201, $message);
		$message=str_replace($emoticon_text202, $emoticon_img202, $message);
		$message=str_replace($emoticon_text203, $emoticon_img203, $message);
		$message=str_replace($emoticon_text204, $emoticon_img204, $message);*/

		$timeNow=date('d/m/y H:i:s');
		
		$basename_chat = '';
		if (!empty($group_id)) {
			$basename_chat = 'messages-'.$dateNow.'_gid-'.$group_id;
		} else if (!empty($session_id)) {
			$basename_chat = 'messages-'.$dateNow.'_sid-'.$session_id;
		} else {
			$basename_chat = 'messages-'.$dateNow;				
		}
		
		if (!api_is_anonymous()) {
			if(!empty($message))
			{
				$message=make_clickable($message);

				if(!file_exists($chatPath.$basename_chat.'.log.html'))
				{
					$doc_id=add_document($_course,$basepath_chat.'/'.$basename_chat.'.log.html','file',0,$basename_chat.'.log.html');

					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'],$group_id,null,null,null,$session_id);
					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id'],$group_id,null,null,null,$session_id);
					item_property_update_on_folder($_course,$basepath_chat, $_user['user_id']);
				}
				else
				{
					$doc_id = DocumentManager::get_document_id($_course,$basepath_chat.'/'.$basename_chat.'.log.html');
				}

				$fp=fopen($chatPath.$basename_chat.'.log.html','a');
                
				$title_name = api_get_person_name($firstname);
				if (isset($_SESSION['set_chat_message'])) {
					$title_name = get_lang("Message");
				}
				
				if($isMaster) {
				//	$photo= '<img src="'.api_get_path(WEB_IMG_PATH).'teachers.gif" alt="'.get_lang('Teacher').'"  width="11" height="11" align="top"  title="'.get_lang('Teacher').'"  />';
				//	fputs($fp,'<span style="color:#999; font-size: smaller;"></span>'.$photo.' <span id="chat_login_name"><b>'.api_get_person_name($firstname).'</b></span> : <i>'.$message.'</i><br>'."\n");
					fputs($fp,'<table width="100%"><tr style="font-size:smaller;"><td width="10%" valign="top" id="chat_login_name" width="90%"><b>'.$title_name.':</b> </td><td><i>'.$message.'</i></td></tr></table>');
				} else {
				//	$photo= '<img src="'.api_get_path(WEB_IMG_PATH).'students.gif" alt="'.get_lang('Student').'"  width="11" height="11" align="top"  title="'.get_lang('Student').'"  />';
				//	 fputs($fp,'<span style="color:#999; font-size: smaller;"></span>'.$photo.' <b>'.api_get_person_name($firstname).'</b> : <i>'.$message.'</i><br>'."\n");
					fputs($fp,'<table width="100%"><tr style="font-size:smaller;"><td width="10%" valign="top" id="chat_login_name" width="90%"><b>'.$title_name.':</b> </td><td><i>'.$message.'</i></td></tr></table>');
				}
				
				fclose($fp);
				// Remove sessions used for display the chat messages
                                unset($_SESSION['set_chat_message']);
                                unset($_SESSION['to_user']);
				$chat_size=filesize($chatPath.$basename_chat.'.log.html');

				update_existing_document($_course, $doc_id,$chat_size);
				item_property_update_on_folder($_course,$basepath_chat, $_user['user_id']);
			}
		}
	}
	?>
	<style>
	a.chat
	{
		padding:0px;
	}
	</style>
	<!--<div class="actions" valign="top" style="width:600px;height:90px;">-->
	<?php
	if (!ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
	echo '<div class="actions" valign="top" style="width:615px;height:93px;">';
	}
	else
	{
	echo '<div class="actions" valign="top" style="width:615px;height:93px;">';
	}
	?>
	<table width="100%"><tr><td width="70%">
		<table width="100%">
		<tr><td><form name="formMessage" method="post" action="<?php echo api_get_self().'?'.api_get_cidreq(); ?>" onsubmit="javascript:if(document.formMessage.message.value == '') { alert('<?php echo addslashes(api_htmlentities(get_lang('TypeMessage'),ENT_QUOTES,$charset)); ?>'); document.formMessage.message.focus(); return false; }" autocomplete="off">
	<input type="hidden" name="sent" value="1"><textarea name="message" style="width: 350px; height: 45px" onkeydown="send_message(event);" onclick="javascript:insert_smile(this);"></textarea></td></tr>
		<tr><td><?php
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text1."')\">".$emoticon_img1."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text2."')\">".$emoticon_img2."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text3."')\">".$emoticon_img3."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text4."')\">".$emoticon_img4."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text5."')\">".$emoticon_img5."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text6."')\">".$emoticon_img6."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text7."')\">".$emoticon_img7."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text8."')\">".$emoticon_img8."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text9."')\">".$emoticon_img9."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text10."')\">".$emoticon_img10."</a>";
		echo "<a class='chat' href=\"javascript:insert('".$emoticon_text11."')\">".$emoticon_img11."</a>";

		?></td></tr>
		</table>
	</td>
	<td width="30%" valign="top"><button type="submit" value="<?php echo get_lang("Send"); ?>" class="save" ><?php echo get_lang("Send"); ?></button></td></tr></table>
	</form>
	</div>
<?php

}
include('footer_frame.inc.php');
?>
