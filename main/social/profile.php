<?php //$id: $
/* For licensing terms, see /license.txt */
/**
* This is the profile social main page
* @author Julio Montoya <gugli100@gmail.com>
* @author Isaac Flores Paz <florespaz_isaac@hotmail.com>
* @package dokeos.social
*/

$language_file = array('userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'array.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$user_id = api_get_user_id();
$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;

//I'm your friend? I can see your profile?
if (isset($_GET['u'])) {
	$user_id 	= (int) Database::escape_string($_GET['u']);
	// It's me!
	if (api_get_user_id() != $user_id) {
		$user_info	= UserManager::get_user_info_by_id($user_id);
		$show_full_profile = false;
		if (!$user_info) {
			// user does no exist !!
			api_not_allowed();
		} else {
			//checking the relationship between me and my friend
			$my_status= SocialManager::get_relation_between_contacts(api_get_user_id(), $user_id);
			if (in_array($my_status, array(USER_RELATION_TYPE_PARENT, USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_GOODFRIEND))) {
				$show_full_profile = true;
			}
			//checking the relationship between my friend and me
			$my_friend_status = SocialManager::get_relation_between_contacts($user_id, api_get_user_id());
			if (in_array($my_friend_status, array(USER_RELATION_TYPE_PARENT, USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_GOODFRIEND))) {
				$show_full_profile = true;
			} else {
				// im probably not a good friend
				$show_full_profile = false;
			}
		}
	} else {
		$user_info	= UserManager::get_user_info_by_id($user_id);
	}
} else {
	$user_info	= UserManager::get_user_info_by_id($user_id);
}


// If user is my contact or users is enrolled in my course or session
if ($show_full_profile === false) {
    $my_friend_status= SocialManager::get_relation_between_contacts(api_get_user_id(), $user_id, true);
    if (in_array($my_friend_status, array(USER_RELATION_TYPE_PARENT, USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_GOODFRIEND))) {
        $show_full_profile = true;
    } else {
        // im probably not a good friend
        $show_full_profile = false;
    }
}
$libpath = api_get_path(LIBRARY_PATH);
require_once api_get_path(LIBRARY_PATH).'announcements.inc.php';
require_once $libpath.'course.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';
require_once $libpath.'magpierss/rss_fetch.inc';

api_block_anonymous_users();

//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.corners.min.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';
$htmlHeadXtra[] = '
<script type="text/javascript">
function toogle_course (element_html, course_code){
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="div#div_group_"+id_elem[1];

	id_button="#btn_"+id_elem[1];
	elem_src=$(id_button).attr("src");
	image_show=elem_src.split("/");
	my_image=image_show[2];
	var content = \'social_content\' + id_elem[1];
	if (my_image=="nolines_plus.gif") {
		$(id_button).attr("src","../img/nolines_minus.gif"); var action = "load_course";
		$("div#"+content).show("slow");
	} else {
		$("div#"+content).hide("slow");
		$(id_button).attr("src","../img/nolines_plus.gif"); var action = "unload";
		return false;
	}

	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("div#"+content).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=toogle_course",
		data: "load_ajax="+id_elem+"&action="+action+"&course_code="+course_code,
		success: function(datos) {
		 //$("div#"+name_div_id).hide("slow");
		 $("div#"+content).html(datos);
		}
	});
}
</script>';
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
			$("#id_content_panel").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
			type: "POST",
			url: "../messages/send_message.php",
			data: "panel_id="+mypanel_id+"&user_id="+myuser_id,
			success: function(datos) {
			 $("div#id_content_panel_init").html(datos);
			 $("div#display_response_id").html("");
			}
		});
}
function action_database_panel (option_id, myuser_id) {
	if (option_id==5) {
		my_txt_subject=$("#txt_subject_id").val();
	} else {
		my_txt_subject="clear";
	}
	my_txt_content=$("#txt_area_invite").val();

	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#display_response_id").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "../messages/send_message.php",
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
function register_friend(element_input) {
 if(confirm("'.get_lang('AddToFriends').'")) {
		name_button=$(element_input).attr("id");
		name_div_id="id_"+name_button.substring(13);
		user_id=name_div_id.split("_");
		user_friend_id=user_id[1];
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
			type: "POST",
			url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=add_friend",
			data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
			success: function(datos) {
				$("form").submit()
			}
		});
 }
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
$nametool = get_lang('Social');
if (isset($_GET['shared'])) {
	$my_link='../social/profile.php';
	$link_shared='shared='.Security::remove_XSS($_GET['shared']);
} else {
	$my_link='../social/profile.php';
	$link_shared='';
}
$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social') );
$interbreadcrumb[]= array ('url' => 'profile.php?u='.api_get_user_id(), 'name' => get_lang('ViewMySharedProfile'));

