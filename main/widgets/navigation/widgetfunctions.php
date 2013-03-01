<?php 
// including the widgets language file
$language_file = array ('widgets');

// include the global Dokeos file
include_once('../../inc/global.inc.php');

// load the specific widget settings
api_load_widget_settings();

//action handling
switch ($_POST['action']) {
	case 'get_widget_information':
		navigation_get_information();
		break;
	case 'get_widget_content':
		navigation_get_content();
		break;
	case 'addnavigation':
		navigation_add_navigation($_POST['navigation_id']);
		break;	
	case 'saveaddnavigation':
		navigation_save_add_navigation();
		break;
	case 'install':
		navigation_install();
		break;	
	case 'managenavigation':
		navigation_manage_items();
		break;
	case 'navigationsaveorder':
		navigation_save_order();
		break;
	case 'navigationdeleteitem':
		navigation_delete_item($_POST['id']);
		break;
}
switch ($_GET['action']) {
	case 'get_widget_information':
		navigation_get_information();
		break;
	case 'get_widget_content':
		navigation_get_content();
		break;
	case 'get_widget_title':
		navigation_get_title();
		break;
	case 'addnavigation':
		navigation_add_navigation($_GET['navigation_id']);
		break;
	case 'saveaddnavigation':
		navigation_save_add_navigation();
		break;
	case 'install':
		navigation_install();
		break;	
	case 'managenavigation':
		navigation_manage_items();
		break;
	case 'navigationsaveorder':
		navigation_save_order();
		break;	
	case 'navigationdeleteitem':
		navigation_delete_item($_GET['id']);
		break;	
}

/**
 * This function determines if the widget can be used inside a course, outside a course or both
 * 
 * @return array 
 * @version Dokeos 1.9
 * @since January 2010
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 */
function navigation_get_scope(){
	return array('course', 'platform');
}


/**
 * Enter description here...
 *
 * @param string $extraid this string will be added to the id's of the different tags because the function is also used for editing or moving the items
 */
function navigation_get_content($extra_id){
	global $_course;

	// Database table definition
	if (!empty($_course) AND is_array($_course)){
		$table_navigation = Database :: get_course_table('navigation');
	} else {
		$table_navigation = Database :: get_main_table('navigation');
	}

	// the query to select navigation items
	$sql = "SELECT * FROM $table_navigation ORDER BY display_order ASC";
	$result = Database::query($sql, __FILE__, __LINE__);
	
	// adding the style of the navigation tools (the actual style is determined by the id of the div which comes from api_get_setting('navigation','style')
	echo '<style type="text/css" media="screen, projection">
			/*<![CDATA[*/
			@import "'.api_get_path(WEB_PATH).'main/widgets/navigation/navigation.css";
			/*]]>*/
			</style>';
	
	// display the menu horizontally or not?
	if (api_get_setting('navigation','displayhorvert') == 'horizontal'){
		echo '
			<style>
			#widget_navigation li {
				float: left;
				display:block;
				}
			</style>';
	}
	
	// the menu
	echo '<div id="'.api_get_setting('navigation','style').$extra_id.'">';
	echo '<ul id="widget_navigation'.$extra_id.'" class="'.$class.'">';
	while ($row = Database::fetch_array($result,'ASSOC')){
		echo '<li id="navigationitem_'.$row['id'].$extra_id.'" class="widget_navigation_item">';
		echo '<a href="'.navigation_create_link($row['resource'],$row['url']).'">';
		if (!empty($row['img'])){
			echo Display::return_icon($row['img'],'',array('align'=>'middle'));
		}
		echo $row['label'];
		echo '</a></li>';
	}
	echo '</ul>';
	echo '</div>';
	
	if (api_is_allowed_to_edit()){
		echo '<div id="navigation_administration'.$extra_id.'" style="clear:both;">
					<a href="'.api_get_path(WEB_PATH).'main/widgets/navigation/widgetfunctions.php?action=addnavigation" title="'.get_lang('AddNavigation').'" class="dialoglink">'.Display::return_icon('new_test.gif').' '.get_lang('AddNavigation').'</a><br /> 
					<a href="'.api_get_path(WEB_PATH).'main/widgets/navigation/widgetfunctions.php?action=managenavigation" title="'.get_lang('ManageNavigation').'" class="dialoglink">'.Display::return_icon('wizard.gif').' '.get_lang('ManageNavigation').'</a>
				</div>';
	}
}

