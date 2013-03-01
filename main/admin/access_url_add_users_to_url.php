<?php
/* For licensing terms, see /dokeos_license.txt */

/**
*	This script allows platform admins to add users to urls.
*	It displays a list of users and a list of courses;
*	you can select multiple users and courses and then click on
*	@package dokeos.admin
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
api_protect_admin_script();
if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');

/*
-----------------------------------------------------------
	Global constants and variables
-----------------------------------------------------------
*/

$form_sent = 0;
$first_letter_user = '';
$first_letter_course = '';
$courses = array ();
$url_list = array();
$users = array();

$tbl_access_url_rel_user = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
$tbl_access_url = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
$tbl_user 		= Database :: get_main_table(TABLE_MAIN_USER);

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
$tool_name = get_lang('AddUsersToURL');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs'));


/*
==============================================================================
		MAIN CODE
==============================================================================
*/

Display :: display_header($tool_name);

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit_users_to_url.php">'.Display::return_icon('edit_32.png',get_lang('EditUsersToURL'),'').get_lang('EditUsersToURL').'</a>';
echo '</div>';
echo '<div id="content">';
api_display_tool_title($tool_name);



if ($_POST['form_sent']) {
	$form_sent = $_POST['form_sent'];
	$users = is_array($_POST['user_list']) ? $_POST['user_list'] : array() ;
	$url_list = is_array($_POST['url_list']) ? $_POST['url_list'] : array() ;
	$first_letter_user = $_POST['first_letter_user'];

	foreach($users as $key => $value) {
		$users[$key] = intval($value);
	}

	if ($form_sent == 1)
	{
		if ( count($users) == 0 || count($url_list) == 0) {
			Display :: display_error_message(get_lang('AtLeastOneUserAndOneURL'));
			//header('Location: access_urls.php?action=show_message&message='.get_lang('AtLeastOneUserAndOneURL'));
		} else {
			UrlManager::add_users_to_urls($users,$url_list);
			Display :: display_confirmation_message(get_lang('UsersBelongURL'));
			//header('Location: access_urls.php?action=show_message&message='.get_lang('UsersBelongURL'));
		}
	}
}



/*
-----------------------------------------------------------
	Display GUI
-----------------------------------------------------------
*/


if(empty($first_letter_user)) {
	$sql = "SELECT count(*) as nb_users FROM $tbl_user";
	$result = Database::query($sql, __FILE__, __LINE__);
	$num_row = Database::fetch_array($result);
	if($num_row['nb_users']>1000) {
		//if there are too much users to gracefully handle with the HTML select list,
	    // assign a default filter on users names
		$first_letter_user = 'A';
	}
	unset($result);
}
$first_letter_user = Database::escape_string($first_letter_user);

$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
$sql = "SELECT user_id,lastname,firstname,username FROM $tbl_user
	    WHERE ".$target_name." LIKE '".$first_letter_user."%' OR ".$target_name." LIKE '".api_strtolower($first_letter_user)."%'
		ORDER BY ". (count($users) > 0 ? "(user_id IN(".implode(',', $users).")) DESC," : "")." ".$target_name;
$result = Database::query($sql, __FILE__, __LINE__);
$db_users = Database::store_result($result);
unset($result);

$sql = "SELECT id, url FROM $tbl_access_url  WHERE active=1 ORDER BY url";
$result = Database::query($sql, __FILE__, __LINE__);
$db_urls = Database::store_result($result);
unset($result);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
 <input type="hidden" name="form_sent" value="1"/>
  <table border="0" cellpadding="5" cellspacing="0" width="100%">
   <tr>
    <td width="40%" align="center">
     <b><?php echo get_lang('UserList'); ?></b>
     <br/><br/>
     <?php echo get_lang('FirstLetterUser'); ?> :
     <select name="first_letter_user" onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();">
      <option value="">--</option>
      <?php
        echo Display :: get_alphabet_options($first_letter_user);
      ?>
     </select>
    </td>
        <td width="20%">&nbsp;</td>
    <td width="40%" align="center">
     <b><?php echo get_lang('URLList'); ?> :</b>
    </td>
   </tr>
   <tr>
    <td width="40%" align="center">
     <select name="user_list[]" multiple="multiple" size="20" style="width:250px;">
		<?php
		foreach ($db_users as $user) {
		?>
			  <option value="<?php echo $user['user_id']; ?>" <?php if(in_array($user['user_id'],$users)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')'; ?></option>
		<?php
		}
		?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <input type="submit" value="<?php echo get_lang('AddToThatURL'); ?> &gt;&gt;"/>
   </td>
   <td width="40%" align="center">
    <select name="url_list[]" multiple="multiple" size="20" style="width:230px;">
		<?php
		foreach ($db_urls as $url_obj) {
			?>
			<option value="<?php echo $url_obj['id']; ?>" <?php if(in_array($url_obj['id'],$url_list)) echo 'selected="selected"'; ?>><?php echo $url_obj['url']; ?></option>
			<?php
		}
		?>
    </select>
   </td>
  </tr>
 </table>
</form>
</div>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>