if (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] != api_get_user_id()) {
	$info_user=api_get_user_info($_GET['u']);
	$interbreadcrumb[]= array ('url' => '#','name' => api_get_person_name($info_user['firstName'], $info_user['lastName']));
	$nametool = '';
}
if (isset($_GET['u'])) {
	$param_user='u='.Security::remove_XSS($_GET['u']);
}else {
	$info_user=api_get_user_info(api_get_user_id());
	$param_user='';
}
$_SESSION['social_user_id'] = intval($user_id);

/**
 * Display
 */
Display :: display_header($nametool);

// Display actions
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.Display::return_icon('pixel.gif',get_lang('Home'),array('class' => 'toolactionplaceholdericon toolactionshome')).get_lang('Home').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('pixel.gif', get_lang('Messages'),array('class' => 'toolactionplaceholdericon toolactionsmessage')).get_lang('Messages').$count_unread_message.'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('pixel.gif',get_lang('Invitations'), array('class' => 'toolactionplaceholdericon toolactionsinvite')).get_lang('Invitations').$total_invitations.'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('pixel.gif',get_lang('ViewMySharedProfile'), array('class' => 'toolactionplaceholdericon toolactionsprofile')).get_lang('ViewMySharedProfile').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/friends.php">'.Display::return_icon('pixel.gif',get_lang('Friends'), array('class' => 'toolactionplaceholdericon toolactionsfriend')).get_lang('Friends').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/groups.php">'.Display::return_icon('pixel.gif',get_lang('Groups'), array('class' => 'toolactionplaceholdericon toolactionsgroup')).get_lang('Groups').'</a>';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/search.php">'.Display::return_icon('pixel.gif',get_lang('Search'), array('class' => 'toolactionplaceholdericon toolactionsearch')).get_lang('Search').'</a>';
echo '</div>';
// Start content
echo '<div id="content">';

// Added by Ivan Tcholakov, 03-APR-2009.
if (USE_JQUERY_CORNERS_SCRIPT) {
	echo $s="<script>$(document).ready( function(){
		  $('.rounded').corners();
		});</script>";
}

//Setting some course info
$my_user_id=isset($_GET['u']) ? Security::remove_XSS($_GET['u']) : api_get_user_id();
$personal_course_list = UserManager::get_personal_session_course_list($my_user_id);
$course_list_code = array();
$i=1;
//print_r($personal_course_list);

if (is_array($personal_course_list)) {
	foreach ($personal_course_list as $my_course) {
		if ($i<=10) {
			$list[] = SocialManager::get_logged_user_course_html($my_course,$i);
			//$course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db'], 'title'=>$my_course['i']); cause double
			$course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db']);

		} else {
			break;
		}
		$i++;
	}
	//to avoid repeted courses
	$course_list_code = array_unique_dimensional($course_list_code);
}

$user_online_list = who_is_online(api_get_setting('time_limit_whosonline'), true);
$user_online_count = count($user_online_list);

echo '<div id="social-content">';
echo '  <div id="social-content-left">';
//this include the social menu div
SocialManager::show_social_menu('shared_profile', null, $user_id, $show_full_profile);
echo '  </div>';

echo '  <div id="social-content-right">';
echo '    <div class="social-box-main1">';
echo '      <div class="social-box-left quiz_content_actions" style="min-height: 250px;">';

echo '          <div class="social-box-content1">';
if (!empty($user_info['firstname']) || !empty($user_info['lastname'])) {
	echo '            <div><h3>'.api_get_person_name($user_info['firstname'], $user_info['lastname']).'</h3></div>';
} else {
	//--- Basic Information
	echo '            <div><h3>'.get_lang('Information').'</h3></div>';
}

