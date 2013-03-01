<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * Upload
 * Display part of the document sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 * @package dokeos.upload
 * @author Yannick Warnier
 */


		// Language files that should be included
		//$language_file[] = 'languagefile1';		// if uncomment Fatal error: [] operator not supported for strings
		//$language_file[] = 'languagefile2';
		
		// setting the help
		$help_content = 'codetemplate';
		
		// including the global Dokeos file
		require_once '../inc/global.inc.php';
		
		// including additional libraries
		include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
		include('../newscorm/content_makers.inc.php');
		require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

//show the title
//api_display_tool_title(get_lang("Learnpath")." - ".$nameTools.$add_group_to_title);

// setting the tabs
$this_section=SECTION_COURSES;

// to prevent Warning and Fatal error: require_once() [function.require]: Failed opening required xapian.php
// TODO : fix this error to enable search engine xapian
$search_enabled = false;
//		$search_enabled = api_get_setting('search_enabled')=='true';

// toggle other criteria in form 
if($search_enabled){
	$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/jquery-1.4.2.min.js" type="text/javascript"></script>';
	$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">function toggle_criteria(){ $(\'#more_criteria\').toggle(); }</script>';
}

// setting the breadcrumbs
$interbreadcrumb[] = array ("url"=>"overview.php", "name"=> get_lang('OverviewOfAllCodeTemplates'));
$interbreadcrumb[] = array ("url"=>"coursetool.php", "name"=> get_lang('CourseTool'));

/**
 * display the form needed to upload a SCORM and give its settings
 */
$nameTools = get_lang("FileUpload");
$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang("Learnpath"));
//Display::display_header($nameTools,"Path");
Display::display_tool_header(null,'Path');

/**
 * Small function to list files in archive/
 */
function get_zip_files_in_garbage(){
	$list = array();
	$dh = opendir(api_get_path(SYS_ARCHIVE_PATH));
	if($dh === false){
		//ignore
	}else{
		while($entry = readdir($dh)){
			if(substr($entry,0,1) == '.')						{/*ignore files starting with . */}
			elseif(preg_match('/^.*\.zip$/i',$entry))			$list[] = $entry;
		}
		natcasesort($list);
		closedir($dh);
	}
	return $list;
}


// Actions bar
echo	'<div class="actions">';
//	echo		'<a href="../newscorm/lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('scorm.gif',get_lang('ReturnToLearningPaths')).' '.get_lang('ReturnToLearningPaths').'</a>';
echo	'<a href="../newscorm/lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('pixel.gif', get_lang('ReturnToLearningPaths'), array('class' => 'toolactionplaceholdericon toolactionback')).' '.get_lang('ReturnToLearningPaths').'</a>';
/*if($search_enabled) {
	echo	'<a href="#" onclick="javascript:toggle_criteria();">'.Display::render_author_action("more_criteria").'</a>';
}*/
echo	'</div>';


/*
==============================================================================
  BUILD FORM
==============================================================================
*/

  $upload_message = get_lang('FileToUpload');
  if (isset($_SESSION['is_in_serious_game']) && $_SESSION['is_in_serious_game'] == 1) {
    $upload_message = get_lang('ZipFileToUpload');
    $nameTools = get_lang('LpGameUpload');
  }

$form = new FormValidator('','POST','upload.php','','id="upload_form" enctype="multipart/form-data" class="orange"');
//		$form->addElement('header', '', $nameTools);
$form->addElement('html', '<h3 class="title">'.$nameTools.'</h3>');
$form->addElement('hidden', 'curdirpath', $path);
$form->addElement('hidden', 'tool', $my_tool);
$form->addElement('file','user_file',$upload_message.'<br />');
$form->addElement ('html','<div id="more_criteria" style="display:none;">');

$select_content_marker = &$form->addElement('select','content_maker',get_lang('ContentMaker').'<br />');
	foreach($content_origins as $index => $origin)
		$select_content_marker->addOption($origin,$origin);

$select_content_proximity = &$form->addElement('select','content_proximity',get_lang('ContentProximity'));
	$select_content_proximity->addOption(get_lang('Local'),"local");
	$select_content_proximity->addOption(get_lang('Remote'),"remote");
	$select_content_proximity -> setSelected("local");

if($search_enabled)
{
	$form -> addElement ('checkbox', 'index_document','', get_lang('SearchFeatureDoIndexDocument'));
	$specific_fields = get_specific_field_list();
	foreach ($specific_fields as $specific_field)
		$form -> addElement ('text', $specific_field['code'], $specific_field['name'].' : ');
}

$form -> addElement ('html','</div>');
if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
    //TODO: include language file
    $form -> addElement('html','<input type="hidden" name="index_document" value="1"/>'.
     '<input type="hidden" name="language" value="' . api_get_setting('platformLanguage') . '"/>');
    $form-> addElement('textarea','search_terms',get_lang('SearchKeywords').':',array('cols'=>'42','rows' => '2'));
}
//$form -> addElement ('submit', 'convert', get_lang('Send'), array('style'=>""));
$form -> addElement ('style_submit_button', 'convert', get_lang('Send'), array('style'=>"",'class'=>'save'));

/*$list = get_zip_files_in_garbage();
if(count($list)>0){
	$select_file_name = &$form->addElement('select','file_name',get_lang('Or').' '.api_strtolower(get_lang('UploadLocalFileFromGarbageDir')));
	foreach($list as $file){
		$select_file_name->addOption($file,$file);
	}
	$form->addElement('submit', 'submit', get_lang('Download'));
}
else{
	$text_empty = &$form->addElement('text', 'empty', get_lang('Or').' '.api_strtolower(get_lang('UploadLocalFileFromGarbageDir')));
	$defaults["empty"] = get_lang('Empty');
	$text_empty->freeze();
}*/

$form->add_real_progress_bar('uploadScorm','user_file');

// the rules for the form
$form->addRule('user_file', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
// the default values for the form
$defaults = array('index_document'=>'checked="checked"');
$form->setDefaults($defaults);


/*
==============================================================================
  rendering div#content
==============================================================================
*/

echo '<div id="content" class="rel">';
//			echo '<h3 class="orange" style="margin-left:40px;">'.get_lang('download word template').'</h3>';
  if (isset($_SESSION['is_in_serious_game']) && $_SESSION['is_in_serious_game'] == 0) {
			echo Display::return_icon("navigation/upload_scorm_man.png", '', array(	'class'	=> "abs", 'style'	=> "margin:50px 0 0 50px; top:0; left:0;"));
  } else {
   echo Display::return_icon("navigation/indiana_man.png", '', array(	'class'	=> "abs", 'style'	=> "margin:50px 0 0 50px; top:0; left:0;"));
  }
//			echo Display::return_icon("navigation/big_word.png", '', array('style'=>"margin:20px 0 0 40px;"));
			echo '<div class="abs" style="margin:0 50px 50px 0; right:0; top:40%; ">';
				$form -> display();
			echo '</div>';
		echo '</div>';


// bottom actions bar
echo '<div class="actions">';
echo '</div>';

// Display the footer
Display::display_footer();
?>
