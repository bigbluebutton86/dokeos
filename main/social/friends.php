<?php
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

$language_file = array('userInfo');
$cidReset=true;
require '../inc/global.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

api_block_anonymous_users();

$this_section = SECTION_SOCIAL;

//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';
$htmlHeadXtra[] = '<script type="text/javascript">

function delete_friend (element_div) {
	id_image=$(element_div).attr("id");
	user_id=id_image.split("_");
	if (confirm("'.get_lang('Delete', '').'")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			type: "POST",
			url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=delete_friend",
			data: "delete_friend_id="+user_id[1],
			success: function(datos) {
			 $("div#"+"div_"+user_id[1]).hide("slow");
			 $("div#"+"div_"+user_id[1]).html("");
			 clear_form ();
			}
		});
	}
}
			
		
function search_image_social(element_html)  {
	name_search=$(element_html).attr("value");
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=show_my_friends",
		data: "search_name_q="+name_search,
		success: function(datos) {
			$("div#div_content_table").html(datos);
		}
	});
}
		
function show_icon_delete(element_html) {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/delete.gif");
	$(ident).attr("alt","'.get_lang('Delete', '').'");
	$(ident).attr("title","'.get_lang('Delete', '').'");
}
		

function hide_icon_delete(element_html)  {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/blank.gif");
	$(ident).attr("alt","");
	$(ident).attr("title","");
}
		
function clear_form () {
	$("input[@type=radio]").attr("checked", false);
	$("div#div_qualify_image").html("");
	$("div#div_info_user").html("");
}
	
		
function show_icon_edit(element_html) {	
	ident="#edit_image";
	$(ident).show();
}		

function hide_icon_edit(element_html)  {
	ident="#edit_image";
	$(ident).hide();
}		
		
</script>';

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Friends'));

Display :: display_header($tool_name, 'Groups');

// Display actions
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.Display::return_icon('pixel.gif',get_lang('Home'), array('class' => 'toolactionplaceholdericon toolactionshome')).get_lang('Home').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('pixel.gif', get_lang('Messages'), array('class' => 'toolactionplaceholdericon toolactionsmessage')).get_lang('Messages').$count_unread_message.'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('pixel.gif',get_lang('Invitations'), array('class' => 'toolactionplaceholdericon toolactionsinvite')).get_lang('Invitations').$total_invitations.'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('pixel.gif',get_lang('ViewMySharedProfile'), array('class' => 'toolactionplaceholdericon toolactionsprofile')).get_lang('ViewMySharedProfile').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/friends.php">'.Display::return_icon('pixel.gif',get_lang('Friends'), array('class' => 'toolactionplaceholdericon toolactionsfriend')).get_lang('Friends').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/groups.php">'.Display::return_icon('pixel.gif',get_lang('Groups'), array('class' => 'toolactionplaceholdericon toolactionsgroup')).get_lang('Groups').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/search.php">'.Display::return_icon('pixel.gif',get_lang('Search'), array('class' => 'toolactionplaceholdericon toolactionsearch')).get_lang('Search').'</a>';
echo '</div>';
// Start content
echo '<div id="content">';

echo '<div id="social-content">';

	/*echo '<div id="social-content-left">';
		//this include the social menu div
		SocialManager::show_social_menu('friends');	
	echo '</div>';*/
	echo '<div id="social-content-all">';
	
$language_variable	= api_xml_http_response_encode(get_lang('Contacts'));
$user_id	= api_get_user_id();

$list_path_friends	= array();
$user_id	= api_get_user_id();
$name_search= Security::remove_XSS($_POST['search_name_q']);
$number_friends = 0;

if (!empty($name_search) && $name_search!='undefined') {
	$friends = SocialManager::get_friends($user_id,null,$name_search);
} else {
	$friends = SocialManager::get_friends($user_id);
}

if (count($friends) == 0 ) {
	echo get_lang('NoFriendsInYourContactList').'<br /><br />';
	echo '<a href="search.php">'.get_lang('TryAndFindSomeFriends').'</a>';	
} else {
	
	?>
	<div align="center" >
	<table width="100%" border="0" cellpadding="0" cellspacing="0" align="left" >
	  <tr>
	    <td height="25" valign="left">
	    <table width="100%" border="0" cellpadding="0" cellspacing="0" >
	      <tr>
              <td width="100%"  align="left" class="social-align-box">
                <?php api_display_tool_title(get_lang('Search')); ?>
                  <input class="social-search-image" type="text" id="id_search_image" name="id_search_image" value="" onkeyup="search_image_social(this)" />
              </td>
	      </tr>
	    </table></td>
	  </tr>
	  <tr>
	    <td height="175" valign="top">
	    <table width="100%" border="0" cellpadding="0" cellspacing="0" >
	      <tr>
			<td height="153" valign="top">
				<?php
				echo '<div class="social-box-container2" align="center">';
				echo '<div id="div_content_table">';
					$friend_html = '';
					$number_of_images = 8;
					
					$number_friends = count($friends);
					$j=0;
                    $my_user_id = api_get_user_id();
					echo '<div id ="social-content-right">';
					$friend_html.= '<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="" >';
					for ($k=0;$k<$number_friends;$k++) {
						$friend_html.='<tr><td valign="top">';
					
						while ($j<$number_friends) {
							if (isset($friends[$j])) {
								$friend = $friends[$j];
								$user_name = api_xml_http_response_encode($friend['firstName'].' '.$friend['lastName']);
								$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92);
                                // Add delete iconf if users is external friend
                                $add_remove_events = '';
                                $add_contact_form = '';
                                if ($friend['contact_type'] <> 0) {
                                    $invitation_sent_list = SocialManager::get_list_invitation_sent_by_user_id($my_user_id);
                                    if (is_array($invitation_sent_list) && is_array($invitation_sent_list[$friend['friend_user_id']]) && count($invitation_sent_list[$friend['friend_user_id']]) <>0 ) {
                                        $add_contact_form =  '<a href="'.api_get_path(WEB_PATH).'main/messages/send_message_to_userfriend.inc.php?view_panel=2&height=260&width=610&user_friend='.$friend['friend_user_id'].'" class="thickbox" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('invitation_22.png', get_lang('SocialInvitationToFriends')).'</a>';
                                    }
                                    $add_remove_events = 'onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)"'; 
                                }
                                // Icon is no added if the contact is direct, for example a session/course/group friend
								$friend_html.='<div  '.$add_remove_events.' class="image-social-content" id=div_'.$friends[$j]['friend_user_id'].'>';
								$friend_html.='<span><a href="profile.php?u='.$friend['friend_user_id'].'"><center><img src="'.$friends_profile['file'].'" style="height:60px;border:3pt solid #eee" id="imgfriend_'.$friend['friend_user_id'].'" title="'.$user_name.'" /></center></a></span>';
								$friend_html.='<img onclick="delete_friend (this)" id=img_'.$friend['friend_user_id'].' src="../img/blank.gif" alt="" title=""  class="image-delete" /> <center class="friend">'.$user_name.'<br/>'.$add_contact_form.'</center></div>';				
							}
							$j++;
						}
						$friend_html.='</td></tr>';
					}
					$friend_html.='<br/></table>';
					echo '</div>';
					echo $friend_html;
				echo '</div>';
				echo '</div>';
				?>
			</td>
	        </tr>
	    </table></td>
	  </tr>
	</table>
	</div>
	<?php	
		
	}	
		echo '</div>';
	echo '</div>';	

// End content
echo '</div>';

// Actions
echo '<div class="actions">';
echo '</div>';

Display :: display_footer();
?>