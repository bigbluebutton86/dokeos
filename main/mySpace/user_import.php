<?php // $Id: user_import.php 14792 2008-04-08 20:57:53Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL
	Copyright (c) 2008 Julio Montoya Armas <gugli100@gmail.com>

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/

/**
==============================================================================
*   This tool allows platform admins to add users by uploading a CSV or XML file
*   This code is inherited from admin/user_import.php
*   Created on 26 julio 2008  by Julio Montoya gugli100@gmail.com
==============================================================================
*/

/**
 * Checks if a username exist in the DB otherwise it create a "double"
 * i.e. if we look into for jmontoya but the user's name already exist we create the user jmontoya2
 * the return array will be array(username=>'jmontoya', sufix='2')
 * @param string firstname
 * @param string lastname
 * @param string username
 * @return array with the username, the sufix
 * @author Julio Montoya Armas
 */
function make_username($firstname, $lastname, $username, $language = null, $encoding = null) {
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	// if username exist
	if (!UserManager::is_username_available($username) || empty($username)) {
		$i = 0;
		while (1) {
			if ($i == 0) {
				$sufix = '';
			} else {
				$sufix = $i;
			}
			$desired_username = UserManager::create_username($firstname, $lastname, $language, $encoding);
			if (UserManager::is_username_available($desired_username.$sufix)) {
				break;
			} else {
				$i++;
			}
		}
		$username_array = array('username' => $desired_username , 'sufix' => $sufix);
		return $username_array;
	} else {
		$username_array = array('username' => $username, 'sufix' => '');
		return $username_array;
	}
}

/**
 * Checks if there are repeted users in a given array
 * @param  array $usernames list of the usernames in the uploaded file
 * @param  array $user_array['username'] and $user_array['sufix'] where sufix is the number part in a login i.e -> jmontoya2
 * @return array with the $usernames array and the $user_array array
 * @author Julio Montoya Armas
 */
function check_user_in_array($usernames, $user_array) {
	$user_list = array_keys($usernames);
	$username = $user_array['username'].$user_array['sufix'];

	if (in_array($username, $user_list)) {
		$user_array['sufix'] += $usernames[$username];
		$usernames[$username]++;
	} else {
		$usernames[$username] = 1;
	}
	$result_array = array($usernames, $user_array);
	return $result_array;
}

/**
 * Checks whether a username has been already subscribed in a session.
 * @param string a given username
 * @param array  the array with the course list codes
 * @param the session id
 * @return 0 if the user is not subscribed  otherwise it returns the user_id of the given username
 * @author Julio Montoya Armas
 */
function user_available_in_session($username, $course_list, $id_session) {
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$id_session = intval($id_session);
	$username = Database::escape_string($username);
	foreach($course_list as $enreg_course) {
		$sql_select = "	SELECT u.user_id FROM $tbl_session_rel_course_rel_user rel INNER JOIN $table_user u
					   	ON (rel.id_user=u.user_id)
						WHERE rel.id_session='$id_session' AND u.status='5' AND u.username ='$username' AND rel.course_code='$enreg_course'";
		$rs = Database::query($sql_select, __FILE__, __LINE__);
		if (Database::num_rows($rs) > 0) {
			return Database::result($rs, 0, 0);
		} else {
			return 0;
		}
	}
}