function navigation_create_link($resource,$url){
	global $_course;
	
	switch ($resource){
		case '':
			return $url;
			break;
		case 'file';
			//return api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$url;
			return api_get_path(WEB_CODE_PATH).'document/showinframes.php?cidReq='.$_course['sysCode'].'&amp;file='.$url;
		case 'folder';
			//return api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$url;
			return api_get_path(WEB_CODE_PATH).'document/document.php?cidReq='.$_course['sysCode'].'&amp;curdirpath=%2F'.$url;
		case 'tool';
			if (!$_SESSION['toollinks'] OR empty($_SESSION['toollinks'])){
				// Database table definition
				$table_course_tool_list = Database :: get_course_table(TABLE_TOOL_LIST);
				
				$sql = "SELECT * FROM $table_course_tool_list";
				$result = Database::query($sql, __FILE__, __LINE__);
				while ($row = Database::fetch_array($result)){
					$_SESSION['toollinks'][$row['name']] = $row['link'];
				}
			}
			return api_get_path(WEB_CODE_PATH).$_SESSION['toollinks'][$url];			
	}
}

function navigation_get_title($param1, $original_title=false) {
	$config_title = api_get_setting('navigation', 'title');
	if (!empty($config_title) AND $original_title==false){
		return $config_title;
	} else {
		return get_lang('Navigation');
	}
}

function navigation_get_information(){
	echo '<span style="float:right;">';
	navigation_get_screenshot();
	echo '</span>';		
	echo 'This widget allows you to create your own navigation by adding links to tools, links to documents or even external links';
}
function navigation_get_screenshot(){
	echo '<img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/screenshot.jpg" alt="'.get_lang('WidgetScreenshot').'"/>';
}


function navigation_settings_form(){
	// display the navigation horizontal or vertical
	echo '<div class="widget_setting">';
	echo '<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationDisplayHorVert').'</div>';
	echo '<label><input type="radio" name="widget_setting_displayhorvert" id="widget_setting_displayhor" value="horizontal" '.navigation_style_checked(api_get_setting('navigation','displayhorvert'),'horizontal').'/>'.get_lang('Horizontal').'</label>';
	echo '<label><input type="radio" name="widget_setting_displayhorvert" id="widget_setting_displayvert" value="vertical" '.navigation_style_checked(api_get_setting('navigation','displayhorvert'),'vertical').'/>'.get_lang('Vertical').'</label>';
	echo '</div>';
	
	// The style for the navigation
	echo '<div class="widget_setting">';
	echo '<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationDisplayStyle').'</div>';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu1" '.navigation_style_checked(api_get_setting('navigation','style'),'menu1').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu1.gif" alt="Menu1"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu2" '.navigation_style_checked(api_get_setting('navigation','style'),'menu2').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu2.gif" alt="Menu2"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu3" '.navigation_style_checked(api_get_setting('navigation','style'),'menu3').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu3.gif" alt="Menu3"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu4" '.navigation_style_checked(api_get_setting('navigation','style'),'menu4').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu4.gif" alt="Menu4"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu5" '.navigation_style_checked(api_get_setting('navigation','style'),'menu5').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu5.gif" alt="Menu5"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu6" '.navigation_style_checked(api_get_setting('navigation','style'),'menu6').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu6.gif" alt="Menu6"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu7" '.navigation_style_checked(api_get_setting('navigation','style'),'menu7').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu7.gif" alt="Menu7"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu8" '.navigation_style_checked(api_get_setting('navigation','style'),'menu8').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu8.gif" alt="Menu8"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu9" '.navigation_style_checked(api_get_setting('navigation','style'),'menu9').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu9.gif" alt="Menu9"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu10" '.navigation_style_checked(api_get_setting('navigation','style'),'menu10').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu10.gif" alt="Menu10"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu11" '.navigation_style_checked(api_get_setting('navigation','style'),'menu11').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu11.gif" alt="Menu11"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu12" '.navigation_style_checked(api_get_setting('navigation','style'),'menu12').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu12.gif" alt="Menu12"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu13" '.navigation_style_checked(api_get_setting('navigation','style'),'menu13').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu13.gif" alt="Menu13"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu14" '.navigation_style_checked(api_get_setting('navigation','style'),'menu14').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/navigation/menu14.gif" alt="Menu14"/></label><br />';
	echo '</div>';
}


