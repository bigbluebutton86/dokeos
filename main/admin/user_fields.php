<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

// Language files that should be included
$language_file = array('admin','registration');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationprofiling';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
include_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// section for the tabs
$this_section=SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();

// Database table definitions
$table_admin	= Database :: get_main_table(TABLE_MAIN_ADMIN);
$table_user 	= Database :: get_main_table(TABLE_MAIN_USER);
$table_uf	 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD);
$table_uf_opt 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
$table_uf_val 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

// setting the breadcrumbs
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

if(1)
{
	$tool_name = get_lang('UserFields');

	// display the header
	Display :: display_header($tool_name, "");

	// action links
	echo '<div class="actions">';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php">'.Display::return_icon('pixel.gif',get_lang('UserList'), array('class' => 'toolactionplaceholdericon toolactionadminusers')).get_lang('UserList').'</a>';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_add.php">'.Display::return_icon('pixel.gif',get_lang('AddUsers'), array('class' => 'toolactionplaceholdericon toolactionaddusertocourse')).get_lang('AddUsers').'</a>';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_export.php">'.Display::return_icon('pixel.gif',get_lang('Export'), array('class' => 'toolactionplaceholdericon toolactionexportcourse')).get_lang('Export').'</a>';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_import.php">'.Display::return_icon('pixel.gif',get_lang('Import'), array('class' => 'toolactionplaceholdericon toolactionupload')).get_lang('Import').'</a>';
	echo '<a href="user_fields_add.php?action=fill">'.Display::return_icon('pixel.gif',get_lang('AddUserField'), array('class' => 'toolactionplaceholdericon toolactionsprofile')).get_lang('AddUserField').'</a>';
	echo '</div>';

	// display the tool title
	//api_display_tool_title($tool_name);

	// action handling
	if (isset ($_GET['action']))
	{
		$check = Security::check_token('get');
		if($check) {
			switch ($_GET['action']) {
				case 'show_message' :
					Display :: display_normal_message($_GET['message']);
					break;
				case 'show_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_visible'=>'1'))) {
						Display :: display_confirmation_message(get_lang('FieldShown'));
					} else {
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;
				case 'hide_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_visible'=>'0'))) {
						Display :: display_confirmation_message(get_lang('FieldHidden'));
					} else {
						Display :: display_error_message(get_lang('CannotHideField'));
					}
					break;
				case 'thaw_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'1'))) {
						Display :: display_confirmation_message(get_lang('FieldMadeChangeable'));
					} else {
						Display :: display_error_message(get_lang('CannotMakeFieldChangeable'));
					}
					break;
				case 'freeze_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'0'))) {
						Display :: display_confirmation_message(get_lang('FieldMadeUnchangeable'));
					} else {
						Display :: display_error_message(get_lang('CannotMakeFieldUnchangeable'));
					}
					break;
				case 'moveup' :
					if (api_is_platform_admin() && !empty($_GET['field_id'])) {
						if (move_user_field('moveup', $_GET['field_id'])) {
							Display :: display_confirmation_message(get_lang('FieldMovedUp'));
						} else {
							Display :: display_error_message(get_lang('CannotMoveField'));
						}
					}
					break;
				case 'movedown' :
					if (api_is_platform_admin() && !empty($_GET['field_id'])) {
						if (move_user_field('movedown', $_GET['field_id'])) {
							Display :: display_confirmation_message(get_lang('FieldMovedDown'));
						} else {
							Display :: display_error_message(get_lang('CannotMoveField'));
						}
					}
					break;
				case 'filter_on' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_filter'=>'1'))) {
						Display :: display_confirmation_message(get_lang('FieldFilterSetOn'));
					} else {
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;
				case 'filter_off' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_filter'=>'0'))) {
						Display :: display_confirmation_message(get_lang('FieldFilterSetOff'));
					} else {
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;

				case 'delete':
					if (api_is_platform_admin() && !empty($_GET['field_id'])) {
						if (delete_user_fields($_GET['field_id'])) {
							Display :: display_confirmation_message(get_lang('FieldDeleted'));
						} else {
							Display :: display_error_message(get_lang('CannotDeleteField'));
						}
					}
					break;
			}
			Security::clear_token();
		}
	}
	if (isset ($_POST['action'])) {
		$check = Security::check_token('get');
		if($check) {
			switch ($_POST['action']) {
				default:
					break;
			}
			Security::clear_token();
		}
	}

	// action links
