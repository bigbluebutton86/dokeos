<?php
/* For licensing terms, see /dokeos_license.txt */
/**
 * Responses to AJAX calls 
 */
$language_file = array('admin', 'registration');
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
$action = $_GET['a'];

switch ($action) {	
	case 'search_tags':
		if (api_is_anonymous()) {
			echo '';	
		} else {		
			$field_id = intval($_GET['field_id']);
			$tag = $_GET['tag'];
			echo UserManager::get_tags($tag, $field_id,'json','10');
		}
	break;
	case 'generate_api_key':
		if (api_is_anonymous()) {
			echo '';
		} else {		
			$array_list_key = array();
			$user_id = api_get_user_id();
			$api_service = 'dokeos';
			$num = UserManager::update_api_key($user_id, $api_service);
			$array_list_key = UserManager::get_api_keys($user_id, $api_service);
			?>			
			<div class="row">
				<div class="label"><?php echo get_lang('MyApiKey'); ?></div>
				<div class="formw">
				<input type="text" name="api_key_generate" id="id_api_key_generate" size="40" value="<?php echo $array_list_key[$num]; ?>"/>
				</div>
			</div>
			<?php			
		}
	break;
	case 'active_user':
		if (api_is_platform_admin()) {			
			$user_id = intval($_GET['user_id']);
			$status = intval($_GET['status']);
			if (!empty($user_id)) {
				$user_table = Database :: get_main_table(TABLE_MAIN_USER);
				$sql="UPDATE $user_table SET active='".$status."' WHERE user_id='".Database::escape_string($user_id)."'";
				$result = Database::query($sql);
				//Send and email if account is active
				if ($status == 1) {
					$user_info = api_get_user_info($user_id);					
					$recipient_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
					$emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
					$email_admin = api_get_setting('emailAdministrator');
					$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
					$emailbody=get_lang('Dear')." ".stripslashes($recipient_name).",\n\n";					
					
					$emailbody.=sprintf(get_lang('YourAccountOnXHasJustBeenApprovedByOneOfOurAdministrators'), api_get_setting('siteName'))."\n";
					$emailbody.=sprintf(get_lang('YouCanNowLoginAtXUsingTheLoginAndThePasswordYouHaveProvided'), api_get_path(WEB_PATH)).",\n\n";
					$emailbody.=get_lang('HaveFun')."\n\n";
					//$emailbody.=get_lang('Problem'). "\n\n". get_lang('Formula');
					$emailbody.=api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
					$result = api_mail($recipient_name, $user_info['mail'], $emailsubject, $emailbody, $sender_name, $email_admin);					
				}
				
			}						
		} else {
			echo '';
		}
	break;
	
		
	default:
		echo '';
}
exit;
?>