/**
This function checks whether some users in the uploaded file repeated and creates unique usernames if necesary.
A case: Within the file there is an user repeted twice (Julio Montoya / Julio Montoya) and the username fields are empty.
Then, this function would create unique usernames based on the first and the last name. Two users wiould be created - jmontoya and jmontoya2.
Of course, if in the database there is a user with the name jmontoya, the newly created two users registered would be jmontoya2 and jmontoya3.
@param $users list of users
@author Julio Montoya Armas
*/
function check_all_usernames($users, $course_list, $id_session) {
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$usernames = array();
	$new_users = array();
	foreach ($users as $index => $user) {
		$desired_username = array();
		if (empty($user['UserName'])) {
			$desired_username = make_username($user['FirstName'], $user['LastName'], '');
			$pre_username = $desired_username['username'].$desired_username['sufix'];
			$user['UserName'] = $pre_username;
			$user['create'] = '1';
		} else {
			if (UserManager::is_username_available($user['UserName'])) {
				$desired_username = make_username($user['FirstName'], $user['LastName'], $user['UserName']);
				$user['UserName'] = $desired_username['username'].$desired_username['sufix'];
				$user['create'] = '1';
			} else {
				$is_session_avail = user_available_in_session($user['UserName'], $course_list, $id_session);
				if ($is_session_avail == 0) {
					$user_name = $user['UserName'];
					$sql_select = "SELECT user_id FROM $table_user WHERE username ='$user_name' ";
					$rs = Database::query($sql_select, __FILE__, __LINE__);
					$user['create'] = Database::result($rs, 0, 0); // This should be the ID because the user exists.
				} else {
					$user['create'] = $is_session_avail;
				}
			}
		}
		// Usernames is the current list of users in the file.
		$result_array = check_user_in_array($usernames, $desired_username);
		$usernames = $result_array[0];
		$desired_username = $result_array[1];
		$user['UserName'] = $desired_username['username'].$desired_username['sufix'];
		$new_users[] = $user;
	}
	return $new_users;
}

/**
 * This functions checks whether there are users that are already registered in the DB by different creator than the current coach.
 * @param string a given username
 * @param array  the array with the course list codes
 * @param the session id
 * @author Julio Montoya Armas
 */
function get_user_creator($users, $course_list, $id_session) {
	$errors = array();
	foreach ($users as $index => $user) {
		// database table definition
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$username = Database::escape_string($user['UserName']);
		//echo "<br>";
		$sql = "SELECT creator_id FROM $table_user WHERE username='$username' ";

		$rs = Database::query($sql, __FILE__, __LINE__);
		$creator_id = Database::result($rs, 0, 0);
		// check if we are the creators or not
		if ($creator_id != '') {
			if ($creator_id != api_get_user_id()) {
				$user['error'] = get_lang('UserAlreadyRegisteredByOtherCreator');
				$errors[] = $user;
			}
		}
	}
	return $errors;
}

/**
 * Validates imported data.
 * @param list of users
 */
function validate_data($users, $id_session = null) {
	$errors = array();
	$usernames = array();
	$new_users = array();
	foreach ($users as $index => $user) {
		// 1. Check whether mandatory fields are set.
		$mandatory_fields = array('LastName', 'FirstName');
		if (api_get_setting('registration', 'email') == 'true') {
			$mandatory_fields[] = 'Email';
		}

		foreach ($mandatory_fields as $key => $field) {
			if (!isset ($user[$field]) || strlen($user[$field]) == 0) {
				$user['error'] = get_lang($field.'Mandatory');
				$errors[] = $user;
			}
		}
		// 2. Check whether the username is too long.
		if (UserManager::is_username_too_long($user['UserName'])) {
			$user['error'] = get_lang('UserNameTooLong');
			$errors[] = $user;
		}

		$user['UserName'] = trim($user['UserName']);

		if (empty($user['UserName'])) {
			 $user['UserName'] = UserManager::create_username($user['FirstName'], $user['LastName']);
		}
		$new_users[] = $user;
	}
	$results = array('errors' => $errors, 'users' => $new_users);
	return $results;
}

/**
 * Adds missing user-information (which isn't required, like password, etc).
 */
function complete_missing_data($user) {
	// 1. Generate a password if it is necessary.
	if (!isset ($user['Password']) || strlen($user['Password']) == 0) {
		$user['Password'] = api_generate_password();
	}
	return $user;
}

/**
 * Saves imported data.
 */
