<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

// we are not inside a course, so we reset the course id
$cidReset = true;

// including the global file that gets the general configuration, the databases, the languages, ...
include ('../inc/global.inc.php');

switch ($_POST['action']){
	case 'savepluginorder':
		savepluginorder();
		break;
}

function savepluginorder(){
	// database table definition
	$table_setting = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	// first we delete the existing plugin order
	$sql = "DELETE FROM $table_setting WHERE variable='pluginorder'";
	$result = api_sql_query ( $sql );

	// now we save the pluginorder
	$sql = "INSERT INTO $table_setting (variable, selected_value, category) VALUES ('pluginorder','" . Database::escape_string ( implode(',',$_POST['plugin']) ) . "','system')";
	$result = api_sql_query ( $sql );

	Display::display_confirmation_message('PluginOrderChanged');
}
?>