if ($show_full_profile) {
	echo '<div class="social-profile-info">';
	echo '<div><p><strong>'.get_lang('UserName').'</strong><br><span class="social-groups-text4">'. $user_info['username'].'</span></p></div>';
	if (!empty($user_info['firstname']) || !empty($user_info['lastname']))
		echo '<div><p><strong>'.get_lang('Name').'</strong><br><span class="social-groups-text4">'. api_get_person_name($user_info['firstname'], $user_info['lastname']).'</span></p></div>';
	if (!empty($user_info['official_code']))
		echo '<div><p><strong>'.get_lang('OfficialCode').'</strong><br><span class="social-groups-text4">'.$user_info['official_code'].'</span></p></div>';
	if (!empty($user_info['email']))
		if (api_get_setting('show_email_addresses')=='true')
			echo '<div><p><strong>'.get_lang('Email').'</strong><br><span class="social-groups-text4">'.$user_info['email'].'</span></p></div>';
		if (!empty($user_info['phone']))
			echo '<div><p><strong>'.get_lang('Phone').'</strong><br><span class="social-groups-text4">'. $user_info['phone'].'</span></p></div>';

	echo '</div>';
} else {
	echo '<div class="social-profile-info">';
	if (!empty($user_info['username']))
		echo '<div><p><strong>'.get_lang('UserName').'</strong><br><span class="social-groups-text4">'. $user_info['username'].'</span></p></div>';
	echo '</div>';
}

echo '<div class="clear"></div>';

// Extra information
if ($show_full_profile) {
	//-- Extra Data
	$t_uf	= Database :: get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufo	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
	$extra_user_data = UserManager::get_extra_user_data($user_id);
	$extra_information = '';
	if (is_array($extra_user_data) && count($extra_user_data)>0 ) {
		$extra_information = '<br />';
		$extra_information .= '<div><h3>'.get_lang('ExtraInformation').'</h3></div>';
		$extra_information .='<div class="social-profile-info">';
		$extra_information_value = '';
		foreach($extra_user_data as $key=>$data) {
			// get display text, visibility and type from user_field table
			$field_variable = str_replace('extra_','',$key);
			$sql = "SELECT field_display_text,field_visible,field_type,id FROM $t_uf WHERE field_variable ='$field_variable'";
			$res_field = Database::query($sql);
			$row_field = Database::fetch_row($res_field);
			$field_display_text = $row_field[0];
			$field_visible = $row_field[1];
			$field_type = $row_field[2];
			$field_id = $row_field[3];
			if ($field_visible == 1) {
				if (is_array($data)) {
					$extra_information_value .= '<div><p><strong>'.ucfirst($field_display_text).'</strong><br><span class="social-groups-text4">'.implode(',',$data).'</span></p></div>';
				} else {
					if ($field_type == USER_FIELD_TYPE_DOUBLE_SELECT) {
						$id_options = explode(';',$data);
						$value_options = array();
						// get option display text from user_field_options table
						foreach ($id_options as $id_option) {
							$sql = "SELECT option_display_text FROM $t_ufo WHERE id = '$id_option'";
							$res_options = Database::query($sql);
							$row_options = Database::fetch_row($res_options);
							$value_options[] = $row_options[0];
						}
						$extra_information_value .= '<div><p><strong>'.ucfirst($field_display_text).':</strong><br><span class="social-groups-text4">'.implode(' ',$value_options).'</span></p></div>';
					} elseif($field_type == USER_FIELD_TYPE_TAG ) {
						$user_tags = UserManager::get_user_tags($user_id, $field_id);
						$tag_tmp = array();
						foreach ($user_tags as $tags) {
							//$tag_tmp[] = $tags['tag'];
							$tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tags['tag'].'">'.$tags['tag'].'</a>';
						}
						if (is_array($user_tags) && count($user_tags)>0) {
							$extra_information_value .= '<div><p><strong>'.ucfirst($field_display_text).':</strong><br><span class="social-groups-text4">'.implode(', ',$tag_tmp).'</span></p></div>';
						}
					} elseif ($field_type == USER_FIELD_TYPE_SOCIAL_PROFILE) {
						$icon_path = UserManager::get_favicon_from_url($data);
						$bottom = '0.3';
						//quick hack for hi5
						$domain = parse_url($icon_path, PHP_URL_HOST); if ($domain == 'www.hi5.com' or $domain == 'hi5.com') { $bottom = '0.8'; }
						$data = '<a href="'.$data.'"><img src="'.$icon_path.'" alt="ico" style="margin-right:0.5em;margin-bottom:-'.$bottom.'em;" />'.ucfirst($field_display_text).'</a>';
						$extra_information_value .= '<div><p><strong>'.$data.'</strong><br>';
					} else {
						if (!empty($data)) {
							$extra_information_value .= '<div><p><strong>'.ucfirst($field_display_text).':</strong><br><span class="social-groups-text4">'.$data.'</span></p></div>';
						}
					}
				}
			}
		}
		// if there are information to show
		if (!empty($extra_information_value)) {
			$extra_information .= $extra_information_value;
		}
		$extra_information .= '</div>';
	}
	// 	if there are information to show
	if (!empty($extra_information_value)) echo $extra_information;
}