function navigation_style_checked($setting,$expected_value){
	if ($setting == $expected_value){
		return 'checked="checked"';
	}
}

/**
 * This function saves the widget specific settings that cannot be saved in the coruse_setting table.
 * In this particular case we are saving which tools have to be visible and which not. This information is saved in the tool table of the course. 
 *
 */
function navigation_settings_save($formvalues){

}

/*
function navigation_style_checked($setting,$expected_value){
	if ($setting == $expected_value){
		return 'checked="checked"';
	}
}
*/

function navigation_add_navigation($navigation_id){
	global $_course;

	// Database table definition
	// Database table definition
	if (!empty($_course) AND is_array($_course)){
		$table_navigation = Database :: get_course_table('navigation');
	} else {
		$table_navigation = Database :: get_main_table('navigation');
	}
	$table_course_tool_list = Database :: get_course_table(TABLE_TOOL_LIST);
	$table_course_document = Database :: get_course_table(TABLE_DOCUMENT);
	$table_course_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);		

	// the jquery to display the tabs
	echo '<script>
			$("#tabs").tabs();
			
			// filling the hidden form element with the currently active tab
			function getSelectedTabIndex() { 
				var selectedtabindex = $("#tabs").tabs("option", "selected");
				$("#active_tab").val(selectedtabindex);
			}
			$("#tabs").bind("tabsshow", function(event, ui) {
  				getSelectedTabIndex();
			});
			</script>';

	// are we editing an existing navigation item
	if (!empty($navigation_id)){
		$sql = "SELECT * FROM $table_navigation WHERE id='".Database::escape_string($navigation_id)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$current_navigation_item = Database::fetch_array($result);

		// which is the active tab, the content of the forms, ...
		if (empty($current_navigation_item['resource'])){
			$activetabid = 'fragment-1';
			$activetab = 0;
			$form_jquery = 	'
				$("#navigation_text").val("'.$current_navigation_item['label'].'");
				$("#navigation_url").val("'.$current_navigation_item['url'].'");';
		} elseif($current_navigation_item['resource'] == 'tool'){
			$activetabid = 'fragment-2';
			$activetab = 1;
			$form_jquery = 	'
				$("#navigationtool_text").val("'.$current_navigation_item['label'].'");
				$(".navigationtool_'.$current_navigation_item['url'].'").attr("checked", "checked");';
		} else {
			$activetabid = 'fragment-3';
			$activetab = 2;
		}

		// we now fill all the correct form elements with the existing information
		echo '<script>
			$("#tabs").tabs("select", "#'.$activetabid.'");
			$("#active_tab").val("'.$activetab.'");
			$("#navigation_id").val("'.$navigation_id.'");
			$("#SaveManageNavigation").remove();
			'.$form_jquery.'
			</script>';
	}
	
	// the jquery to fill the title immediately: NavigationTool
	echo '<script>$(".navigationtool").live("click",function(){
			$("#navigationtool_text").val($(this).attr("alt"));
			});</script>';
	
	// the jquery to fill the title immediately: NavigationDocument
	echo '<script>$(".navigationdocument").live("click",function(){
			$("#navigationdocument_text").val($(this).val());
			if ($(this).hasClass("folder")){
				$("#navigationdocument_type").val("folder");
			} else{
				$("#navigationdocument_type").val("file");
			}
			
			});</script>';

	
	echo '<script>

		</script>';

	
	// starting the form
	echo '<form id="add_navigation" method="post" action="'.api_get_path(WEB_PATH).'main/widgets/navigation/widgetfunctions.php?action=saveaddnavigation">';	
	
	// starting the div that contains the tabs and the content
	echo '<div id="tabs">';
	
	// hidden form that stores the currently active tab
	echo '	<input type="hidden" name="active_tab" id="active_tab" value="0" />';

	// hidden form that stores which item we are editing (if empty => adding a new one)
	echo '	<input type="hidden" name="navigation_id" id="navigation_id" value="" />';
	
	// the tabs itself
	echo '<ul>';
	echo '	<li><a href="#fragment-1"><span>'.get_lang('NavigationLink').'</span></a></li>';
	if (!empty($_course) AND is_array($_course)){
		echo '	<li><a href="#fragment-2"><span>'.get_lang('NavigationTool').'</span></a></li>';
		echo '	<li><a href="#fragment-3"><span>'.get_lang('NavigationDocument').'</span></a></li>';
	}
	echo '</ul>';
	
	// the content of the first tab
	echo '<div id="fragment-1">';
	// the text that the navigation item has to have
	echo '	<div class="widget_setting">';
	echo '	<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationText').'</div>';
	echo '	<input type="text" name="navigation_text" id="navigation_text" value="'.$navigation_text.'" style="width:100%;" />';
	echo '	</div>';	
	// the text that the navigation item has to have
	echo '	<div class="widget_setting">';
	echo '	<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationUrl').'</div>';
	echo '	<input type="text" name="navigation_url" id="navigation_url" value="'.$navigation_text.'" style="width:100%;" />';
	echo '	</div>';
	echo '</div>';

	
	// the content of the second tab
	if (!empty($_course) AND is_array($_course)){
		echo '<div id="fragment-2">';
		// the text that the navigation item has to have
		echo '	<div class="widget_setting">';
		echo '		<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationText').'</div>';
		echo '		<input type="text" name="navigationtool_text" id="navigationtool_text" value="'.$navigation_text.'" style="width:100%;" />';
		echo '	</div>';
		// the text that the navigation item has to have
		echo '	<div class="widget_setting">';
		echo '		<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationTool').'</div>';
		$sql = "SELECT * FROM $table_course_tool_list";
		$result = Database::query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result)) {
			echo '<label><input name="navigationtool" type="radio" id="navigationtool['.$row['name'].']" value="'.$row['name'].'" alt="'.get_lang(ucfirst($row['name'])).'" class="navigationtool navigationtool_'.$row['name'].'" />'.Display::return_icon($row['image']).' '.get_lang(ucfirst($row['name'])).'</label><br />';
		}
		echo '	</div>';	
		echo '</div>';

		// the content of the third tab
		echo '<div id="fragment-3">';
		// the text that the navigation item has to have
		echo '	<div class="widget_setting">';
		echo '	<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationText').'</div>';
		echo '	<input type="text" name="navigationdocument_text" id="navigationdocument_text" value="'.$navigation_text.'" style="width:100%;" />';
		echo '	</div>';	
		// hidden form that stores if it is a file or a folder
		echo '	<input type="hidden" name="navigationdocument_type" id="navigationdocument_type" />';
	
		echo '	<div class="widget_setting">';
		echo '	<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('NavigationUrl').'</div>';
		echo '
				<script type="text/javascript" src="'.api_get_path(WEB_PATH).'main/course_home/js/jquery.treeview.min.js"></script>
				<link type="text/css" href="'.api_get_path(WEB_PATH).'main/course_home/js/jquery.treeview.css" rel="stylesheet" />
				<script>
					$("#documenttree").treeview();
				</script>';

		// creating an array with all the items (files and folders)
		$sql = "SELECT * FROM $table_course_document ORDER BY path";
		$result = Database::query($sql, __FILE__, __LINE__);
	
		while ($row = Database::fetch_array($result)) {
			if ($row['filetype'] == 'folder'){
				$raw_output[$row['path']] = array();
			} else{
				$raw_output[$row['path']] = $row['path'];
			}
		}

		$tree = explodeTree($raw_output,'/',true);
		//echo '<pre>';
		//print_r($tree);
		//echo '<pre>';
		echo '<ul id="documenttree" class="filetree">';
		print_item($tree);
		echo '</ul>';	
	
		echo '</div>';
	}

	
    // closing the div that contains the tabs and the content
	echo '</div>';

	// closing the form
	echo '</form>';

	echo '<script>';	
	// we already add the save button and a feedback message to the button pane (but do not display it yet)
	if (!empty($navigation_id)){
		$savebutton = get_lang('SaveModifiedNavigationItem');
	} else {
		$savebutton = get_lang('SaveNewNavigationItem');
	}
	echo '$(".ui-dialog-buttonpane").prepend("<button class=\"ui-state-default ui-corner-all\" type=\"button\" name=\"SaveNavigation\" id=\"SaveNavigation\" style=\"display:none;\">'.$savebutton.'</button>");';
	echo '$(".ui-dialog-buttonpane").append("<div class=\"ui-widget\" style=\"width: 75%\">',
												'<div class=\"ui-corner-all dialogfeedback ui-state-highlight\" name=\"dialogfeedback\" id=\"dialogfeedback\" style=\"display:none; line-height:1.4em; font-size: 100%; margin:5px 5px 3px 0px; padding:0.2em 0.6em 0.3em;\">',
													'',
												'</div>',
											'</div>");';
	
	// displaying the save button when something in the form is changed and hiding the OK button
	echo '$("#add_navigation").live("click", function() {
			// hiding the OK button
			$(".ui-dialog-buttonpane button").hide(); 
			// showing the SaveSettings button
			$("#SaveNavigation").show(); // attr("style","display:block;");
    	});';	
    
	// saving the navigation item	
    echo '$("#SaveNavigation").live("click", function() {
    		// changing the button to indicate that we are saving it
			$("#SaveNavigation").html("'.get_lang('SavingDotted').'");
			// the actual saving
			var options = { 
		    	success:    function() { 
		    		// display a feedback message in the dialog for 5 seconds, then remove it
        			$(".dialogfeedback:first").html("'.get_lang('NewNavigationIsSaved').'").show();
        			// hide it again
					$(".dialogfeedback").animate({ 
						opacity: 1
				  	}, 5000).animate({ 
						opacity: 0
				  	}, 1500);
				  	// we set the text of the button again to SaveSettings
				  	$("#SaveNavigation").html("'.$savebutton.'");
					// we show all the buttons again (the OK button was hidden)
					$(".ui-dialog-buttonpane button").show();
					//alert("hier");
					// but we hide the save button again after successfully saving the widget settings
				  	$("#SaveNavigation").hide();
					//alert("hier2");
    			}
			};
			$("#add_navigation").ajaxSubmit(options);
			
    	});';
	echo '</script>';
}

