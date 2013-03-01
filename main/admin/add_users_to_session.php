<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

// name of the language file that needs to be included
$language_file=array('admin','registration');

// resetting the course id
$cidReset=true;

// including some necessary dokeos files
require('../inc/global.inc.php');

require_once ('../inc/lib/xajax/xajax.inc.php');
$xajax = new xajax();
//$xajax->debugOn();
$xajax -> registerFunction ('search_users');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'session_list.php','name' => get_lang('SessionList'));

// Database Table Definitions
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_class				= Database::get_main_table(TABLE_MAIN_SESSION_CLASS);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user							= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_class							= Database::get_main_table(TABLE_MAIN_CLASS);
$tbl_class_user						= Database::get_main_table(TABLE_MAIN_CLASS_USER);

// setting the name of the tool
$tool_name=get_lang('SubscribeUsersToSession');

$id_session=intval($_GET['id_session']);

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin()) {
	$sql = 'SELECT session_admin_id FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_session;
	$rs = Database::query($sql,__FILE__,__LINE__);
	if(Database::result($rs,0,0)!=$_user['user_id']) {
		api_not_allowed(true);
	}
}

//checking for extra field with filter on
include_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
include_once (api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');
$extra_field_list= UserManager::get_extra_fields();
$new_field_list = array();
if (is_array($extra_field_list)) {
	foreach ($extra_field_list as $extra_field) {
		//if is enabled to filter and is a "<select>" field type
		if ($extra_field[8]==1 && $extra_field[2]==4 ) {
			$new_field_list[] = array('name'=> $extra_field[3], 'variable'=>$extra_field[1], 'data'=> $extra_field[9]);
		}
	}
}

function search_users($needle,$type)
{
	global $tbl_user,$tbl_session_rel_user,$id_session;
	$xajax_response = new XajaxResponse();
	$return = '';

	if (!empty($needle) && !empty($type)) {

		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_setting('platform_charset');
		$needle = Database::escape_string($needle);
		$needle = api_convert_encoding($needle, $charset, 'utf-8');
		$user_anonymous=api_get_anonymous_id();

		$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
		$cond_user_id = '';
		if (!empty($id_session)) {
		$id_session = Database::escape_string($id_session);
			// check id_user from session_rel_user table
			$sql = 'SELECT id_user FROM '.$tbl_session_rel_user.' WHERE id_session ="'.(int)$id_session.'"';
			$res = Database::query($sql,__FILE__,__LINE__);
			$user_ids = array();
			if (Database::num_rows($res) > 0) {
				while ($row = Database::fetch_row($res)) {
					$user_ids[] = (int)$row[0];
				}
			}
			if (count($user_ids) > 0){
				$cond_user_id = ' AND user_id NOT IN('.implode(",",$user_ids).')';
			}
		}

		if ($type == 'single') {
			// search users where username or firstname or lastname begins likes $needle
			$sql = 'SELECT user_id, username, lastname, firstname FROM '.$tbl_user.' user
					WHERE (username LIKE "'.$needle.'%"
					OR firstname LIKE "'.$needle.'%"
				OR lastname LIKE "'.$needle.'%") AND user_id<>"'.$user_anonymous.'"'.
				$order_clause.
				' LIMIT 11';
		} else {
			$sql = 'SELECT user_id, username, lastname, firstname FROM '.$tbl_user.' user
					WHERE '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user_id<>"'.$user_anonymous.'"'.$cond_user_id.
					$order_clause;
		}

		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
				if ($type == 'single') {
					$sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
					INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
					WHERE access_url_id = '.$access_url_id.'  AND (username LIKE "'.$needle.'%"
					OR firstname LIKE "'.$needle.'%"
					OR lastname LIKE "'.$needle.'%") AND user.user_id<>"'.$user_anonymous.'"'.
					$order_clause.
					' LIMIT 11';
				} else {
					$sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
					INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
					WHERE access_url_id = '.$access_url_id.'
					AND '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user.user_id<>"'.$user_anonymous.'"'.$cond_user_id.
					$order_clause;
				}

			}
		}

		$rs = Database::query($sql, __FILE__, __LINE__);
        $i=0;
		if ($type=='single') {
			while ($user = Database :: fetch_array($rs)) {
	            $i++;
	            if ($i<=10) {
            		$person_name = api_get_person_name($user['firstname'], $user['lastname']);
					$return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_session(\''.$user['user_id'].'\',\''.$person_name.' ('.$user['username'].')'.'\')">'.$person_name.' ('.$user['username'].')</a><br />';
	            } else {
	            	$return .= '...<br />';
	            }
			}

			$xajax_response -> addAssign('ajax_list_users_single','innerHTML',api_utf8_encode($return));

		} else {
			global $nosessionUsersList;
			$return .= '<select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">';
			while ($user = Database :: fetch_array($rs)) {
				$person_name = api_get_person_name($user['firstname'], $user['lastname']);
	            $return .= '<option value="'.$user['user_id'].'">'.$person_name.' ('.$user['username'].')</option>';
			}
			$return .= '</select>';
			$xajax_response -> addAssign('ajax_list_users_multiple','innerHTML',api_utf8_encode($return));
		}
	}

	return $xajax_response;
}