function save_data($users, $course_list, $id_session) {
	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);

	$id_session = intval($id_session);
	$sendMail = $_POST['sendMail'] ? 1 : 0;

	// Adding users to the platform.
	$new_users = array();
	foreach ($users as $index => $user) {
		$user = complete_missing_data($user);
		// coach only will registered users
		$default_status = '5';
		if ($user['create'] == '1') {
			$user['id'] = UserManager :: create_user($user['FirstName'], $user['LastName'], $default_status, $user['Email'], $user['UserName'], $user['Password'], $user['OfficialCode'], api_get_setting('PlatformLanguage'), $user['PhoneNumber'], '');
			$user['added_at_platform'] = 1;
		} else {
			$user['id'] = $user['create'];
			$user['added_at_platform'] = 0;
		}
		$new_users[] = $user;
	}
	// Update user list.
	$users = $new_users;

	// Inserting users.
	$super_list = array();
	foreach ($course_list as $enreg_course) {
		$nbr_users = 0;
		$new_users = array();
		$enreg_course = Database::escape_string($enreg_course);
		foreach ($users as $index => $user) {
			$userid = intval($user['id']);
			$sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$userid')";
			$course_session = array('course' => $enreg_course, 'added' => 1);
			//$user['added_at_session'] = $course_session;
			Database::query($sql, __FILE__, __LINE__);
			if (Database::affected_rows()) {
				$nbr_users++;
			}
			$new_users[] = $user;
		}
		$super_list[] = $new_users;

		//update the nbr_users field
		$sql_select = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
		$rs = Database::query($sql_select, __FILE__, __LINE__);
		list($nbr_users) = Database::fetch_array($rs);
		$sql_update = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
		Database::query($sql_update , __FILE__, __LINE__);

		$sql_update = "UPDATE $tbl_session SET nbr_users= '$nbr_users' WHERE id='$id_session'";
		Database::query($sql_update, __FILE__, __LINE__);
	}
	// We don't delete the users (thoughts while dreaming)
	//$sql_delete = "DELETE FROM $tbl_session_rel_user WHERE id_session = '$id_session'";
	//Database::query($sql_delete,__FILE__, __LINE__);

	$new_users = array();
	foreach ($users as $index => $user) {
		$userid = $user['id'];
		$sql_insert = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$userid')";
		Database::query($sql_insert, __FILE__, __LINE__);
		$user['added_at_session'] = 1;
		$new_users[] = $user;
	}

	$users = $new_users;
	$registered_users = get_lang('FileImported').'<br /> Import file results : <br />';
	// Sending emails.
	$addedto = '';
	if ($sendMail) {
		$i = 0;
		foreach ($users as $index => $user) {
			$emailto = api_get_person_name($user['FirstName'], $user['LastName'], null, PERSON_NAME_EMAIL_ADDRESS).' <'.$user['Email'].'>';
			$emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
			$emailbody = get_lang('Dear').' '.api_get_person_name($user['FirstName'], $user['LastName']).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName')." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : $user[UserName]\n".get_lang('Pass')." : $user[Password]\n\n".get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('Is')." : ".api_get_path('WEB_PATH')." \n\n".get_lang('Problem')."\n\n".get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Manager')." ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email')." : ".api_get_setting('emailAdministrator')."";
			$emailheaders = 'From: '.api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS).' <'.api_get_setting('emailAdministrator').">\n";
			$emailheaders .= 'Reply-To: '.api_get_setting('emailAdministrator');
			@api_send_mail($emailto, $emailsubject, $emailbody, $emailheaders);

			if (($user['added_at_platform'] == 1  && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
				if ($user['added_at_platform'] == 1) {
					$addedto = get_lang('UserCreatedPlatform');
				} else  {
					$addedto = '          ';
				}

				if ($user['added_at_session'] == 1) {
					$addedto .= get_lang('UserInSession');
				}
				$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
			} else {
				$addedto = get_lang('UserNotAdded');
				$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
			}
		}
	} else {
		$i = 0;
		foreach ($users as $index => $user) {
			if (($user['added_at_platform'] == 1 && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
				if ($user['added_at_platform'] == 1) {
					$addedto = get_lang('UserCreatedPlatform');
				} else {
					$addedto = '          ';
				}

				if ($user['added_at_session'] == 1) {
					$addedto .= ' '.get_lang('UserInSession');
				}

				$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
			} else {
				$addedto = get_lang('UserNotAdded');
				$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
			}
		}
	}

	header('Location: course.php?id_session='.$id_session.'&action=show_message&message='.urlencode($registered_users));
	exit ();

	//header('Location: resume_session.php?id_session='.$id_session);
}