/**
 * Prints an item in the jquery tree
 *
 * @param string or array $item if it is a string then it is a file, if it is an array then it is a folder
 */
function print_item($item){
	foreach ($item as $key=>$element){
		if (is_array($element)){
			echo '<li class="closed"><input name="navigationdocument" type="radio" id="navigationdocument['.$key.']" value="'.$key.'" class="navigationdocument folder" style="float:left;"/> <span class="folder" style="margin-left: 20px">'.$key.'</span>
					<ul>';
					print_item($element);
			echo '	</ul>';	
		}
		else {
			echo '<li><span class="file"><input name="navigationdocument" type="radio" id="navigationdocument['.$key.']" value="'.$element.'" class="navigationdocument file" style="float:left;"/> '.$key.'</span></li>';
		}
	}
}

function navigation_save_add_navigation(){
	global $_course;

	// Database table definition
	if (!empty($_course) AND is_array($_course)){
		$table_navigation = Database :: get_course_table('navigation');
	} else {
		$table_navigation = Database :: get_main_table('navigation');
	}

	// getting the max display order of the items
	$sql = "SELECT max(display_order) AS max FROM $table_navigation";
	$res = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_array($res);
	
	// the actual content depends on the active tab
	switch ($_POST['active_tab']){
		case '0':
			$label 	= $_POST['navigation_text'];
			$url 	= $_POST['navigation_url'];
			break;
		case '1':
			$label 	= 	$_POST['navigationtool_text'];
			$url 	= 	$_POST['navigationtool'];
			$resource = 'tool';
			break;
		case '2':
			$label 		= $_POST['navigationdocument_text'];
			$url 		= $_POST['navigationdocument'];
			$resource 	= $_POST['navigationdocument_type'];
			break;			
	}
	
	if (empty($_POST['navigation_id'])){
		$sql = "INSERT INTO $table_navigation (image,label,url,resource,identifier,display_order) VALUES (
			'".Database::escape_string($_POST['image'])."',
			'".Database::escape_string($label)."',
			'".Database::escape_string($url)."',
			'".Database::escape_string($resource)."',
			'".Database::escape_string($identifier)."',
			'".Database::escape_string(($row['max']+1))."')";
	} else {
		$sql = "UPDATE $table_navigation SET 
			label		= '".Database::escape_string($label)."',
			url 		= '".Database::escape_string($url)."',
			resource 	= '".Database::escape_string($resource)."'
			WHERE id 	= '".Database::escape_string($_POST['navigation_id'])."'";
	}
	$res = Database::query($sql, __FILE__, __LINE__);
}