$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user_to_session (code, content) {

	document.getElementById("user_to_add").value = "";
	document.getElementById("ajax_list_users_single").innerHTML = "";

	destination = document.getElementById("destination_users");

	for (i=0;i<destination.length;i++) {
		if(destination.options[i].text == content) {
				return false;
		}
	}

	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);

}
function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}

function validate_filter() {

		document.formulaire.add_type.value = \''.$add_type.'\';
		document.formulaire.form_sent.value=0;
		document.formulaire.submit();

}



</script>';


$form_sent=0;
$errorMsg=$firstLetterUser=$firstLetterSession='';
$UserList=$SessionList=array();
$users=$sessions=array();
$noPHP_SELF=true;

if($_POST['form_sent']) {
	$form_sent=$_POST['form_sent'];
	$firstLetterUser=$_POST['firstLetterUser'];
	$firstLetterSession=$_POST['firstLetterSession'];
	$UserList=$_POST['sessionUsersList'];
	$ClassList=$_POST['sessionClassesList'];
	if(!is_array($UserList)) {
		$UserList=array();
	}

	if ($form_sent == 1) {
		//added a parameter to send emails when registering a user
		SessionManager::suscribe_users_to_session($id_session,$UserList,true,true);

		//adding the session to the access_url_rel_session table
		/*global $_configuration;
		require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
		if ($_configuration['multiple_access_urls']==true) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			UrlManager::add_session_to_url($id_session,$access_url_id);
		} else {
			// we are filling by default the access_url_rel_session table
			UrlManager::add_session_to_url($id_session,1);
		}*/
		//if(empty($_GET['add']))
			//header('Location: '.Security::remove_XSS($_GET['page']).'?id_session='.$id_session);
		//else
		header('Location: resume_session.php?id_session='.$id_session);
	}
}

$session_info = SessionManager::fetch($id_session);
Display::display_header($tool_name);
//api_display_tool_title($tool_name.' ('.$session_info['name'].')');


$nosessionUsersList = $sessionUsersList = array();
/*$sql = 'SELECT COUNT(1) FROM '.$tbl_user;
$rs = Database::query($sql, __FILE__, __LINE__);
$count_courses = Database::result($rs, 0, 0);*/
$ajax_search = $add_type == 'unique' ? true : false;
global $_configuration;