/**
 * Reads CSV-file.
 * @param string $file Path to the CSV-file
 * @return array All userinformation read from the file
 */
function parse_csv_data($file) {
	$users = Import :: csv_to_array($file);
	foreach ($users as $index => $user) {
		if (isset ($user['Courses'])) {
			$user['Courses'] = explode('|', trim($user['Courses']));
		}
		$users[$index] = $user;
	}
	return $users;
}

/**
 * XML-parser: the handler at the beginning of element.
 */
function element_start($parser, $data) {
	$data = api_utf8_decode($data);
	global $user;
	global $current_tag;
	switch ($data) {
		case 'Contact' :
			$user = array ();
			break;
		default :
			$current_tag = $data;
	}
}

/**
 * XML-parser: the handler at the end of element.
 */
function element_end($parser, $data) {
	$data = api_utf8_decode($data);
	global $user;
	global $users;
	global $current_value;
	global $purification_option_for_usernames;
	$user[$data] = $current_value;
	switch ($data) {
		case 'Contact' :
			$user['UserName'] = UserManager::purify_username($user['UserName'], $purification_option_for_usernames);
			$users[] = $user;
			break;
		default :
			$user[$data] = $current_value;
			break;
	}
}

/**
 * XML-parser: the handler for character data.
 */
function character_data($parser, $data) {
	$data = trim(api_utf8_decode($data));
	global $current_value;
	$current_value = $data;
}

/**
 * Reads XML-file.
 * @param string $file Path to the XML-file
 * @return array All userinformation read from the file
 */
function parse_xml_data($file) {
	global $current_tag;
	global $current_value;
	global $user;
	global $users;
	$users = array ();
	$parser = xml_parser_create('UTF-8');
	xml_set_element_handler($parser, 'element_start', 'element_end');
	xml_set_character_data_handler($parser, "character_data");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
	xml_parse($parser, api_utf8_encode_xml(file_get_contents($file)));
	xml_parser_free($parser);
	return $users;
}


/*
==============================================================================
		Main script
==============================================================================
*/

$language_file = array ('admin', 'registration', 'index', 'trad4all', 'tracking');

$cidReset = true;
require '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN; // TODO: Platform admin section?

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

$tool_name = get_lang('ImportUserListXMLCSV');
api_block_anonymous_users();

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('MySpace'));
$id_session = '';
if (isset($_GET['id_session']) && $_GET['id_session'] != '') {
 	$id_session = intval($_GET['id_session']);
	$interbreadcrumb[] = array ('url' => 'session.php', 'name' => get_lang('Sessions'));
	$interbreadcrumb[] = array ('url' => 'course.php?id_session='.$id_session.'', 'name' => get_lang('Course'));
}

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;

/*
// Checking whether the current coach is the admin coach.
if (!api_is_coach()) {
	api_not_allowed(true);
}
*/

// Checking whether the current coach is the admin coach.
if (api_get_setting('add_users_by_coach') == 'true') {
	if (!api_is_platform_admin()) {
		if (isset($_REQUEST['id_session'])) {
			$id_session = intval($_REQUEST['id_session']);
			$sql = 'SELECT id_coach FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_session;
			$rs = Database::query($sql, __FILE__, __LINE__);
			if (Database::result($rs, 0, 0) != $_user['user_id']) {
				api_not_allowed(true);
			}
		} else {
			api_not_allowed(true);
		}
	}
} else {
	api_not_allowed(true);
}

set_time_limit(0);

if ($_POST['formSent'] && $_FILES['import_file']['size'] !== 0) {
	$file_type = $_POST['file_type'];
	$id_session = intval($_POST['id_session']);
	if ($file_type == 'csv') {
		$users = parse_csv_data($_FILES['import_file']['tmp_name']);
	} else {
		$users = parse_xml_data($_FILES['import_file']['tmp_name']);
	}
	if (count($users) > 0) {
		$results = validate_data($users);
		$errors = $results['errors'];
		$users = $results['users'];

		if (count($errors) == 0) {
			if (!empty($id_session)) {
				$tbl_session_rel_course	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
				// Selecting all the courses from the session id requested.
				$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
				$result = Database::query($sql, __FILE__, __LINE__);
				$course_list = array();
				while ($row = Database::fetch_array($result)) {
					$course_list[] = $row['course_code'];
				}
				$errors = get_user_creator($users, $course_list, $id_session);
				$users = check_all_usernames($users, $course_list, $id_session);
				if (count($errors) == 0) {
					save_data($users, $course_list, $id_session);
				}
			} else {
				header('Location: course.php?id_session='.$id_session.'&action=error_message&message='.urlencode(get_lang('NoSessionId')));
			}
		}
	} else {
		header('Location: course.php?id_session='.$id_session.'&action=error_message&message='.urlencode(get_lang('NoUsersRead')));
	}
}

