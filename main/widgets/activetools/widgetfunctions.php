<?php 
// including the widgets language file
$language_file = array ('widgets', 'course_home');

// include the global Dokeos file
include_once('../../inc/global.inc.php');

// load the specific widget settings
api_load_widget_settings();

//action handling
switch ($_POST['action']) {
	case 'get_widget_information':
		activetools_get_information();
		break;
	case 'get_widget_content':
		activetools_get_content();
		break;			
}
switch ($_GET['action']) {
	case 'get_widget_information':
		activetools_get_information();
		break;
	case 'get_widget_content':
		activetools_get_content();
		break;
	case 'get_widget_title':
		activetools_get_title();
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
function activetools_get_scope(){
	return array('course');
}

function activetools_get_content(){
	// Database table definition
	$table_course_tool_list = Database :: get_course_table(TABLE_TOOL_LIST);
	$table_main_tool_list 	= Database :: get_main_table(TABLE_MAIN_COURSE_MODULE);
	
	// the query to select all the active tools
	if (api_is_allowed_to_edit()){
		$sql = "SELECT a.*, t.image img FROM $table_course_tool_list a, $table_main_tool_list t
							WHERE a.visibility = 1 AND ((a.link=t.link)
							OR ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image))
							GROUP BY link 
							 ORDER BY a.id";									 
	} else {
	$sql = "SELECT a.*, t.image img FROM $table_course_tool_list a, $table_main_tool_list t
							WHERE a.visibility = 1 AND ((a.link=t.link AND t.position<>'courseadmin')
							OR ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image))
							GROUP BY link 
							 ORDER BY a.id";									 
	}
	$result = Database::query($sql, __FILE__, __LINE__);
	
	// adding the style of the active tools (the actual style is determined by the id of the div which comes from api_get_setting('activetools','style')
	echo '<style type="text/css" media="screen, projection">
			/*<![CDATA[*/
			@import "'.api_get_path(WEB_PATH).'main/widgets/activetools/menu.css";
			/*]]>*/
			</style>';
	
	// display the menu horizontally or not?
	if (api_get_setting('activetools','displayhorvert') == 'horizontal'){
		echo '
			<style>
			#widget_activetools_toollist li {
				float: left;
				display:block;
				}
			</style>';
	}
	
	// the menu
	echo '<div id="'.api_get_setting('activetools','style').'">';
	echo '<ul id="widget_activetools_toollist" class="'.$class.'">';
	while ($row = Database::fetch_array($result,'ASSOC')){
		echo '<li><a href="'.api_get_path(WEB_CODE_PATH).$row['link'].'">'.Display::return_icon($row['img'],'',array('align'=>'middle')).' '.get_lang(ucfirst($row['name'])).'</a></li>';
	}
	echo '</ul>';
	echo '</div>';
}

function activetools_get_title($param1, $original_title=false) {
	$config_title = api_get_setting('activetools', 'title');
	if (!empty($config_title) AND $original_title==false){
		return $config_title;
	} else {
		return get_lang('Tools');
	}
}

function activetools_get_information(){
	echo '<span style="float:right;">';
	activetools_get_screenshot();
	echo '</span>';	
	echo get_lang('ActiveToolsExplanation')	;
}
function activetools_get_screenshot(){
	echo '<img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/screenshot.jpg" alt="'.get_lang('WidgetScreenshot').'"/>';
}


function activetools_settings_form(){
	// Database table definition
	$table_course_tool_list = Database :: get_course_table(TABLE_TOOL_LIST);
	
	// display the activetools horizontal or vertical
	echo '<div class="widget_setting">';
	echo '<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('ActiveToolsDisplayHorVert').'</div>';
	echo '<label><input type="radio" name="widget_setting_displayhorvert" id="widget_setting_displayhor" value="horizontal" '.active_tools_style_checked(api_get_setting('activetools','displayhorvert'),'horizontal').'/>'.get_lang('Horizontal').'</label>';
	echo '<label><input type="radio" name="widget_setting_displayhorvert" id="widget_setting_displayvert" value="vertical" '.active_tools_style_checked(api_get_setting('activetools','displayhorvert'),'vertical').'/>'.get_lang('Vertical').'</label>';
	echo '</div>';
	
	// selecting the tools that need to be active. We do not give these form elements a name that starts with widget_setting_ because
	// these are handled differently by the activetools_settings_save() function
	echo '<div class="widget_setting">';
	echo '<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('CheckTheToolsThatNeedToBeActive').'</div>';
	$sql="SELECT * FROM $table_course_tool_list ORDER BY category, name";
	$result = Database::query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result)) {
		echo '<label><input name="activetools['.$row['name'].']" type="checkbox" id="activetools['.$row['name'].']" value="1" '.active_tools_style_checked($row['visibility'],'1').'/>'.Display::return_icon($row['image']).' '.get_lang(ucfirst($row['name'])).'</label><br />';
	}
	echo '</div>';	
	
	// The style for the activetools
	echo '<div class="widget_setting">';
	echo '<div  class="ui-corner-all widget_setting_subtitle">'.get_lang('ActiveToolsDisplayStyle').'</div>';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu1" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu1').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu1.gif" alt="Menu1"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu2" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu2').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu2.gif" alt="Menu2"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu3" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu3').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu3.gif" alt="Menu3"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu4" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu4').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu4.gif" alt="Menu4"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu5" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu5').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu5.gif" alt="Menu5"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu6" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu6').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu6.gif" alt="Menu6"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu7" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu7').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu7.gif" alt="Menu7"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu8" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu8').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu8.gif" alt="Menu8"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu9" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu9').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu9.gif" alt="Menu9"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu10" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu10').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu10.gif" alt="Menu10"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu11" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu11').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu11.gif" alt="Menu11"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu12" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu12').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu12.gif" alt="Menu12"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu13" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu13').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu13.gif" alt="Menu13"/></label><br />';
  	echo '<label><input type="radio" name="widget_setting_style" value="menu14" '.active_tools_style_checked(api_get_setting('activetools','style'),'menu14').'/><img src="'.api_get_path(WEB_PATH).'main/widgets/activetools/menu14.gif" alt="Menu14"/></label><br />';
	echo '</div>';	
}

/**
 * This function saves the widget specific settings that cannot be saved in the coruse_setting table.
 * In this particular case we are saving which tools have to be visible and which not. This information is saved in the tool table of the course. 
 *
 */
function activetools_settings_save($formvalues){
	// Database table definition
	$table_course_tool_list = Database :: get_course_table(TABLE_TOOL_LIST);
	
	// first we set every tool as invisible
	$sql = "UPDATE $table_course_tool_list SET visibility='0'";
	$result = Database::query($sql, __FILE__, __LINE__);
	
	foreach ($formvalues['activetools'] as $key=>$value){
		$tools[]=$key;
	}
	
	// now we update the checked checkboxes to visibility = 1 for the selected tools ($tools)
	if (is_array($tools)){
		$sql = "UPDATE $table_course_tool_list SET visibility='1' WHERE name IN ('".implode("','",$tools)."')";
		$result = Database::query($sql, __FILE__, __LINE__);
	}
}

function active_tools_style_checked($setting,$expected_value){
	if ($setting == $expected_value){
		return 'checked="checked"';
	}
}
?>