$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
if ($ajax_search) {
	$sql="SELECT user_id, lastname, firstname, username, id_session
			FROM $tbl_user
			INNER JOIN $tbl_session_rel_user
				ON $tbl_session_rel_user.id_user = $tbl_user.user_id
				AND $tbl_session_rel_user.id_session = ".intval($id_session).
			$order_clause;

	if ($_configuration['multiple_access_urls']==true) {
		$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){
			$sql="SELECT u.user_id, lastname, firstname, username, id_session
			FROM $tbl_user u
			INNER JOIN $tbl_session_rel_user
				ON $tbl_session_rel_user.id_user = u.user_id
				AND $tbl_session_rel_user.id_session = ".intval($id_session)."
				INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
				WHERE access_url_id = $access_url_id
				$order_clause";
		}
	}
	$result=Database::query($sql,__FILE__,__LINE__);
	$Users=Database::store_result($result);
	foreach ($Users as $user) {
		$sessionUsersList[$user['user_id']] = $user ;
	}

} else {
		//Filter by Extra Fields
		$use_extra_fields = false;
		if (is_array($extra_field_list)) {
			if (is_array($new_field_list) && count($new_field_list)>0 ) {
				$result_list=array();
				foreach ($new_field_list as $new_field) {
					$varname = 'field_'.$new_field['variable'];
					if (Usermanager::is_extra_field_available($new_field['variable'])) {
						if (isset($_POST[$varname]) && $_POST[$varname]!='0') {
							$use_extra_fields = true;
							$extra_field_result[]= Usermanager::get_extra_user_data_by_value($new_field['variable'], $_POST[$varname]);
						}
					}
				}
			}
		}
		if ($use_extra_fields) {
			$final_result = array();
			if (count($extra_field_result)>1) {
				for($i=0;$i<count($extra_field_result)-1;$i++) {
					if (is_array($extra_field_result[$i+1])) {
						$final_result  = array_intersect($extra_field_result[$i],$extra_field_result[$i+1]);
					}
				}
			} else {
				$final_result = $extra_field_result[0];
			}

			$where_filter ='';
			if ($_configuration['multiple_access_urls']==true) {
				if (is_array($final_result) && count($final_result)>0) {
					$where_filter = " AND u.user_id IN  ('".implode("','",$final_result)."') ";
				} else {
					//no results
					$where_filter = " AND u.user_id  = -1";
				}
			} else {
				if (is_array($final_result) && count($final_result)>0) {
					$where_filter = " WHERE u.user_id IN  ('".implode("','",$final_result)."') ";
				} else {
					//no results
					$where_filter = " WHERE u.user_id  = -1";
				}
			}
		}

		if ($use_extra_fields) {
			$sql="SELECT  user_id, lastname, firstname, username, id_session
				FROM $tbl_user u
				LEFT JOIN $tbl_session_rel_user
				ON $tbl_session_rel_user.id_user = u.user_id AND id_session = '$id_session'
				$where_filter
			$order_clause";

		} else {
			$sql="SELECT  user_id, lastname, firstname, username, id_session
				FROM $tbl_user u
				LEFT JOIN $tbl_session_rel_user
				ON $tbl_session_rel_user.id_user = u.user_id AND id_session = '$id_session'
			$order_clause";
		}
		if ($_configuration['multiple_access_urls']==true) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
				$sql="SELECT  u.user_id, lastname, firstname, username, id_session
				FROM $tbl_user u
				LEFT JOIN $tbl_session_rel_user
					ON $tbl_session_rel_user.id_user = u.user_id AND id_session = '$id_session'
				INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
				WHERE access_url_id = $access_url_id  $where_filter
			$order_clause";
			}
		}
		
		$result=Database::query($sql,__FILE__,__LINE__);
		$Users=Database::store_result($result);
		//var_dump($_REQUEST['id_session']);
		foreach ($Users as $user) {
			if($user['id_session'] != $id_session)
				$nosessionUsersList[$user['user_id']] = $user ;
		}
		$user_anonymous=api_get_anonymous_id();
		foreach($nosessionUsersList as $key_user_list =>$value_user_list) {
			if ($nosessionUsersList[$key_user_list]['user_id']==$user_anonymous) {
				unset($nosessionUsersList[$key_user_list]);
			}
		}
		//filling the correct users in list
		$sql="SELECT  user_id, lastname, firstname, username, id_session
			FROM $tbl_user u
			LEFT JOIN $tbl_session_rel_user
			ON $tbl_session_rel_user.id_user = u.user_id AND id_session = '$id_session'
			$order_clause";

		if ($_configuration['multiple_access_urls']==true) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
				$sql="SELECT  u.user_id, lastname, firstname, username, id_session
				FROM $tbl_user u
				LEFT JOIN $tbl_session_rel_user
					ON $tbl_session_rel_user.id_user = u.user_id AND id_session = '$id_session'
				INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
				WHERE access_url_id = $access_url_id
				$order_clause";
			}
		}
	$result=Database::query($sql,__FILE__,__LINE__);
	$Users=Database::store_result($result);

	foreach($Users as $key_user_list =>$value_user_list) {
		if ($Users[$key_user_list]['user_id']==$user_anonymous) {
			unset($Users[$key_user_list]);
			}
		}

	foreach ($Users as $user) {
		if($user['id_session'] == $id_session){
			$sessionUsersList[$user['user_id']] = $user;
			if (array_key_exists($user['user_id'],$nosessionUsersList))
                unset($nosessionUsersList[$user['user_id']]);
		}
/*		else if ( $sessionUsersList[$user['user_id']]['id_session']!=$id_session )
			$nosessionUsersList[$user['user_id']] = $user ;*/
	}
}