Display :: display_header($tool_name);

if ($_FILES['import_file']['size'] == 0 && $_POST) {
	Display::display_error_message(get_lang('ThisFieldIsRequired'));
}

if (count($errors) != 0) {
	$error_message = '<ul>';
	foreach ($errors as $index => $error_user) {
		$error_message .= '<li><strong>'.$error_user['error'].'</strong>: ';
		$error_message .= api_get_person_name($error_user['FirstName'], $error_user['LastName']);
		$error_message .= '</li>';
	}
	$error_message .= '</ul>';
	Display :: display_error_message($error_message, false);
}

$form = new FormValidator('user_import');
$form->addElement('hidden', 'formSent');
$form->addElement('hidden', 'id_session',$id_session);
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$form->addRule('import_file', get_lang('ThisFieldIsRequired'), 'required');
$allowed_file_types = array ('xml', 'csv');
$form->addRule('import_file', get_lang('InvalidExtension').' ('.implode(',', $allowed_file_types).')', 'filetype', $allowed_file_types);
$form->addElement('radio', 'file_type', get_lang('FileType'), 'XML (<a href="exemple.xml" target="_blank">'.get_lang('ExampleXMLFile').'</a>)', 'xml');
$form->addElement('radio', 'file_type', null, 'CSV (<a href="exemple.csv" target="_blank">'.get_lang('ExampleCSVFile').'</a>)', 'csv');
$form->addElement('radio', 'sendMail', get_lang('SendMailToUsers'), get_lang('Yes'), 1);
$form->addElement('radio', 'sendMail', null, get_lang('No'), 0);
$form->addElement('submit', 'submit', get_lang('Ok'));
$defaults['formSent'] = 1;
$defaults['sendMail'] = 0;
$defaults['file_type'] = 'xml';
$form->setDefaults($defaults);
$form->display();
/*
<?php echo implode('/',$defined_auth_sources); ?>
&lt;AuthSource&gt;<?php echo implode('/',$defined_auth_sources); ?>&lt;/AuthSource&gt;
*/

?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<b>LastName</b>;<b>FirstName</b>;<b>Email</b>;UserName;Password;OfficialCode;PhoneNumber;
<b>Montoya</b>;<b>Julio</b>;<b>info@localhost</b>;jmontoya;123456789;code1;3141516
<b>Doewing</b>;<b>Johny</b>;<b>info@localhost</b>;jdoewing;123456789;code2;3141516
</pre>
</blockquote>

<p><?php echo get_lang('XMLMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>
<blockquote>
<pre>
&lt;?xml version=&quot;1.0&quot; encoding=&quot;<?php echo api_refine_encoding_id(api_get_system_encoding()); ?>&quot;?&gt;
&lt;Contacts&gt;
    &lt;Contact&gt;
        <b>&lt;LastName&gt;Montoya&lt;/LastName&gt;</b>
        <b>&lt;FirstName&gt;Julio&lt;/FirstName&gt;</b>
        <b>&lt;Email&gt;info@localhost&lt;/Email&gt;</b>
        &lt;UserName&gt;jmontoya&lt;/UserName&gt;
        &lt;Password&gt;123456&lt;/Password&gt;
        &lt;OfficialCode&gt;code1&lt;/OfficialCode&gt;
        &lt;PhoneNumber&gt;3141516&lt;/PhoneNumber&gt;
    &lt;/Contact&gt;
&lt;/Contacts&gt;
</pre>
</blockquote>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();