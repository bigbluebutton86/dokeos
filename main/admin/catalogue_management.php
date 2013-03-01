<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @author Bart Mollet
* @package dokeos.admin
*/

// name of the language file that needs to be included
$language_file = array ('admin');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationsessionadd';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once(api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');
require_once ('../inc/lib/xajax/xajax.inc.php');
require_once (api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_list.php","name" => get_lang('SessionList'));

// Displaying the header
Display::display_header($nameTools);

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/catalogue_management.php">' . Display::return_icon('pixel.gif',get_lang('Catalogue'), array('class' => 'toolactionplaceholdericon toolactioncataloguecircle')) . get_lang('Catalogue') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/topic_list.php">' . Display :: return_icon('pixel.gif', get_lang('Topics'),array('class' => 'toolactionplaceholdericon toolactiontopic')) . get_lang('Topics') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/programme_list.php">' . Display :: return_icon('pixel.gif', get_lang('Programmes'),array('class' => 'toolactionplaceholdericon toolactionprogramme')) . get_lang('Programmes') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_list.php">' . Display :: return_icon('pixel.gif', get_lang('SessionList'),array('class' => 'toolactionplaceholdericon toolactionsession')) . get_lang('SessionList') . '</a>';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_export.php">'.Display::return_icon('pixel.gif',get_lang('ExportSessionListXMLCSV'),array('class' => 'toolactionplaceholdericon toolactionexportcourse')).get_lang('ExportSessionListXMLCSV').'</a>';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_import.php">'.Display::return_icon('pixel.gif',get_lang('ImportSessionListXMLCSV'),array('class' => 'toolactionplaceholdericon toolactionimportcourse')).get_lang('ImportSessionListXMLCSV').'</a>';	        
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'coursecopy/copy_course_session.php">'.Display::return_icon('pixel.gif',get_lang('CopyFromCourseInSessionToAnotherSession'),array('class' => 'toolactionplaceholdericon toolsettings')).get_lang('CopyFromCourseInSessionToAnotherSession').'</a>';
echo '</div>';

echo '<style>
		div.row {
			width: 900px;			
		}
		div.row div.label{
			width: 275px; 					
		} 
		div.row div.formw{
			width: 600px; 					
		} 
		</style>';

echo '<div id="content">';

echo '<div class="quiz_content_actions">';

$catalogue_table 		= Database :: get_main_table(TABLE_MAIN_CATALOGUE);

$sql = "SELECT * FROM $catalogue_table";
$res = Database::query($sql,__FILE__,__LINE__);
$num_rows = Database::num_rows($res);
$row = Database::fetch_array($res);

if($num_rows > 0 && !isset($_REQUEST['action'])){
	echo '<script>window.location.href = "'.api_get_self().'?action=edit"</script>';
}


//Form for catalogue management
if($_REQUEST['action'] == 'edit'){
$form = new FormValidator('catalogue', 'post',api_get_self().'?action=edit');
}
else {
$form = new FormValidator('catalogue', 'post','');
}
$form->addElement('header', '', get_lang('CatalogueManagement'));

$form->addElement('text', 'title', get_lang('Title'), 'class="focus";style="width:300px;"');	

$radios_economic_model[] = FormValidator :: createElement('radio', null, null, get_lang('Commercial'), '1');
$radios_economic_model[] = FormValidator :: createElement('radio', null, null, get_lang('NonCommercial'), '0');
$form->addGroup($radios_economic_model, 'economic_model', get_lang('EconomicModel'));

$radios_visible[] = FormValidator :: createElement('radio', null, null, get_lang('Onhomepage'), '1');
$radios_visible[] = FormValidator :: createElement('radio', null, null, get_lang('NotVisible'), '0');
$form->addGroup($radios_visible, 'visible', get_lang('Visible'));

$form->addElement('html','<table width="100%" border="0"><tr><td><div class="row"><div class="label">'.get_lang('DisplayInCatalogue').'</div><div class="formw"><input name="catalogue_display[]" type="checkbox" value="ProgrammeName"');
if(strpos($row['catalogue_display'],"ProgrammeName") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('ProgrammeName').'<input name="catalogue_display[]" type="checkbox" value="Image"');
if(strpos($row['catalogue_display'],"Image") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Image').'<input name="catalogue_display[]" type="checkbox" value="Description"');
if(strpos($row['catalogue_display'],"Description") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Description').'<input name="catalogue_display[]" type="checkbox" value="Topic"');
if(strpos($row['catalogue_display'],"Topic") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Topic').'<input name="catalogue_display[]" type="checkbox" value="Location"');
if(strpos($row['catalogue_display'],"Location") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Location').'<br/>'.'<input name="catalogue_display[]" type="checkbox" value="Modality"');
if(strpos($row['catalogue_display'],"Modality") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Modality').'<input name="catalogue_display[]" type="checkbox" value="Availability"');
if(strpos($row['catalogue_display'],"Availability") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Availability').'<input name="catalogue_display[]" type="checkbox" value="Start"');
if(strpos($row['catalogue_display'],"Start") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Start').'<input name="catalogue_display[]" type="checkbox" value="End"');
if(strpos($row['catalogue_display'],"End") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('End').'<input name="catalogue_display[]" type="checkbox" value="Language"');
if(strpos($row['catalogue_display'],"Language") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Language').'<br/>'.'<input name="catalogue_display[]" type="checkbox" value="Price"');
if(strpos($row['catalogue_display'],"Price") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Price'));
/*
$form->addElement('html','>'.get_lang('SessionList').'<input name="catalogue_display[]" type="checkbox" value="CourseList"');
if(strpos($row['catalogue_display'],"CourseList") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('CourseList').'<input name="catalogue_display[]" type="checkbox" value="Hours"');
if(strpos($row['catalogue_display'],"Hours") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Hours').'</div></div></td></tr>');
*/
$form->addElement('html','</table>');



$form->addElement('html','<table width="100%" border="0"><tr><td><div class="row"><div class="label">'.get_lang('PaymentMethods').'</div><div class="formw"><input name="payment_methods[]" type="checkbox" value="Online"');
if(strpos($row['payment'],"Online") !== false){
	$form->addElement('html',' checked ');
}
$form->addElement('html','>'.get_lang('Online').'<input name="payment_methods[]" type="checkbox" value="Cheque"');
if(strpos($row['payment'],"Cheque") !== false){
	$form->addElement('html',' checked ');
}

$form->addElement('html','>'.get_lang('Cheque'));

$form->addElement('html','</div></div></td></tr>');
$form->addElement('html','</table>');

//$form->addElement('text', 'atos_acno', get_lang('ATOSAccountNo'), 'class="focus";style="width:300px;"');	
//$form->addElement('text', 'paypal_account_ref', get_lang('PaypalAccountRef'), 'class="focus";style="width:300px;"');	
/*
$form->addElement('html','<table width="100%" border="0"><tr><td><div class="row"><div class="label"><b>'.get_lang('InstallmentSplit').'</b></div></div></td></tr>');
$form->addElement('html','<tr><td>');
$form->addElement('static','',get_lang('FirstInstallment'),get_lang('Immediate'));
$form->addElement('html','</td></tr>');
$form->addElement('html','<tr><td><div class="row"><div class="label">'.get_lang('SecondInstallment').'</div><div class="formw">');
$form->addElement('html','<input type="text" name="second_installment" size="10"');
if($_REQUEST['action'] == 'edit'){
	$form->addElement('html',' value="'.$row['second_installment'].'"');
}
$form->addElement('html','>&nbsp;'.get_lang('SecondInstallmentPriceAmt').'&nbsp;&nbsp;'.get_lang('Delay').'<input type="text" name="second_delay" size="5"');
if($_REQUEST['action'] == 'edit'){
	$form->addElement('html',' value="'.$row['second_installment_delay'].'"');
}
$form->addElement('html','>&nbsp;&nbsp;'.get_lang('Weeks'));
$form->addElement('html','</div></div></td></tr>');
$form->addElement('html','</td></tr>');
$form->addElement('html','<tr><td><div class="row"><div class="label">'.get_lang('ThirdInstallment').'</div><div class="formw">');
$form->addElement('html','<input type="text" name="third_installment" size="10"');
if($_REQUEST['action'] == 'edit'){
	$form->addElement('html',' value="'.$row['third_installment'].'"');
}
$form->addElement('html','>&nbsp;'.get_lang('ThirdInstallmentPriceAmt').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.get_lang('Delay').'&nbsp;'.'<input type="text" name="third_delay" size="5"');
if($_REQUEST['action'] == 'edit'){
	$form->addElement('html',' value="'.$row['third_installment_delay'].'"');
}
$form->addElement('html','>&nbsp;&nbsp;'.get_lang('Weeks'));
$form->addElement('html','</div></div></td></tr>');
$form->addElement('html','</table>');
*/


$form->addElement('header', '', get_lang('InvoicingInformation'));
$form->addElement('file', 'file', get_lang('CompanyLogo'), 'size="40"');

$form->addElement('textarea','company_address',get_lang('CompanyAddress'),array ('rows' => '3', 'cols' => '60'));
$form->addElement('textarea','bank_details',get_lang('BankDetails'),array ('rows' => '3', 'cols' => '60'));

$editor_config = array('ToolbarSet' => 'Catalogue', 'Width' => '100%', 'Height' => '180');

// Option selection message
$form->addElement ('html','<div class="HideFCKEditor" id="HiddenFCKexerciseDescription" >');
$form->add_html_editor('opt_selection_text', get_lang('OptionsSelectionText'), false, false, $editor_config);
$form->addElement ('html','</div>');

// Payment Messages
$form->addElement('header', '', get_lang('PaymentMethodsMessage'));
$form->addElement ('html','<div class="HideFCKEditor" id="HiddenFCKexerciseDescription" >');
$form->add_html_editor('cc_payment_message', get_lang('CreditCartPaymentMessage'), false, false, $editor_config);
$form->addElement ('html','</div>');

// Installment Payment
//$form->addElement ('html','<div class="HideFCKEditor" id="HiddenFCKexerciseDescription" >');
//$form->add_html_editor('install_payment_message', get_lang('InstallmentPaymentMessage'), false, false, $editor_config);
//$form->addElement ('html','</div>');

// Cheque Payment
$form->addElement ('html','<div class="HideFCKEditor" id="HiddenFCKexerciseDescription" >');
$form->add_html_editor('cheque_message', get_lang('ChequeMessage'), false, false, $editor_config);
$form->addElement ('html','</div>');

/*
$form->addElement('static','',get_lang('Email'),get_lang('EmailMessage'));
$radios_email[] = FormValidator :: createElement('radio', null, null, get_lang('Yes'), '1');
$radios_email[] = FormValidator :: createElement('radio', null, null, get_lang('No'), '0');
$form->addGroup($radios_email, 'email', '');
 */

$form->addElement('header', '', get_lang('InformationPages'));
$editor_config = array('ToolbarSet' => 'Catalogue', 'Width' => '100%', 'Height' => '180');

$form->addElement ('html','<div class="HideFCKEditor" id="HiddenFCKexerciseDescription" >');
$form->add_html_editor('termsconditions', get_lang('TermsAndConditions'), false, false, $editor_config);
$form->addElement ('html','</div>');

$form->addElement ('html','<div class="HideFCKEditor" id="HiddenFCKexerciseDescription" >');
$form->add_html_editor('tvadescription', get_lang('TvaDescription'), false, false, $editor_config);
$form->addElement ('html','</div>');

$form->addElement('style_submit_button', 'submit', get_lang('Ok'), 'class="save"');

if($_REQUEST['action'] == 'edit'){
$defaults['title'] = $row['title'];
$defaults['economic_model'] = $row['economic_model'];
$defaults['visible'] = $row['visible'];
/*$defaults['atos_acno'] = $row['atos_account_number'];
$defaults['paypal_account_ref'] = $row['paypal_account_ref'];
$defaults['option_selection'] = $row['options_selection'];
$defaults['payment_message'] = $row['payment_message'];
$defaults['cc_payment_message'] = $row['cc_payment_message'];
$defaults['installment_payment_message'] = $row['installment_payment_message'];
$defaults['cheque_payment_message'] = $row['cheque_payment_message'];*/
$defaults['termsconditions'] = $row['terms_conditions'];
$defaults['tvadescription'] = $row['tva_description'];

$defaults['company_address'] = $row['company_address'];
$defaults['bank_details'] = $row['bank_details'];

$defaults['opt_selection_text'] = $row['options_selection'];
$defaults['cc_payment_message'] = $row['cc_payment_message'];
$defaults['cheque_message'] = $row['cheque_message'];
//$defaults['install_payment_message'] = $row['installment_payment_message'];
//$defaults['email'] = $row['email'];
}
else {
$defaults['economic_model'] = 1;
$defaults['visible'] = 1;
//$defaults['email'] = 0;
}
$form->setDefaults($defaults);
$updir = api_get_path(SYS_PATH). 'home/default_platform_document/'; //directory path to upload
// Validate form
if( $form->validate()) {

	$catalogue = $form->exportValues();

        
	$catalogue_display = $_POST['catalogue_display'];
	$payment_methods = $_POST['payment_methods'];
	$catalogues = array();
	$payments = array();
	for ($i=0; $i<count($catalogue_display); $i++) {
      //  echo( ($i+1) . ") " . $catalogue_display[$i] . "<br>");
	  $catalogues[] = $catalogue_display[$i];
    }
	
	for ($i=0; $i<count($payment_methods); $i++) {
     //   echo( ($i+1) . ") " . $payment_methods[$i] . "<br>");
	 $payments[] = $payment_methods[$i];
    }
	
	if(!empty($_FILES['file']['tmp_name'])){
	$company_logo = replace_dangerous_char($_FILES['file']['name'], 'strict');
	
	@move_uploaded_file($_FILES['file']['tmp_name'], $updir . $company_logo);
	}
	
	if($_REQUEST['action'] != 'edit'){
	$sql = "INSERT INTO " . $catalogue_table . " SET " .
										   "title	= '".Database::escape_string($catalogue['title'])."',
										   economic_model		= '".Database::escape_string($catalogue['economic_model'])."',
										   visible				= '".Database::escape_string($catalogue['visible'])."',
										   catalogue_display 	= '".implode(', ',$catalogues)."',
										   payment 				= '".implode(',',$payments)."',										   										  
										   company_logo 	= '".$company_logo."',										   
										   company_address	= '".Database::escape_string($catalogue['company_address'])."',	
										   cheque_message	= '".Database::escape_string($catalogue['cheque_message'])."',	
                                                                                   cc_payment_message	= '".Database::escape_string($catalogue['cc_payment_message'])."',	
                                                                                   installment_payment_message	= '".Database::escape_string($catalogue['install_payment_message'])."',	
                                                                                   options_selection	= '".Database::escape_string($catalogue['opt_selection_text'])."',	                                                                                       
                                                                                   terms_conditions	= '".Database::escape_string($catalogue['termsconditions'])."',
                                                                                   tva_description	= '".Database::escape_string($catalogue['tvadescription'])."',    
										   bank_details	= '".Database::escape_string($catalogue['bank_details'])."'";

					Database::query($sql, __FILE__, __LINE__);	                                                                                

					echo '<script>window.location.href = "'.api_get_self().'"</script>';
                                        
	}
	else {
	$sql = "UPDATE " . $catalogue_table . " SET " .
										   "title				= '".Database::escape_string($catalogue['title'])."',
										   economic_model		= '".Database::escape_string($catalogue['economic_model'])."',
										   visible				= '".Database::escape_string($catalogue['visible'])."',
										   catalogue_display 	= '".implode(', ',$catalogues)."',
										   payment 				= '".implode(',',$payments)."',										   										   
										   cheque_message	= '".Database::escape_string($catalogue['cheque_message'])."',	
                                                                                   cc_payment_message	= '".Database::escape_string($catalogue['cc_payment_message'])."',	
                                                                                   installment_payment_message	= '".Database::escape_string($catalogue['install_payment_message'])."',	
                                                                                   options_selection	= '".Database::escape_string($catalogue['opt_selection_text'])."',	                                                                                       
                                                                                   terms_conditions	= '".Database::escape_string($catalogue['termsconditions'])."',
                                                                                   tva_description	= '".Database::escape_string($catalogue['tvadescription'])."',";
										    if(!empty($_FILES['file']['tmp_name'])){
                                                                                        $sql .= " company_logo 	= '".$company_logo."',";
										   }
                                                                                        $sql .= " company_address	= '".Database::escape_string($catalogue['company_address'])."',										   
										   bank_details	= '".Database::escape_string($catalogue['bank_details'])."' WHERE id = ".$row['id'];
                                                       
            Database::query($sql, __FILE__, __LINE__);
					echo '<script>window.location.href = "'.api_get_self().'"</script>';
	}

}

$form->display();

//} //End of else statement
echo '</div>';
echo '</div>';

// display the footer
Display::display_footer();
?>