/**
 * Explode any single-dimensional array into a full blown tree structure, 
 * based on the delimiters found in it's keys.
 * 
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author    Lachlan Donald
 * @author    Takkie
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: explodeTree.inc.php 89 2008-09-05 20:52:48Z kevin $
 * @link      http://kevin.vanzonneveld.net/
 * 
 * @param array   $array
 * @param string  $delimiter
 * @param boolean $baseval
 * 
 * @return array
 */
function explodeTree($array, $delimiter = '_', $baseval = false)
{
    if(!is_array($array)) return false;
    $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
    $returnArr = array();
    foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts    = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);
 
        // Build parent structure 
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
            if (!isset($parentArr[$part])) {
                $parentArr[$part] = array();
            } elseif (!is_array($parentArr[$part])) {
                if ($baseval) {
                    $parentArr[$part] = array('__base_val' => $parentArr[$part]);
                } else {
                    $parentArr[$part] = array(); 
                }
            }
            $parentArr = &$parentArr[$part];
        }
 
        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
            $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
            $parentArr[$leafPart]['__base_val'] = $val;
        }
    }
    return $returnArr;
}

function navigation_install(){
	global $_course;

	// Database table definition
	if (!empty($_course) AND is_array($_course)){
		$table_navigation = Database :: get_course_table('navigation');
	} else {
		$table_navigation = Database :: get_main_table('navigation');
	}
	
	$sql  = 'CREATE TABLE IF NOT EXISTS '.$table_navigation.' (
			  id int(11) NOT NULL auto_increment,
			  image varchar(250) NOT NULL,
			  label varchar(250) NOT NULL,
			  url text NOT NULL,
			  resource varchar(250) NOT NULL,
			  identifier varchar(250) NOT NULL,
			  display_order int(11) NOT NULL,
			  PRIMARY KEY  (id)
			)';	
	$res = Database::query($sql, __FILE__, __LINE__);
}

