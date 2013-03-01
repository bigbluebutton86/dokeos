<?php

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.group
==============================================================================
*/

// name of the language file that needs to be included
$language_file = "group";

// including the global Dokeos file
require_once ('../inc/global.inc.php');

$this_section = SECTION_COURSES;

// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// the section (fro the tabs)
$this_section = SECTION_COURSES;

// get all the information of the group
$current_group = GroupManager :: get_group_properties($_SESSION['_gid']);

$nameTools = get_lang('EditGroup');

// breadcrumbs
$interbreadcrumb[] = array ("url" => "group.php", "name" => get_lang('Groups'));

// access restriction
if (!api_is_allowed_to_edit(false,true)) {
	api_not_allowed(true);
}


/*
==============================================================================
		MAIN CODE
==============================================================================
*/


// display the header
Display :: display_header($nameTools, "Group");
?>

<div class="actions">
 <a href="group.php?<?php echo api_get_cidreq(); ?>"><?php  echo Display::return_icon('pixel.gif', get_lang('ReturnTo').' '.get_lang('GroupSpace'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('ReturnTo').' '.get_lang('Groups') ?></a>
</div>

<?php
//start the content div
echo '<div id="content">';

$table_group = Database :: get_course_table(TABLE_GROUP);
$sql = "SELECT * FROM $table_group WHERE id = ".$_REQUEST['group_id'];
$rs = Database::query($sql,__FILE__,__LINE__);
$row = Database::fetch_array($rs);

echo '<div id="scenario_custom" class="section">';
		echo '	<div class="sectiontitle">';
		echo 	get_lang('CreateNewScenario');
		echo '	</div>';
		echo '	<div class="sectioncontent">';
		$form = new FormValidator('group_category','post','?'.api_get_cidreq().'&group_id='.$_REQUEST['group_id']);
		$form->addElement('hidden', 'groupnames');
		$form->addElement('hidden', 'userspergroup');
		$defaults['groupnames']=str_replace('"','*',serialize($_POST['group_name']));	
		$defaults['userspergroup']=str_replace('"','*',serialize($_POST['users_of_group']));

	/*	$form->addElement('radio', 'scenario', null, get_lang('Tutoring').'<br/><span style="padding-left:20px;">'.get_lang('ScenarioText1').'</span>'.'<br/>'.'<span style="padding-left:20px;">'.get_lang('ScenarioTools1').'</span>', 1);
		$form->addElement('radio', 'scenario', null, get_lang('Collaboration').'<br/><span style="padding-left:20px;">'.get_lang('ScenarioText2').'</span>'.'<br/>'.'<span style="padding-left:20px;">'.get_lang('ScenarioTools2').'</span>', 2);
		$form->addElement('radio', 'scenario', null, get_lang('Competition').'<br/><span style="padding-left:20px;">'.get_lang('ScenarioText3').'</span>'.'<br/>'.'<span style="padding-left:20px;">'.get_lang('ScenarioTools3').'</span>', 3);*/

		$form->addElement('radio', 'scenario', null, '<b>'.get_lang('Tutoring').'</b>', 1);
		$form->addElement('html','<table width="100%" border="0"><tr><td width="15%">&nbsp;</td><td>'.get_lang('ScenarioText1').'</td></tr>'.'<tr><td width="15%">&nbsp;</td><td>'.get_lang('ScenarioTools1').'</td></tr></table>');
		$form->addElement('radio', 'scenario', null, '<b>'.get_lang('Collaboration').'</b>', 2);
		$form->addElement('html','<table width="100%" border="0"><tr><td width="15%">&nbsp;</td><td>'.get_lang('ScenarioText2').'</td></tr>'.'<tr><td width="15%">&nbsp;</td><td>'.get_lang('ScenarioTools2').'</td></tr></table>');
		$form->addElement('radio', 'scenario', null, '<b>'.get_lang('Competition').'</b>', 3);
		$form->addElement('html','<table width="100%" border="0"><tr><td width="15%">&nbsp;</td><td>'.get_lang('ScenarioText3').'</td></tr>'.'<tr><td width="15%">&nbsp;</td><td>'.get_lang('ScenarioTools3').'</td></tr></table>');
		$form->addElement('style_submit_button', 'submit', get_lang('Ok'), 'class="save"');
		
		$defaults['scenario'] = $row['category_id'];
		$form->setDefaults($defaults);	
		if ($form->validate()) {
			$values = $form->exportValues();

			$sql = "UPDATE $table_group SET category_id = ".$values['scenario']." WHERE id = ".$_REQUEST['group_id'];
			Database::query($sql,__FILE__,__LINE__);
			echo '<script>window.location.href = "group.php"</script>';
		}
		$form->display();
		echo '	</div>';
		echo '</div>';

// close the content div
echo '</div>';

// display secondary actions
echo '<div class="actions">&nbsp;</div>';

// display the footer
Display :: display_footer();
?>
