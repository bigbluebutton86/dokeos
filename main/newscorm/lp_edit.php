<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * Learning Path
 * Script allowing simple edition of learnpath information (title, description, etc)
 * @package dokeos.learnpath
 * @author Yannick Warnier
 */

// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

// we set the encoding of the lp
if (!empty($_SESSION['oLP']->encoding)) {
	$charset = $_SESSION['oLP']->encoding;
    // Check if we have a valid api encoding
    $valid_encodings = api_get_valid_encodings();
    $has_valid_encoding = false;
    foreach ($valid_encodings as $valid_encoding) {
      if (strcasecmp($charset,$valid_encoding) == 0) {
        $has_valid_encoding = true;
      }
    }
    // If the scorm packages has not a valid charset, i.e : UTF-16 we are displaying
    if ($has_valid_encoding === false) {
      $charset = api_get_system_encoding();
    }
} else {
	$charset = api_get_system_encoding();
}
if (empty($charset)) {
	$charset = 'ISO-8859-1';
}

$show_description_field = false; //for now
$nameTools = get_lang("Doc");
event_access_tool(TOOL_LEARNPATH);
if (! $is_allowed_in_course) api_not_allowed();

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}
$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>api_get_self()."?action=admin_view&lp_id=$learnpath_id", "name" => $_SESSION['oLP']->get_name());

Display::display_tool_header(null,'Path');

$author_lang_var = api_convert_encoding(get_lang('Author'), $charset, api_get_system_encoding());
$content_lang_var = api_convert_encoding(get_lang('Content'), $charset, api_get_system_encoding());
$scenario_lang_var = api_convert_encoding(get_lang('Scenario'), $charset, api_get_system_encoding());
$publication_lang_var = api_convert_encoding(get_lang('Publication'), $charset, api_get_system_encoding());
$export_lang_var = api_convert_encoding(get_lang('Export'), $charset, api_get_system_encoding());

// actions link
echo '<div class="actions">';
$gradebook = Security::remove_XSS($_GET['gradebook']);
echo '<a href="' . api_get_self() . '?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '">' . Display::return_icon('pixel.gif', $author_lang_var, array('class' => 'toolactionplaceholdericon toolactionback')).$author_lang_var . '</a>';
echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display::return_icon('pixel.gif', $content_lang_var, array('class' => 'toolactionplaceholdericon toolactionauthorcontent')).$content_lang_var . '</a>';
echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&gradebook=&action=admin_view&lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display::return_icon('pixel.gif', $scenario_lang_var, array('class' => 'toolactionplaceholdericon toolactionauthorscenario')).$scenario_lang_var . '</a>';
echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&gradebook=&action=edit&lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display::return_icon('pixel.gif', $publication_lang_var, array('class' => 'toolactionplaceholdericon toolsettings')).$publication_lang_var . '</a>';
/*   Export  */
if($_SESSION['oLP']->get_type()==1){
    $dsp_disk =
        "<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=".$_SESSION['oLP']->lp_id ."'>" .Display::return_icon('pixel.gif', $export_lang_var, array('class' => 'toolactionplaceholdericon toolactionauthorexport')).$export_lang_var.
        "</a>";
}elseif($_SESSION['oLP']->get_type()==2){
    $dsp_disk =
        "<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=".$_SESSION['oLP']->lp_id ."&export_name=".replace_dangerous_char($_SESSION['oLP']->get_name(),'strict').".zip'>" .Display::return_icon('pixel.gif', $export_lang_var, array('class' => 'toolactionplaceholdericon toolactionauthorexport')).$export_lang_var.
        "</a>";
}
echo $dsp_disk;
echo '</div>';

$defaults=array();
$form = new FormValidator('form1', 'post', 'lp_controller.php?'.api_get_cidreq());

$editlpsettings_lang_var = api_convert_encoding(get_lang('EditLPSettings'), $charset, api_get_system_encoding());

// form title
$form->addElement('header',null, $editlpsettings_lang_var);

//Title
$title_lang_var = api_convert_encoding(get_lang('_title'), $charset, api_get_system_encoding());
$form->addElement('text', 'lp_name', api_ucfirst($title_lang_var),array('size'=>43));
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');

//Metadata
//$clean_scorm_id=Security::remove_XSS($_GET['lp_id']);
//$metadata_link = '<a href="../metadata/index.php?eid='.urlencode('Scorm.'.$clean_scorm_id).'">'.get_lang('AddMetadata').'</a>';
//$form->addElement('static',null,get_lang('Metadata'),$metadata_link);

//Encoding
$charset_lang_var = api_convert_encoding(get_lang('Charset'), $charset, api_get_system_encoding());
$encoding_select = &$form->addElement('select', 'lp_encoding', $charset_lang_var);
$encodings = array('UTF-8','ISO-8859-1','ISO-8859-15','cp1251','cp1252','KOI8-R','BIG5','GB2312','Shift_JIS','EUC-JP');
foreach($encodings as $encoding){
	if (api_equal_encodings($encoding, $_SESSION['oLP']->encoding)) {
  		$s_selected_encoding = $encoding;
  	}
  	$encoding_select->addOption($encoding,$encoding);
}