function navigation_manage_items(){
	navigation_get_content('management');
	echo '
		<style>
		#widget_navigationmanagement li {
			float: none;
			display:block;
			}
		</style>';
	echo '<script>
			$("#widget_navigationmanagement li").each(function(){
					var current_navigation_id = $(this).attr("id").replace("navigationitem_","");
					$(this).prepend("<span style=\"float:right; padding-top: 5px;\"><img src=\"'.api_get_path(WEB_CODE_PATH).'img/edit.png\" id=\"edit_" + current_navigation_id + "\" class=\"navigation_edit\"/><img src=\"'.api_get_path(WEB_CODE_PATH).'img/delete.png\" id=\"delete_" + current_navigation_id + "\" class=\"navigation_delete\"/></span>");
					});
					
			// disable the click
			$("#widget_navigationmanagement a").click(function() { return false; });
			
			// hide the administrative links
			$("#navigation_administrationmanagement").hide();
			
			// add a class to style it nicer
			$("#widget_navigationmanagement a").addClass("widgetconfigurationitem active");
			
			// we already add the save button and a feedback message to the button pane (but do not display it yet)
			$(".ui-dialog-buttonpane").prepend("<button class=\"ui-state-default ui-corner-all\" type=\"button\" name=\"SaveManageNavigation\" id=\"SaveManageNavigation\" style=\"display:none;\">' . get_lang ( 'SavingNavigationItems' ) . '</button>");
			$(".ui-dialog-buttonpane").append("<div class=\"ui-widget\" style=\"width: 75%\">', '<div class=\"ui-corner-all dialogfeedback ui-state-highlight\" name=\"dialogfeedback\" id=\"dialogfeedback\" style=\"display:none; line-height:1.4em; font-size: 100%; margin:5px 5px 3px 0px; padding:0.2em 0.6em 0.3em;\">', get_lang ( 'ABC' ), '</div>', '</div>");			
			
			$("#widget_navigationmanagement").sortable({
				connectWith: "li",
				opacity: 0.6, 
				cursor: "move",
				update: function() {
					
					// temporarily hiding the OK button
					$(".ui-dialog-buttonpane button").hide(); 
					// temporarily showing the SaveSettings button
					$("#SaveManageNavigation").show(); // attr("style","display:block;");
				    		
					// the querystring
					var order = $(this).sortable(\'serialize\')+"&action=navigationsaveorder";
					$.post("'.api_get_path(WEB_PATH).'main/widgets/navigation/widgetfunctions.php", order, function(theResponse){
						$("#debug").html(theResponse);
					}); 

		    		// display a feedback message in the dialog for 5 seconds, then remove it
        			$(".dialogfeedback").html("' . get_lang ( 'WidgetSettingsAreSaved' ) . '").show();
        			// hide it again
					$(".dialogfeedback").animate({ 
						opacity: 1
				  	}, 5000).animate({ 
						opacity: 0
				  	}, 1500);	
				  	// we show all the buttons again (the OK button was hidden)
					$(".ui-dialog-buttonpane button").show();
					// but we hide the save button again after successfully saving the widget settings
				  	$("#SaveManageNavigation").hide();				
				}	
			});	

			$(".navigation_delete").live("click",function(){
				var current_navigation_id = $(this).parents().filter("li").attr("id").replace("navigationitem_","");
				var parameters = "action=navigationdeleteitem&id="+current_navigation_id;
				$.post("'.api_get_path(WEB_PATH).'main/widgets/navigation/widgetfunctions.php", parameters, function(theResponse){
					$(".dialogfeedback").html("' . get_lang ( 'TheNavigationItemHasBeenDeleted' ) . '").show();
					$("#navigationitem_"+current_navigation_id).remove();
				}); 
				
			});

			$(".navigation_edit").live("click",function(){
				// the navigation id we are editing
				var current_navigation_id = $(this).parents().filter("li").attr("id").replace("navigationitem_","").replace("management","");

				// show a temporary loader
				$(".ui-dialog-content").html("<div align=\"center\"><br />'.str_replace('"','\"',Display::return_icon('ajax-loader.gif','',array('style'=>'text-align: left;'))).'</div>");

				// we load the form for editing the navigation item
				$(".ui-dialog-content").load("'.api_get_path(WEB_PATH).'main/widgets/navigation/widgetfunctions.php",{"action":"addnavigation","navigation_id":current_navigation_id});
				
			});

		</script>';
}

function navigation_save_order(){
	global $_course;

	// Database table definition
	if (!empty($_course) AND is_array($_course)){
		$table_navigation = Database :: get_course_table('navigation');
	} else {
		$table_navigation = Database :: get_main_table('navigation');
	}

	$counter = 1;
	foreach($_POST['navigationitem'] as $key=>$itemid){
		$sql = "UPDATE $table_navigation SET display_order='".Database::escape_string($counter)."' WHERE id='".Database::escape_string($itemid)."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$counter++;
	}
}

function navigation_delete_item($id){
	global $_course;

	// Database table definition
	if (!empty($_course) AND is_array($_course)){
		$table_navigation = Database :: get_course_table('navigation');
	} else {
		$table_navigation = Database :: get_main_table('navigation');
	}

	$sql = "DELETE FROM $table_navigation WHERE id='".Database::escape_string($id)."'";
	$res = Database::query($sql, __FILE__, __LINE__);
	echo $sql;
}
?>
