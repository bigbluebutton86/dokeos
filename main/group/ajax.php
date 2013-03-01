<?php
// name of the language file that needs to be included
$language_file = "group";

// including the global Dokeos file
require_once ('../inc/global.inc.php');

$table_group = Database :: get_course_table(TABLE_GROUP);

switch ($_GET['action']){
	case 'group_name_form_elements':
		group_name_form_elements($_GET['number_of_groups'],$_GET['number_of_users_per_group']);
		break;
}

function group_name_form_elements($number_of_groups,$number_of_users_per_group){
	$table_group = Database :: get_course_table(TABLE_GROUP);

	echo '<form id="group_creation" name="group_creation" method="post" action="group_creation.php?cidReq='.Security::remove_XSS($_GET['cidReq']).'&action=save_groups">';
	for ($i = 0; $i < $number_of_groups; $i++) {		
		for($k = 0;$k<=20;$k++){			
			$j = $k+1;
			$sql = "SELECT * FROM $table_group WHERE name = '".get_lang('Group').' '.($j)."'";
			$rs = Database::query($sql,__FILE__,__LINE__);
			$num_rows = Database::num_rows($rs);
			if($num_rows <> 0){				
				continue;
			}
			else {				
				$group_no = $k + 1;			
				break;
			}
		}
		
		if($i == 0){
		$group_name = get_lang('Group').' '.($group_no);
		}
		else {
		$group_name = get_lang('Group').' '.($group_no + $i);
		}
		echo '<div class="marginbottom">';
		echo '<input type="text" name="group_name[]" value="'.$group_name.'">';
		echo '<input type="text" name="users_of_group[]" value="'.Security::remove_XSS($number_of_users_per_group).'" size="3">';
		echo '</div>';
	}
	echo '<button type="submit" name="action" class="save">'.get_lang('SaveGroups').'</button>';
	echo '</form>';
}
?>	