//Origin
$origin_lang_var = api_convert_encoding(get_lang('Origin'), $charset, api_get_system_encoding());
$origin_select = &$form->addElement('select', 'lp_maker', $origin_lang_var);
$lp_orig = $_SESSION['oLP']->get_maker();

include('content_makers.inc.php');
foreach($content_origins as $origin){
	if($lp_orig == $origin){
		$s_selected_origin = $origin;
	}
	$origin = api_convert_encoding($origin, $charset, api_get_system_encoding());
	$origin_select->addOption($origin,$origin);
}


//Content proximity
$contprox_lang_var = api_convert_encoding(get_lang('ContentProximity'), $charset, api_get_system_encoding());
$local_lang_var = api_convert_encoding(get_lang('Local'), $charset, api_get_system_encoding());
$remote_lang_var = api_convert_encoding(get_lang('Remote'), $charset, api_get_system_encoding());
$content_proximity_select = &$form->addElement('select', 'lp_proximity', $contprox_lang_var);
$lp_prox = $_SESSION['oLP']->get_proximity();
if($lp_prox != 'local'){
	$s_selected_proximity = 'remote';
}else{
	$s_selected_proximity = 'local';
}
$content_proximity_select->addOption($local_lang_var, 'local');
$content_proximity_select->addOption($remote_lang_var, 'remote');


if (api_get_setting('allow_course_theme') == 'true')
{
	$mycourselptheme=api_get_course_setting('allow_learning_path_theme');
	$theme_lang_var = api_convert_encoding(get_lang('Theme'), $charset, api_get_system_encoding());
	if (!empty($mycourselptheme) && $mycourselptheme!=-1 && $mycourselptheme== 1)
	{
		//LP theme picker
		$theme_select = &$form->addElement('select_theme', 'lp_theme', $theme_lang_var);
		$form->applyFilter('lp_theme', 'trim');

		$s_theme = $_SESSION['oLP']->get_theme();
		$theme_select ->setSelected($s_theme); //default
	}
}

// Course interface
$dfcourseint_lang_var = api_convert_encoding(get_lang('LpSelectDefaultCourseInterface'), $charset, api_get_system_encoding());
$lpdfmode_lang_var = api_convert_encoding(get_lang('LpDefaultMode'), $charset, api_get_system_encoding());
$lpoldmode_lang_var = api_convert_encoding(get_lang('LpOldInterfaceMode'), $charset, api_get_system_encoding());
$lpnewmode_lang_var = api_convert_encoding(get_lang('LpNewInterfaceMode'), $charset, api_get_system_encoding());
$course_interface_select = &$form->addElement('select', 'lp_interface', $dfcourseint_lang_var);
$lp_interfaz = $_SESSION['oLP']->get_course_interface();
if($lp_interfaz == 0){
	$s_selected_lp_interfaz = '0';
}elseif($lp_interfaz == 2) {
	$s_selected_lp_interfaz = '2';
}else{
	$s_selected_lp_interfaz = '1';
}
$course_interface_select->addOption($lpdfmode_lang_var, '0');
$course_interface_select->addOption($lpoldmode_lang_var, '1');
$course_interface_select->addOption($lpnewmode_lang_var, '2');
//Author
//$form->addElement('html_editor', 'lp_author', get_lang('Author'), array('size'=>80), array('ToolbarSet' => 'LearningPathAuthor', 'Width' => '100%', 'Height' => '150px') );
$author_lang_var = api_convert_encoding(get_lang('Author'), $charset, api_get_system_encoding());
$form->addElement('text', 'lp_author', $author_lang_var, '');
$form->applyFilter('lp_author', 'html_filter');

// LP image
$form->add_progress_bar();
if( strlen($_SESSION['oLP']->get_preview_image() ) > 0)
{
	$imagepreview_lang_var = api_convert_encoding(get_lang('ImagePreview'), $charset, api_get_system_encoding());
	$delimage_lang_var = api_convert_encoding(get_lang('DelImage'), $charset, api_get_system_encoding());

	$show_preview_image='<img src='.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image().'>';
	$div = '<div class="row">
	<div class="label">'.$imagepreview_lang_var.'</div>
	<div class="formw">
	'.$show_preview_image.'
	</div>
	</div>';
	$form->addElement('html', $div .'<br/>');
	$form->addElement('checkbox', 'remove_picture', null, $delimage_lang_var);
}
$updateimage_lang_var = api_convert_encoding(get_lang('UpdateImage'), $charset, api_get_system_encoding());
$addimage_lang_var = api_convert_encoding(get_lang('AddImage'), $charset, api_get_system_encoding());

$form->addElement('file', 'lp_preview_image', ($_SESSION['oLP']->get_preview_image() != '' ? $updateimage_lang_var : $addimage_lang_var));

$imgresize_lang_var = api_convert_encoding(get_lang('ImageWillResizeMsg'), $charset, api_get_system_encoding());
$form->addElement('static', null, null, $imgresize_lang_var);


