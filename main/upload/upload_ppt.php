<?php

/* For licensing terms, see /dokeos_license.txt */

/**
 * Upload
 * Action controller for the upload process. The display scripts (web forms) redirect
 * the process here to do what needs to be done with each file.
 * as it prepares most of the variables needed here.
 * @package dokeos.upload
 * @author Yannick Warnier
 */
// Language files that should be included
$language_file[] = "document";
$language_file[] = "learnpath";
$language_file[] = "scormdocument";

// setting the help
$help_content = 'codetemplate';

// including the global Dokeos file
require_once '../inc/global.inc.php';
include_once(api_get_path(LIBRARY_PATH) . 'searchengine.lib.php');
require_once('../newscorm/lp_upload.php');
require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
// including additional libraries
include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php');

// setting the tabs
$this_section = SECTION_COURSES;


// setting the breadcrumbs
//$interbreadcrumb[] = array ("url"=>"overview.php", "name"=> get_lang('OverviewOfAllCodeTemplates'));
//$interbreadcrumb[] = array ("url"=>"coursetool.php", "name"=> get_lang('CourseTool'));

$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/upload.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
						var myUpload = new upload(0);
					</script>';




$search_enabled = (api_get_setting('search_enabled') == 'true');

// toggle other criteria in form 
if ($search_enabled) {
    //$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/jquery.js" type="text/javascript"></script>';
    //$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">function toggle_criteria(){ $(\'#more_criteria\').toggle(); }</script>';
}


if (isset($_POST['convert'])) {
    $cwdir = getcwd();
    if (isset($_FILES['user_file'])) {
        $allowed_extensions = array('odp', 'sxi', 'ppt', 'pps', 'sxd', 'pptx');
        if (in_array(strtolower(pathinfo($_FILES['user_file']['name'], PATHINFO_EXTENSION)), $allowed_extensions)) {
            if (isset($o_ppt) && $first_item_id != 0) {
                if ($search_enabled) {
                    $specific_fields = get_specific_field_list();
                    foreach ($specific_fields as $specific_field) {
                        $values = explode(',', trim($_POST[$specific_field['code']]));
                        if (!empty($values)) {
                            foreach ($values as $value) {
                                $value = trim($value);
                                if (!empty($value))
                                    add_specific_field_value($specific_field['id'], api_get_course_id(), TOOL_LEARNPATH, $o_ppt->lp_id, $value);
                            }
                        }
                    }
                }
                header('Location: ../newscorm/lp_controller.php?' . api_get_cidreq() . '&lp_id=' . $o_ppt->lp_id . '&action=view&id=' . $first_item_id);
            } else {
                if (!empty($o_ppt->error))
                    $errorMessage = $o_ppt->error;
                else
                    $errorMessage = get_lang('OogieUnknownError');
            }
        }
        else
            $errorMessage = get_lang('OogieBadExtension');
    }
}

event_access_tool(TOOL_UPLOAD);


// check access permissions (edit permission is needed to add a document or a LP)
if (!api_is_allowed_to_edit())
    api_not_allowed(true);

$interbreadcrumb[] = array("url" => "../newscorm/lp_controller.php?action=list", "name" => get_lang("Doc"));

//$nameTools = get_lang("OogieConversionPowerPoint");
//Display :: display_header($nameTools);

Display::display_tool_header(null, 'Path');


// Actions
echo '<div class="actions">';
echo '<a href="../newscorm/lp_controller.php?cidReq=' . $_course['sysCode'] . '">' . Display::return_icon('pixel.gif',get_lang('ReturnToLearningPaths'),array('class' => 'toolactionplaceholdericon toolactionback')) .' '.get_lang('ReturnToLearningPaths') .'</a>';
//if ($search_enabled)
//echo	'<a href="#" onclick="javascript:toggle_criteria();">'.Display::render_author_action("more_criteria").'</a>';
    echo '</div>';



$message = get_lang("WelcomeOogieConverter");
//Display::display_normal_message($message);