/*	echo '<div class="actions">';
	echo '<a href="user_fields_add.php?action=fill">'.Display::return_icon('fieldadd.gif', get_lang('AddUserField')).get_lang('AddUserField').'</a>';
	echo '</div>';*/

	// Create a sortable table with user-data
	$parameters['sec_token'] = Security::get_token();
	$column_show  = array(1,1,1,1,1,1,1,1,1,0,0);
	$column_order = array(1,2,3,4,5,6,7,8,9,10,11);
	$extra_fields = UserManager::get_extra_fields(0,100,5,'ASC');
	$number_of_extra_fields = count($extra_fields);
	$table = new SortableTableFromArrayConfig($extra_fields, 5, 50, '', $column_show, $column_order, 'ASC');
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('FieldLabel'), false);
	$table->set_header(2, get_lang('FieldType'), false);
	$table->set_header(3, get_lang('FieldTitle'),false);
	$table->set_header(4, get_lang('FieldDefaultValue'),false);
	$table->set_header(5, get_lang('FieldOrder'), false);
	$table->set_header(6, get_lang('FieldVisibility'), false);
	$table->set_header(7, get_lang('FieldChangeability'), false);
	$table->set_header(8, get_lang('FieldFilter'), false);
	$table->set_header(9, get_lang('Modify'), false);
	$table->set_column_filter(5, 'order_filter');
	$table->set_column_filter(6, 'modify_visibility');
	$table->set_column_filter(7, 'modify_changeability');
	$table->set_column_filter(8, 'modify_field_filter');
	$table->set_column_filter(9, 'edit_filter');
	$table->set_column_filter(2, 'type_filter');

	// start the content div
	echo '<div id="content" class="maxcontent">';	

	// display the sortable table
	$table->display();

	// close the content div
	echo '</div>';
}

// display the footer
Display::display_footer();


//gateway functions to the UserManager methods (provided for SorteableTable callback mechanism)
function get_number_of_extra_fields()
{
	return UserManager::get_number_of_extra_fields();
}

function get_extra_fields($f,$n,$o,$d)
{
	return UserManager::get_extra_fields($f,$n,$o,$d);
}

/**
 * This functions translates the id of the form type into a human readable description
 *
 * @param integer $type the id of the form type
 * @return string the huma readable description of the field type (text, date, select drop-down, ...)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function type_filter($type)
{
	$types[USER_FIELD_TYPE_TEXT]  				= get_lang('FieldTypeText');
	$types[USER_FIELD_TYPE_TEXTAREA] 			= get_lang('FieldTypeTextarea');
	$types[USER_FIELD_TYPE_RADIO] 				= get_lang('FieldTypeRadio');
	$types[USER_FIELD_TYPE_SELECT] 				= get_lang('FieldTypeSelect');
	$types[USER_FIELD_TYPE_SELECT_MULTIPLE] 	= get_lang('FieldTypeSelectMultiple');
	$types[USER_FIELD_TYPE_DATE] 				= get_lang('FieldTypeDate');
	$types[USER_FIELD_TYPE_DATETIME] 			= get_lang('FieldTypeDatetime');
	$types[USER_FIELD_TYPE_DOUBLE_SELECT] 		= get_lang('FieldTypeDoubleSelect');
	$types[USER_FIELD_TYPE_DIVIDER] 			= get_lang('FieldTypeDivider');
	$types[USER_FIELD_TYPE_TAG] 				= get_lang('FieldTypeTag');
	return $types[$type];
}

/**
 * Modify the display order field into up and down arrows
 *
 * @param unknown_type $field_order
 * @param	array	Url parameters
 * @param	array	The results row
 * @return	string	The link
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function order_filter($field_order,$url_params,$row)
{
	global $number_of_extra_fields;

	// the up icon only has to appear when the row can be moved up (all but the first row)
	if ($row[5]<>1)
	{
		$return .= '<a href="'.api_get_self().'?action=moveup&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif', get_lang('Up'),array('class'=>'actionplaceholdericon actioniconup')).'</a>';
	}
	else
	{
		$return .= Display::return_icon('blank.gif','',array('width'=>'21px'));
	}

	// the down icon only has to appear when the row can be moved down (all but the last row)
	if ($row[5]<>$number_of_extra_fields)
	{
		$return .= '<a href="'.api_get_self().'?action=movedown&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif', get_lang('Down'),array('class'=>'actionplaceholdericon actionicondown')).'</a>';
	}

	return $return;
}
/**
 * Modify the visible field to show links and icons
 * @param	int 	The current visibility
 * @param	array	Url parameters
 * @param	array	The results row
 * @return	string	The link
 */
function modify_visibility($visibility,$url_params,$row)
{
	return ($visibility?'<a href="'.api_get_self().'?action=hide_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif',get_lang('Hide'), array('class' => 'actionplaceholdericon actionvisible')).'</a>':'<a href="'.api_get_self().'?action=show_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif',get_lang('Show'), array('class' => 'actionplaceholdericon actionvisible invisible')).'</a>');
}
/**
 * Modify the changeability field to show links and icons
 * @param	int 	The current changeability
 * @param	array	Url parameters
 * @param	array	The results row
 * @return	string	The link
 */