echo '  </div>'; // close div tag .social-box-content1

echo '</div>';	// close div tag .social-box-left


if ($show_full_profile) {

	echo '<div class="social-box-right quiz_content_actions" style="min-height: 250px;">';
	echo '<div class="social-box-content1">';

	$list_path_friends= $list_path_normal_friends = $list_path_parents = array();

	//SOCIALGOODFRIEND , USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_PARENT
	$friends = SocialManager::get_friends($user_id, USER_RELATION_TYPE_FRIEND);

	$friend_html		= '';
	$number_of_images	= 6;
	$number_friends		= 0;
	$list_friends_id	= array();
	$number_friends  	= count($friends);

	if ($number_friends != 0) {
		$friend_html.= '<div><h3>'.get_lang('SocialFriend').'</h3></div>';
		$friend_html.= '<div id="friend-container" class="social-friend-container">';
		$friend_html.= '<div id="friend-header" >';

		if ($number_friends == 1) {
			$friend_html.= '<div style="float:left;width:80%">'.$number_friends.' '.get_lang('Friend').'</div>';
		} else {
			$friend_html.= '<div style="float:left;width:80%">'.$number_friends.' '.get_lang('Friends').'</div>';
		}

		if ($number_friends > $number_of_images) {
			if (api_get_user_id() == $user_id) {
				$friend_html.= '<div style="float:right;width:20%"><a href="friends.php">'.get_lang('SeeAll').'</a></div>';
			} else {
				$friend_html.= '<div style="float:right;width:20%"><a href="'.api_get_path(WEB_CODE_PATH).'social/profile_friends_and_groups.inc.php?view=friends&height=390&width=610&&user_id='.$user_id.'" class="thickbox" title="'.get_lang('SeeAll').'" >'.get_lang('SeeAll').'</a></div>';
			}
		}
		$friend_html.= '</div>'; // close div friend-header

		$j=1;
		for ($k=0;$k<$number_friends;$k++) {

			if ($j > $number_of_images) break;
			if (isset($friends[$k])) {
				$friend = $friends[$k];
				$name_user	= api_get_person_name($friend['firstName'], $friend['lastName']);
				$friend_html.='<div id=div_'.$friend['friend_user_id'].' class="image_friend_network" ><span><center>';

				// the height = 92 must be the sqme in the image_friend_network span style in default.css
				$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92, USER_IMAGE_SIZE_MEDIUM);

				$friend_html.='<a href="profile.php?u='.$friend['friend_user_id'].'&amp;'.$link_shared.'">';
				$friend_html.='<img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$friend['friend_user_id'].'" title="'.$name_user.'" />';
				$friend_html.= '</center></span>';
				$friend_html.= '<center class="friend">'.$name_user.'</a></center>';
				$friend_html.= '</div>';
			}
			$j++;
		}
	} else {
		// No friends!! :(
		$friend_html .= '<div><h3>'.get_lang('SocialFriend').'</h3></div>';
		$friend_html.= '<div id="friend-container" class="social-friend-container">';
		$friend_html.= '<div id="friend-header">';
		$friend_html.= '<div style="float:left; padding:0px 8px 0px 8px;">'.get_lang('NoFriendsInYourContactList').'<br /><a href="'.api_get_path(WEB_PATH).'whoisonline.php">'.get_lang('TryAndFindSomeFriends').'</a></div>';
		$friend_html.= '</div>'; // close div friend-header
	}
	$friend_html.= '</div>';
	echo $friend_html;
	echo '</div>';
	echo '</div>';
}