if (!empty($errorMessage)) {
    //Display::display_error_message($errorMessage);
    if (api_get_setting('display_feedback_messages') == 'true') {
        $s_style_error = "border-width: 1px; border-style: solid; margin-left: 0; margin-top: 10px; margin-bottom: 10px; min-height: 30px; padding: 5px; position: relative; width: 500px; background-color: #FFD1D1; border-color: #FF0000; color: #000;";
        echo '<div style="' . $s_style_error . '"><div style="float:left; margin-right:10px;"><img src="' . api_get_path(WEB_IMG_PATH) . "message_error.gif" . '" alt="' . $alt_text . '" ' . $attribute_list . '  /></div><div style="margin-left: 43px">' . $errorMessage . '</div></div>';
    } else {
        echo $errorMessage;
    }
}


// Build the form
$form = new FormValidator('upload_ppt', 'POST', '', '', array('class' => "orange"));

$div_upload_limit = '<br/>' . get_lang('UploadMaxSize') . ' : ' . ini_get('post_max_size');
$renderer = & $form->defaultRenderer();



// set template for user_file element
$user_file_template =
        <<<EOT
		<!-- BEGIN required -->* <!-- END required -->{label}{element}$div_upload_limit
		<!-- BEGIN error --><br />{error}<!-- END error -->
EOT;
$renderer->setElementTemplate($user_file_template, 'user_file');

// set template for other elements
$user_file_template =
        <<<EOT
		<!-- BEGIN required -->* <!-- END required -->{label}{element}
		<!-- BEGIN error --><br />{error}<!-- END error -->
EOT;
$renderer->setElementTemplate($user_file_template);



$form->addElement('html', '<h3 class="title">' . get_lang('UploadFile') . '</h3>');
$form->addElement('file', 'user_file', '', 'class="input_browse"');
//$form -> addElement ('checkbox', 'take_slide_name','', get_lang('TakeSlideName'));

if ($search_enabled) {
    $specific_fields = get_specific_field_list();
    $form->addElement('html', '<div id="more_criteria" >');
    $form->addElement('hidden', 'index_document', 1);
    $form->addElement('textarea', 'terms', '<br/>' . get_lang('SearchKeywords') . ': ', array('rows' => '2', 'cols' => '40'));
    $form->addElement('hidden', 'language', api_get_setting('platformLanguage'));
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name'] . ' : ');
    }
    $form->addElement('html', '</div>');
}
$form->addElement('hidden', 'ppt2lp', 'true');
$form->add_real_progress_bar(md5(rand(0, 10000)), 'user_file', 1, true);
//$defaults = array('take_slide_name'=>'checked="checked"','index_document'=>'checked="checked"');
$defaults = array('take_slide_name' => 'checked="checked"');
$form->setDefaults($defaults);
//$form -> addElement ('submit', 'convert', get_lang('ConvertToLP'), array('style'=>"margin-top:30px;"));
$form->addElement('style_submit_button', 'convert', get_lang('ConvertToLP'), array('style' => "margin-top:30px;", 'class' => 'save'));

/*
  ==============================================================================
  rendering div#content
  ==============================================================================
 */

echo '<div id="content" class="rel">';
echo '<a href="DokeosScenarioEn.ppt"><h3 class="orange" style="margin-left:40px;">' . get_lang('DownloadPowerpointTemplate') . '</h3></a>';
echo Display::return_icon("navigation/upload_ppt_man.png", '', array('class' => "abs", 'style' => "margin-right:30px; top:20%; right:400px;"));
echo Display::return_icon('pixel.gif', $content_lang_var, array('class' => 'toolscenarioactionplaceholdericon ppt_button','style' => 'margin:20px 0 0 40px;'));
echo '<div class="abs" style="margin:0 50px 50px 0; right:0; top:40%; ">';
$form->display();
echo '</div>';
echo '</div>';


// bottom actions bar
echo '<div class="actions">';
echo '</div>';

// display the footer
Display::display_footer();
?>