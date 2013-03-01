<?php
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
 
$language_file= 'userInfo';
$cidReset=true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'/formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

api_block_anonymous_users();

if (api_get_setting('allow_students_to_create_groups_in_social') == 'false' && !api_is_allowed_to_edit()) {
	api_not_allowed();
}

global $charset;

//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script type="text/javascript">
textarea = "";
num_characters_permited = 255;
function text_longitud(){
   num_characters = document.forms[0].description.value.length;
  if (num_characters > num_characters_permited){
      document.forms[0].description.value = textarea;
   }else{
      textarea = document.forms[0].description.value;
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

$htmlHeadXtra[] = '
	<style>
	div.row div.label{
		width: 10%;
	}
	div.row div.formw{
		width: 85%;
	}

	</style>
	';

$table_message = Database::get_main_table(TABLE_MESSAGE);

$form = new FormValidator('add_group');

// name
$form->addElement('text', 'name', get_lang('Name'), array('class'=>'focus','size'=>60, 'maxlength'=>120));
$form->applyFilter('name', 'html_filter');
$form->applyFilter('name', 'trim');
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

// Description
$form->addElement('textarea', 'description', get_lang('Description'), array('rows'=>3, 'cols'=>58, onKeyDown => "text_longitud()", onKeyUp => "text_longitud()"));
$form->applyFilter('description', 'html_filter');
$form->applyFilter('description', 'trim');

// url
//$form->addElement('text', 'url', get_lang('URL'), array('size'=>35));
//$form->applyFilter('url', 'html_filter');
//$form->applyFilter('url', 'trim');

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

// Status
$status = array();
$status[GROUP_PERMISSION_OPEN] 		= get_lang('Open');
$status[GROUP_PERMISSION_CLOSED]	= get_lang('Closed');

$form->addElement('select', 'visibility', get_lang('GroupPermissions'), $status);
$form->addElement('style_submit_button','add_group', get_lang('AddGroup'),'class="save"');

$form->setDefaults($default);
if ($form->validate()) {
	$values = $form->exportValues();

	$picture_element = & $form->getElement('picture');
	$picture 		= $picture_element->getValue();
	$picture_uri 	= '';
	$name 			= $values['name'];
	$description	= $values['description'];
	//$url 			= $values['url'];
        $url = '';
	$status 		= intval($values['visibility']);
	$picture 		= $_FILES['picture'];

	$group_id = GroupPortalManager::add($name, $description, $url, $status);
	GroupPortalManager::add_user_to_group(api_get_user_id(), $group_id,GROUP_USER_PERMISSION_ADMIN);
		
	if (!empty($picture['name'])) {
		$picture_uri = GroupPortalManager::update_group_picture($group_id, $_FILES['picture']['name'], $_FILES['picture']['tmp_name']);
		GroupPortalManager::update($group_id, $name, $description, $url,$status, $picture_uri);
	}
	header('Location: groups.php?id='.$group_id.'&action=show_message&message='.urlencode(get_lang('GroupAdded')));
	exit();		
}

$nameTools = get_lang('AddGroup');
$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[]= array ('url' =>'#','name' => $nameTools);
Display :: display_header($tool_name, 'Groups');
// Display actions
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.Display::return_icon('pixel.gif',get_lang('Home'),array('class' => 'toolactionplaceholdericon toolactionshome')).get_lang('Home').'</a>';
// Only admins and teachers can create groups
if (api_is_allowed_to_edit(null,true)) {
    echo '<a href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.Display::return_icon('pixel.gif',get_lang('CreateAgroup'),array('class' => 'toolactionplaceholdericon toolactionsgroup')).get_lang('CreateAgroup').'</a>';
}
echo '<a href="'.api_get_path(WEB_PATH).'main/social/groups.php?view=mygroups">'.Display::return_icon('pixel.gif',get_lang('MyGroups'), array('class' => 'toolactionplaceholdericon toolactiongroupimage')).get_lang('MyGroups').'</a>';
echo '</div>';
// Start content
echo '<div id="content">';

echo '<div id="social-content">';
	echo '<div id="social-content-left">';
		//show the action menu			
		SocialManager::show_social_menu('group_add');
	echo '</div>';
	echo '<div id="social-content-right">';
		$form->display();	
	echo '</div>';
echo '</div>';

// End content
echo '</div>';

// Actions bar
echo '<div class="actions">';
echo '</div>';
Display :: display_footer();
?>