if ($add_type == 'multiple') {
	$link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=unique">'.Display::return_icon('pixel.gif',get_lang('SessionAddTypeUnique'), array('class' => 'toolactionplaceholdericon toolactionsingle')).get_lang('SessionAddTypeUnique').'</a>';
	$link_add_type_multiple = '<a href="javascript:void(0)">'.Display::return_icon('pixel.gif',get_lang('SessionAddTypeMultiple'), array('class' => 'toolactionplaceholdericon toolactionmultiple')).get_lang('SessionAddTypeMultiple').'</a>';
} else {
	$link_add_type_unique = '<a href="javascript:void(0)">'.Display::return_icon('pixel.gif',get_lang('SessionAddTypeUnique'), array('class' => 'toolactionplaceholdericon toolactionsingle')).get_lang('SessionAddTypeUnique').'</a>';
	$link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=multiple">'.Display::return_icon('pixel.gif',get_lang('SessionAddTypeMultiple'), array('class' => 'toolactionplaceholdericon toolactionmultiple')).get_lang('SessionAddTypeMultiple').'</a>';
}


?>

<div class="actions">
	<?php
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/catalogue_management.php">' . Display::return_icon('pixel.gif',get_lang('Catalogue'), array('class' => 'toolactionplaceholdericon toolactioncatalogue')) . get_lang('Catalogue') . '</a>';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/topic_list.php">' . Display :: return_icon('pixel.gif', get_lang('Topics'),array('class' => 'toolactionplaceholdericon toolactiontopic')) . get_lang('Topics') . '</a>';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/programme_list.php">' . Display :: return_icon('pixel.gif', get_lang('Programmes'),array('class' => 'toolactionplaceholdericon toolactionprogramme')) . get_lang('Programmes') . '</a>';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_list.php">' . Display :: return_icon('pixel.gif', get_lang('SessionList'),array('class' => 'toolactionplaceholdericon toolactionsession')) . get_lang('Sessions') . '</a>';
	?>
	<?php echo $link_add_type_unique ?><?php echo $link_add_type_multiple ?>
</div>
<?php
// Start content page
echo '<div id="content">';

echo '<div class="row"><div class="form_header">'.$tool_name.' ('.$session_info['name'].')</div></div><br/>'; ?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?page=<?php echo Security::remove_XSS($_GET['page']); ?>&id_session=<?php echo $id_session; ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>

<?php
if ($add_type=='multiple') {
	if (is_array($extra_field_list)) {
		if (is_array($new_field_list) && count($new_field_list)>0 ) {
			echo '<h3>'.get_lang('FilterUsers').'</h3>';
			foreach ($new_field_list as $new_field) {
				echo $new_field['name'];
				$varname = 'field_'.$new_field['variable'];
				echo '&nbsp;<select name="'.$varname.'">';
				echo '<option value="0">--'.get_lang('Select').'--</option>';
				foreach	($new_field['data'] as $option) {
					$checked='';
					if (isset($_POST[$varname])) {
						if ($_POST[$varname]==$option[1]) {
							$checked = 'selected="true"';
						}
					}
					echo '<option value="'.$option[1].'" '.$checked.'>'.$option[1].'</option>';
				}
				echo '</select>';
				echo '&nbsp;&nbsp;';
			}
			echo '<input type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" />';
			echo '<br /><br />';
		}
	}
}
?>

<input type="hidden" name="form_sent" value="1" />
<input type="hidden" name="add_type"  />