echo '</div>'; // close div tag .social-box-main1

if ($show_full_profile) {

	// MY GROUPS
    $results = GroupPortalManager::get_groups_by_user($my_user_id, 0);
	$grid_my_groups = array();
	$max_numbers_of_group = 4;
	if (is_array($results) && count($results) > 0) {
		$i = 1;
		foreach ($results as $result) {
			if ($i > $max_numbers_of_group) break;
			$id = $result['id'];
			$url_open  = '<a href="groups.php?id='.$id.'">';
			$url_close = '</a>';
			$icon = '';
			$name = api_strtoupper(cut($result['name'],20,true));
			if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
				$icon = Display::return_icon('admin_star.png', get_lang('Admin'), array('style'=>'vertical-align:middle;width:16px;height:16px;'));
			} elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
				$icon = Display::return_icon('moderator_star.png', get_lang('Moderator'), array('style'=>'vertical-align:middle;width:16px;height:16px;'));
			}
			$count_users_group = count(GroupPortalManager::get_all_users_by_group($id));
			if ($count_users_group == 1 ) {
				$count_users_group = $count_users_group.' '.get_lang('Member');
			} else {
				$count_users_group = $count_users_group.' '.get_lang('Members');
			}
			$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
			$item_name = '<div class="box_shared_profile_group_title">'.$url_open.'<span class="social-groups-text1">'.api_strtoupper($name).'</span>'. $icon.$url_close.'</div>';

			if ($result['description'] != '') {
				$item_description = '<div class="box_shared_profile_group_description"><span class="social-groups-text2">'.get_lang('DescriptionGroup').'</span><p class="social-groups-text4">'.cut($result['description'],100,true).'</p></div>';
			} else {
				$item_description = '<div class="box_shared_profile_group_description"><span class="social-groups-text2"></span><p class="social-groups-text4"></p></div>';
			}

			$result['picture_uri'] = '<div class="box_shared_profile_group_image"><img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" /></div>';
			$item_actions = '';
			if (api_get_user_id() == $user_id) {
				$item_actions = '<div class="box_shared_profile_group_actions"><a href="groups.php?id='.$id.'">'.get_lang('SeeMore').$url_close.'</div>';
			}
			$grid_my_groups[]= array($item_name,$url_open.$result['picture_uri'].$url_close, $item_description.$item_actions);
			$i++;
		}
	}

    if (count($grid_my_groups) > 0) {
    	echo '<div class="social-box-main1">';
			echo '<div class="social-box-container2 quiz_content_actions">';
				echo '<div class="social-box-content2">';
					echo '<div><h3>'.get_lang('MyGroups').'</h3></div>';
					$count_groups = 0;
					if (count($results) == 1 ) {
						$count_groups = count($results).' '.get_lang('Group');
					} else {
						$count_groups = count($results).' '.get_lang('Groups');
					}
					echo '<div>'.$count_groups.'</div>';

					if ($i > $max_numbers_of_group) {
						if (api_get_user_id() == $user_id) {
							echo '<div class="box_shared_profile_group_actions"><a href="groups.php?view=mygroups">'.get_lang('SeeAllMyGroups').'</a></div>';
						} else {
							echo '<div class="box_shared_profile_group_actions"><a href="'.api_get_path(WEB_CODE_PATH).'social/profile_friends_and_groups.inc.php?view=mygroups&height=390&width=610&&user_id='.$user_id.'" class="thickbox" title="'.get_lang('SeeAll').'" >'.get_lang('SeeAllMyGroups').'</a></div>';
						}
					}

	    			Display::display_sortable_grid('shared_profile_mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}

    if (count($grid_my_groups) > 0) {
    	echo '<div class="social-box-main1">';
			echo '<div class="social-box-container2 quiz_content_actions">';
				echo '<div class="social-box-content2">';

                if ($show_full_profile && $user_id == intval(api_get_user_id())) {
                    $personal_course_list = UserManager::get_personal_session_course_list($user_id);
                    $course_list_code = array();
                    $i=1;
                    if (is_array($personal_course_list)) {
                        foreach ($personal_course_list as $my_course) {
                            if ($i<=10) {
                                //$list[] = SocialManager::get_logged_user_course_html($my_course,$i);
                                $course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db']);
                            } else {
                                break;
                            }
                            $i++;
                        }
                        //to avoid repeted courses
                        $course_list_code = array_unique_dimensional($course_list_code);
                    }

                    //-----Announcements
                    $my_announcement_by_user_id= intval($user_id);
                    $announcements = array();
                    foreach ($course_list_code as $course) {
                        $content = AnnouncementManager::get_all_annoucement_by_user_course($course['dbName'],$my_announcement_by_user_id);
                        $course_info=api_get_course_info($course['code']);
                        if (!empty($content)) {
                            $announcements[] = '<a href="'.api_get_path(WEB_CODE_PATH).'announcements/announcements.php?cidReq='.$course['code'].'"'.Display::return_icon('announcement.png',get_lang('Announcements'),array('hspace'=>'6')).'<span class="social-menu-text4">'.$course_info['name'].' ('.$content['count'].')</span></a>';
                        }
                    }
                    if (!empty($announcements)) {
                        echo '<div align="left" class="social-menu-title" ><h3>'.get_lang('ToolAnnouncement').'</h3></div>';
                        echo '<div>';
                            foreach ($announcements as $announcement) {
                                echo '<div align="left">'.$announcement.'</div>';
                            }
                        echo '</div>';
                    }
                }
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}

	// COURSES LIST
	if ( is_array($list) ) {
		echo '<div class="social-box-main1">';
			echo '<div class="social-box-container2 quiz_content_actions">';
				echo '<div class="social-box-content2">';
					echo '<div><h3>'.api_ucfirst(get_lang('MyCourses')).'</h3></div>';
					echo '<div class="social-content-training">';
					//Courses whithout sessions
					$old_user_category = 0;
					$i=1;
					foreach($list as $key=>$value) {
						if ( empty($value[2]) ) { //if out of any session
							echo $value[1];
							echo '<div id="social_content'.$i.'" style="background : #EFEFEF; padding:0px; ">';
							echo '</div>';
							$i++;
						}
					}
					$listActives = $listInactives = $listCourses = array();
					foreach ( $list as $key=>$value ) {
						if ( $value['active'] ) { //if the session is still active (as told by get_logged_user_course_html())
							$listActives[] = $value;
						} elseif ( !empty($value[2]) ) { //if there is a session but it is not active
							$listInactives[] = $value;
						}
					}
					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
	// user feeds
	$user_feeds = SocialManager::get_user_feeds($user_id);
	if (!empty($user_feeds )) {
		echo '<div class="social-box-main1">';
			echo '<div class="social-box-container2 quiz_content_actions">';
				echo '<div class="social-box-content2">';
					echo '<div><h3>'.get_lang('RSSFeeds').'</h3></div>';
	    			echo '<div class="social-content-training">'.$user_feeds.'</div>';
	    			//echo '<div class="clear"></div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}

	//--Productions
	$production_list =  UserManager::build_production_list($user_id);

	// Images uploaded by course
	$file_list = '';
	if (is_array($course_list_code) && count($course_list_code)>0) {
		foreach ($course_list_code as $course) {
			$file_list.= UserManager::get_user_upload_files_by_course($user_id,$course['code'],$resourcetype='images');
		}
	}

	$count_pending_invitations = 0;
	if (!isset($_GET['u']) || (isset($_GET['u']) && $_GET['u']==api_get_user_id())) {
		$pending_invitations = SocialManager::get_list_invitation_of_friends_by_user_id(api_get_user_id());
		$list_get_path_web	 = SocialManager::get_list_web_path_user_invitation_by_user_id(api_get_user_id());
		$count_pending_invitations = count($pending_invitations);
	}

	echo '<div class="social-box-main1">';

	if (!empty($production_list) || !empty($file_list) || $count_pending_invitations > 0) {
		echo '<div class="quiz_content_actions">';
		//Pending invitations
		if (!isset($_GET['u']) || (isset($_GET['u']) && $_GET['u']==api_get_user_id())) {
			if ($count_pending_invitations > 0) {
				echo '<div class="social-box-content1">';
				echo '<div><h3>'.get_lang('PendingInvitations').'</h3></div>';
				for ($i=0;$i<$count_pending_invitations;$i++) {
					$user_invitation_id = $pending_invitations[$i]['user_sender_id'];
					echo '<div id="dpending_'.$user_invitation_id.'" class="friend_invitations">';
					echo '<div style="float:left;width:60px;" >';
					echo '<img style="margin-bottom:5px;" src="'.$list_get_path_web[$i]['dir'].'/'.$list_get_path_web[$i]['file'].'" width="60px">';
					echo '</div>';

					echo '<div style="padding-left:70px;">';
					$user_info = api_get_user_info($user_invitation_id);
					echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_invitation_id.'">'.api_get_person_name($user_info['firstname'], $user_info['lastname']).'</a>';
					echo '<br />';
					echo ' '.(substr($pending_invitations[$i]['content'],0,50));
					echo '<br />';
					echo '<a id="btn_accepted_'.$user_invitation_id.'" onclick="register_friend(this)" href="javascript:void(0)">'.get_lang('SocialAddToFriends').'</a>';
					echo '<div id="id_response">&nbsp;</div>';
					echo '</div>';
					echo '</div>';
					echo '<div class="clear"></div>';
				}
				echo '</div>';
			}
		}

		echo '<div class="social-box-content1">';
		//--Productions
		$production_list =  UserManager::build_production_list($user_id);
		if (!empty($production_list )) {
			echo '<div><h3>'.get_lang('MyProductions').'</h3></div>';
			echo '<div class="rounded1">';
			echo $production_list;
			echo '</div>';
		}
		// Images uploaded by course

		if (!empty($file_list)) {
			echo '<div><h3>'.get_lang('ImagesUploaded').'</h3></div>';
			echo '<div class="social-content-information">';
			echo $file_list;
			echo '</div>';
		}
		echo '</div>'; // close div tag .social-box-content1
		echo '</div>';	// close div tag .social-box-left
	}

	if (!empty($user_info['competences']) || !empty($user_info['diplomas']) || !empty($user_info['openarea']) || !empty($user_info['teach']) ) {
		echo '<div class="social-box-container2 quiz_content_actions" style="margin-top:10px;">';
		echo '<div class="social-box-content1" style="width:95%">';
		echo '<div><h3>'.get_lang('MoreInformation').'</h3></div>';
		echo '<div class="social-content-competences">';
		$cut_size = 220;
		if (!empty($user_info['competences'])) {
			echo '<br />';
			echo '<div class="social-background-content" style="width:100%;">';
			echo '<div class="social-actions-message"><strong>'.get_lang('MyCompetences').'</strong></div>';
			echo '<div class="social-profile-extended">'.$user_info['competences'].'</div>';
			echo '</div>';
			echo '<br />';
		}
		if (!empty($user_info['diplomas'])) {
			echo '<div class="social-background-content" style="width:100%;" >';
			echo '<div class="social-actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div>';
			echo '<div class="social-profile-extended">'.$user_info['diplomas'].'</div>';
			echo '</div>';
			echo '<br />';
		}
		if (!empty($user_info['openarea'])) {
			echo '<div class="social-background-content" style="width:100%;" >';
			echo '<div class="social-actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div>';
			echo '<div class="social-profile-extended">'.$user_info['openarea'].'</div>';
			echo '</div>';
			echo '<br />';
		}
		if (!empty($user_info['teach'])) {
			echo '<div class="social-background-content" style="width:100%;" >';
			echo '<div class="social-actions-message"><strong>'.get_lang('MyTeach').'</strong></div>';
			echo '<div class="social-profile-extended">'.$user_info['teach'].'</div>';
			echo '</div>';
			echo '<br />';
		}
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
	echo '</div>'; // close div tag .social-box-main
}
echo '</div>'; // close div tag .socialContentRight
echo '<form id="id_reload" name="id_reload" action="profile.php">&nbsp;</form>';
echo '</div>';

// End content
echo '</div>';

// Actions
echo '<div class="actions">';
echo '</div>';

Display :: display_footer();