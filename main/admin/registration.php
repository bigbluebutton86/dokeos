<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @author Bart Mollet
* @package dokeos.admin
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationsessionadd';

// including the global Dokeos file
require_once '../inc/global.inc.php';

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

//display the header
Display::display_header(get_lang('TrainingCategory'));

// start the content div
echo '<div id="content">';
// Steps breadcrumbs
SessionManager::display_steps_breadcrumbs();

if (!isset($_SESSION['steps'][1])) {
    $_SESSION['steps'][1] = true;
}

if (!isset($_SESSION['steps'][2])) {
    $_SESSION['steps'][2] = true;
}

if(isset($_REQUEST['iden'])){
	$iden = $_REQUEST['iden'];
}
else {
	$iden = 0;
}

if(isset($_REQUEST['wish'])){
	$wish = $_REQUEST['wish'];
}
else {
	$wish = 1;
}


if(isset($_REQUEST['id'])){
	$_SESSION['cat_id'] = $_REQUEST['id'];
}

$product = SessionManager::get_session_category($_REQUEST['id']);                                            
echo '<div class="row"><div class="form_header register-payment-steps-name"><h2>'.$product['name'].'</h2></div></div>';

$form = new FormValidator('registration');	
$form->addElement('header', '', get_lang('Identification'));
$form->addElement('hidden','formSent','1');
$form->addElement('hidden','id', intval($_SESSION['cat_id']));

$form->addElement('radio', 'identification', '', get_lang('Individual'), 0);
$form->addElement('radio', 'identification', '', get_lang('Collectivity'), 1);

$form->addElement('header', '', get_lang('YouWish'));
$form->addElement('radio', 'wish', '', get_lang('Personally'), 0);
$form->addElement('radio', 'wish', '', get_lang('Seperate'), 1);

$form->addElement('html','</br></br></br>');
$select_level = array (); 	
$navigator_info = api_get_navigator(); 
if ($navigator_info['name'] == 'Internet Explorer' && ($navigator_info['version'] >= '6')) {	
	$html_results_enabled[] = FormValidator :: createElement ('submit', 'previous', get_lang('Previous'), 'style="background-color: #4171B5;;height:32px;border:1px solid #b8b8b6;text-transform:uppercase;font-weight:bold;color:#fff;"');
	$html_results_enabled[] = FormValidator :: createElement ('submit', 'submit', get_lang('Ok'), 'style="background-color: #4171B5;;height:32px;border:1px solid #b8b8b6;text-transform:uppercase;font-weight:bold;color:#fff;"');
}
else {
	$html_results_enabled[] = FormValidator :: createElement ('style_submit_button', 'previous', get_lang('Previous'), '');
	$html_results_enabled[] = FormValidator :: createElement ('style_submit_button', 'submit', get_lang('Ok'), '');
}
$form->addGroup($html_results_enabled);

$defaults['identification'] = isset($_SESSION['iden'])?intval($_SESSION['iden']):0;
$defaults['wish'] = isset($_SESSION['wish'])?intval($_SESSION['wish']):0;
$form->setDefaults($defaults);

if( $form->validate()) {
	$user = $form->exportValues();	
	$iden = $user['identification'];
	$wish = $user['wish'];
	$id   = $user['id'];

	if (isset($_POST['previous'])) {
		echo '<script>window.location.href = "'.api_get_path(WEB_CODE_PATH).'admin/category_list.php?id='.$id.'&prev=1";</script>';
	}
	else {		
		echo '<script>window.location.href = "registration_step3.php?iden='.$iden.'&wish='.$wish.'&id='.$id.'&next=3";</script>';
	}
}

$form->display();

// close the content div
echo '</div>';

// display the footer
Display::display_footer();
?>