<?php
if(!empty($errorMsg)) {
	Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<!-- Users -->
<tr>
  <td align="center"><b><?php echo get_lang('UserListInPlatform') ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('UserListInSession') ?> :</b></td>
</tr>

<?php if ($add_type=='multiple') { ?>
<tr>
<td align="center">

<?php echo get_lang('FirstLetterUser'); ?> :
     <select name="firstLetterUser" onchange = "xajax_search_users(this.value,'multiple')" >
      <option value = "%">--</option>
      <?php
        echo Display :: get_alphabet_options();
      ?>
     </select>
</td>
<td align="center">&nbsp;</td>
</tr>
<?php } ?>


<tr>
  <td align="center">
  <div id="content_source">
  	  <?php
  	  if (!($add_type=='multiple')) {
  	  	?>
		<input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')" />
		<div id="ajax_list_users_single"></div>
		<?php
  	  } else {
  	  ?>
  	  <div id="ajax_list_users_multiple">
	  <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">
		<?php
		foreach($nosessionUsersList as $enreg) {
		?>
			<option value="<?php echo $enreg['user_id']; ?>" <?php if(in_array($enreg['user_id'],$UserList)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>
		<?php
		}
		?>
	  </select>
	  </div>
	<?php
  	  }
  	  unset($nosessionUsersList);
  	 ?>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
  <?php
  if ($ajax_search) {
  ?>
  	<button class="arrowl" type="button" onclick="remove_item(document.getElementById('destination_users'))" ></button>
  <?php
  } else {
  ?>
  	<button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))"></button>
	<br /><br />
	<button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))"></button>
	<?php
  }
  ?>
	<br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
  <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:360px;">

<?php
foreach($sessionUsersList as $enreg) {
?>
	<option value="<?php echo $enreg['user_id']; ?>"><?php echo $enreg['firstname'].' '.$enreg['lastname'].' ('.$enreg['username'].')'; ?></option>

<?php
}

unset($sessionUsersList);
?>

  </select></td>
</tr>
<tr>
	<td colspan="3" align="center">
		<br />
		<?php
		if(isset($_GET['add'])) {
			echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('FinishSessionCreation').'</button>';
        } else {
            //@todo see that the call to "valide()" doesn't duplicate the onsubmit of the form (necessary to avoid delete on "enter" key pressed)
			echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('SubscribeUsersToSession').'</button>';

        }
		?>
	</td>
</tr>
</table>
</form>

<script type="text/javascript">
<!--
function moveItem(origin , destination){

	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
			origin.options[i]=null;
			i = i-1;
		}
	}
	destination.selectedIndex = -1;
	sortOptions(destination.options);

}

function sortOptions(options) {

	newOptions = new Array();
	for (i = 0 ; i<options.length ; i++)
		newOptions[i] = options[i];

	newOptions = newOptions.sort(mysort);
	options.length = 0;
	for(i = 0 ; i < newOptions.length ; i++)
		options[i] = newOptions[i];

}

function mysort(a, b){
	if(a.text.toLowerCase() > b.text.toLowerCase()){
		return 1;
	}
	if(a.text.toLowerCase() < b.text.toLowerCase()){
		return -1;
	}
	return 0;
}

function valide(){
	var options = document.getElementById('destination_users').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;
	document.forms.formulaire.submit();
}


function loadUsersInSelect(select){

	var xhr_object = null;

	if(window.XMLHttpRequest) // Firefox
		xhr_object = new XMLHttpRequest();
	else if(window.ActiveXObject) // Internet Explorer
		xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
	else  // XMLHttpRequest non supporté par le navigateur
	alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

	//xhr_object.open("GET", "loadUsersInSelect.ajax.php?id_session=<?php echo $id_session ?>&letter="+select.options[select.selectedIndex].text, false);
	xhr_object.open("POST", "loadUsersInSelect.ajax.php");

	xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");


	nosessionUsers = makepost(document.getElementById('origin_users'));
	sessionUsers = makepost(document.getElementById('destination_users'));
	nosessionClasses = makepost(document.getElementById('origin_classes'));
	sessionClasses = makepost(document.getElementById('destination_classes'));
	xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);

	xhr_object.onreadystatechange = function() {
		if(xhr_object.readyState == 4) {
			document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
			//alert(xhr_object.responseText);
		}
	}
}

function makepost(select){

	var options = select.options;
	var ret = "";
	for (i = 0 ; i<options.length ; i++)
		ret = ret + options[i].value +'::'+options[i].text+";;";

	return ret;

}
-->

</script>
<?php
// End content page
echo '</div>';
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