if($_SESSION['oLP']->type == '3')
{ // then an update of source exists
	$updateaicc_lang_var = api_convert_encoding(get_lang('UpdateAiccSource'), $charset, api_get_system_encoding());
	$form->addElement('file', 'lp_update_source', $updateaicc_lang_var);
}

/*
$form->addRule('lp_preview_image', get_lang('OnlyImagesAllowed'), 'mimetype', array('image/gif', 'image/jpeg', 'image/png'));
*/
$onlyimages_lang_var = api_convert_encoding(get_lang('OnlyImagesAllowed'), $charset, api_get_system_encoding());
$form->addRule('lp_preview_image', $onlyimages_lang_var, 'filetype', array ('jpg', 'jpeg', 'png', 'gif'));

// Search terms (only if search is activated)
if (api_get_setting('search_enabled') === 'true' && extension_loaded('xapian'))
{
	$specific_fields = get_specific_field_list();
	foreach ($specific_fields as $specific_field) {
		$form -> addElement ('text', $specific_field['code'], $specific_field['name']);
		$filter = array('course_code'=> "'". api_get_course_id() ."'", 'field_id' => $specific_field['id'], 'ref_id' => $_SESSION['oLP']->lp_id, 'tool_id' => '\''. TOOL_LEARNPATH .'\'');
		$values = get_specific_field_values_list($filter, array('value'));
		if ( !empty($values) ) {
			$arr_str_values = array();
			foreach ($values as $value) {
				$arr_str_values[] = $value['value'];
			}
			$defaults[$specific_field['code']] = implode(', ', $arr_str_values);
		}
	}
}

$showdebug_lang_var = api_convert_encoding(get_lang('ShowDebug'), $charset, api_get_system_encoding());
$hidedebug_lang_var = api_convert_encoding(get_lang('HideDebug'), $charset, api_get_system_encoding());
$selectDebug = &$form->addElement('select', 'enable_debug', $showdebug_lang_var);
$selectDebug->addOption($showdebug_lang_var, 0);
$selectDebug->addOption($hidedebug_lang_var, 1);

//default values
$content_proximity_select -> setSelected($s_selected_proximity);
$origin_select -> setSelected($s_selected_origin);
$course_interface_select->  setSelected($s_selected_lp_interfaz);
$encoding_select -> setSelected($s_selected_encoding);
$defaults['lp_name'] = Security::remove_XSS(api_convert_encoding($_SESSION['oLP']->get_name(), $charset, api_get_system_encoding()));
$defaults['lp_author'] = Security::remove_XSS(api_convert_encoding($_SESSION['oLP']->get_author(), $charset, api_get_system_encoding()));
$defaults['enable_debug'] = $_SESSION['oLP']->scorm_debug;

//get the keyword
$searchkey = new SearchEngineManager();
$keyword = $searchkey->getKeyWord(TOOL_LEARNPATH, $_SESSION['oLP']->lp_id);

$defaults['search_terms'] = $keyword;
if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
    //TODO: include language file
    $form -> addElement('html','<input type="hidden" name="index_document" value="1"/>'.
     '<input type="hidden" name="language" value="' . api_get_setting('platformLanguage') . '"/>');
    $form-> addElement('textarea','search_terms',get_lang('SearchKeywords'),array('cols'=>65));
}

//Submit button
$savelp_lang_var = api_convert_encoding(get_lang('SaveLPSettings'), $charset, api_get_system_encoding());
$form->addElement('style_submit_button', 'Submit',$savelp_lang_var,'class="save"');
//'<img src="'.api_get_path(WEB_IMG_PATH).'accept.png'.'" alt=""/>'

//Hidden fields
$form->addElement('hidden', 'action', 'update_lp');
$form->addElement('hidden', 'lp_id', $_SESSION['oLP']->get_id());



$form->setDefaults($defaults);
echo '<div id="content_with_secondary_actions">';
echo '<table style="width:100%"><tr><td style="width:70%" valign="top">';
	$form -> display();
echo '</td><td style="width:30%" valign="top">
<table style="text-align: left;padding-left:20px; width: 100%;" border="0" cellpadding="2" cellspacing="2">
  <tbody>
    <tr>
      <td style="vertical-align: top;">'.$lpnewmode_lang_var.'</td>
    </tr>
    <tr>
      <td style="vertical-align: top;">'.Display::return_icon('full_screen.png',$lpnewmode_lang_var).'</td>
    </tr>
    <tr>
      <td style="vertical-align: top;">'.$lpdfmode_lang_var.'</td>
    </tr>
    <tr>
      <td style="vertical-align: top;">'.Display::return_icon('dokeos_navigation.png',$lpdfmode_lang_var).'<br>
    </td>
    </tr>
    <tr>
      <td style="vertical-align: top;">'.$lpoldmode_lang_var.'</td>
    </tr>
    <tr>
      <td style="vertical-align: top;">'.Display::return_icon('table_of_contents.png',$lpoldmode_lang_var).'<br>
    </td>
    </tr>
  </tbody>
</table>
</td></tr></table>';
echo '</div>';
// Actions bar
echo '<div class="actions">';
echo '</div>';
Display::display_footer();
?>