function modify_changeability($changeability,$url_params,$row)
{
	return ($changeability?'<a href="'.api_get_self().'?action=freeze_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif', get_lang('MakeUnchangeable'),array('class'=>'actionplaceholdericon actionsvalidate')).'</a>':'<a href="'.api_get_self().'?action=thaw_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif', get_lang('MakeChangeable'),array('class'=>'actionplaceholdericon actionwrongconvertir')).'</a>');
}

function modify_field_filter ($changeability,$url_params,$row)
{
	return ($changeability?'<a href="'.api_get_self().'?action=filter_off&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif', get_lang('FilterOff'),array('class'=>'actionplaceholdericon actionsvalidate')).'</a>':'' .
						   '<a href="'.api_get_self().'?action=filter_on&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif', get_lang('FilterOn'),array('class'=>'actionplaceholdericon actionwrongconvertir')).'</a>');
}

function edit_filter($id,$url_params,$row)
{
	global $charset;
	$return = '<a href="user_fields_add.php?action=edit&field_id='.$row[0].'&field_type='.$row[2].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('pixel.gif', get_lang('Edit'), array('class' => 'actionplaceholdericon actionedit')).'</a>';
	$return .= ' <a href="'.api_get_self().'?action=delete&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('pixel.gif', get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete')).'</a>';
	return $return;
}
/**
 * Move a user defined field up or down
 *
 * @param string $direction the direction we have to move the field to (up or down)
 * @param unknown_type $field_id
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function move_user_field($direction,$field_id)
{
	// Databse table definitions
	$table_user_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);

	// check the parameters
	if (!in_array($direction,array('moveup','movedown')) OR !is_numeric($field_id))
	{
		return false;
	}

	// determine the SQL sort direction
	if ($direction == 'moveup')
	{
		$sortdirection = 'DESC';
	}
	else
	{
		$sortdirection = 'ASC';
	}

	$found = false;

	$sql = "SELECT id, field_order FROM $table_user_field ORDER BY field_order $sortdirection";
	$result = Database::query($sql,__FILE__,__LINE__);
	while($row = Database::fetch_array($result))
	{
		if ($found)
		{
			$next_id = $row['id'];
			$next_order = $row['field_order'];
			break;
		}

		if ($field_id == $row['id'])
		{
			$this_id = $row['id'];
			$this_order = $row['field_order'];
			$found = true;
		}
	}

	$sql1 = "UPDATE ".$table_user_field." SET field_order = '".Database::escape_string($next_order)."' WHERE id =  '".Database::escape_string($this_id)."'";
	$sql2 = "UPDATE ".$table_user_field." SET field_order = '".Database::escape_string($this_order)."' WHERE id =  '".Database::escape_string($next_id)."'";
	Database::query($sql1,__FILE__,__LINE__);
	Database::query($sql2,__FILE__,__LINE__);

	return true;
}

/**
 * Delete a user field (and also the options and values entered by the users)
 *
 * @param integer $field_id the id of the field that has to be deleted
 * @return boolean true if the field has been deleted, false if the field could not be deleted (for whatever reason)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function delete_user_fields($field_id)
{
	// Database table definitions
	$table_user_field 			= Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$table_user_field_options	= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
	$table_user_field_values 	= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	// delete the fields
	$sql = "DELETE FROM $table_user_field WHERE id = '".Database::escape_string($field_id)."'";
	$result = Database::query($sql,__FILE__,__LINE__);
	if (Database::affected_rows() == 1)
	{
		// delete the field options
		$sql = "DELETE FROM $table_user_field_options WHERE field_id = '".Database::escape_string($field_id)."'";
		$result = Database::query($sql,__FILE__,__LINE__);

		// delete the field values
		$sql = "DELETE FROM $table_user_field_values WHERE field_id = '".Database::escape_string($field_id)."'";
		$result = Database::query($sql,__FILE__,__LINE__);

		// recalculate the field_order because the value is used to show/hide the up/down icon
		// and the field_order value cannot be bigger than the number of fields
		$sql = "SELECT * FROM $table_user_field ORDER BY field_order ASC";
		$result = Database::query($sql,__FILE__,__LINE__);
		$i = 1;
		while($row = Database::fetch_array($result))
		{
			$sql_reorder = "UPDATE $table_user_field SET field_order = '".Database::escape_string($i)."' WHERE id = '".Database::escape_string($row['id'])."'";
			$result_reorder = Database::query($sql_reorder,__FILE__,__LINE__);
			$i++;
		}

		// field was deleted so we return true
		return true;
	}
	else
	{
		// the field was not deleted so we return false
		return false;
	}
}
?>
