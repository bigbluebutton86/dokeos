<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('TermsAndConditions');
Display :: display_header($tool_name);

require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'security.lib.php');
require_once (api_get_path(LIBRARY_PATH).'legal.lib.php');

$parameters['sec_token'] = Security::get_token();

// action menu
echo '<div class="actions" style="height:22px;">';
echo '<div style="float:right;">
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/legal_add.php">'.Display::return_icon('pixel.gif',get_lang('EditTermsAndConditions'),array('class'=>'actionplaceholdericon actionedit'),'').get_lang('EditTermsAndConditions').'</a>&nbsp;&nbsp;
	  </div><br />';
echo '</div>';
echo '<div id="content">';

// Actions
if (isset ($_GET['action'])) {
	if ($_GET['action'] == 'show_message')
		Display :: display_normal_message(Security::remove_XSS(stripslashes($_GET['message'])));
	Security::clear_token();
}

$table = new SortableTable('conditions', 'count_mask', 'get_legal_data_mask',2);
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Version'), false, 'width="15px"');
$table->set_header(1, get_lang('Language'), false, 'width="30px"');
$table->set_header(2, get_lang('Content'),false);
$table->set_header(3, get_lang('Changes'), false, 'width="60px"');
$table->set_header(4, get_lang('Type'), false, 'width="60px"');
$table->set_header(5, get_lang('Date'), false, 'width="50px"');
$table->display();

echo '</div>';

// this 2 "mask" function are here just because the SortableTable
function get_legal_data_mask($id, $params=null, $row=null) {
	return LegalManager::get_legal_data($id, $params, $row);
}
function count_mask() {
	return LegalManager::count();
}

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>
