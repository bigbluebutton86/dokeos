<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 *  Learning Path
 * This is a learning path creation and player tool in Dokeos - previously learnpath_handler.php
 * @package dokeos.learnpath
 * @author Yannick Warnier - cleaning and update for new SCORM tool
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Julio Montoya  - Improving the list of templates
 */

$this_section=SECTION_COURSES;

api_protect_course_script();

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default

include('learnpath_functions.inc.php');
include('resourcelinker.inc.php');
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
// name of the language file that needs to be included
$language_file = "learnpath";

/*
-----------------------------------------------------------
	Header and action code
-----------------------------------------------------------
*/
$htmlHeadXtra[] = '
<script type="text/javascript">

function FCKeditor_OnComplete( editorInstance )
{
	//document.getElementById(\'frmModel\').innerHTML = "<iframe height=890px; width=230px; frameborder=0 src=\''.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/fckdialogframe.html \'>";
}

function InnerDialogLoaded()
{
	if (document.all)
	{
		// if is iexplorer
		var B=new window.frames.content_lp___Frame.FCKToolbarButton(\'Templates\',window.content_lp___Frame.FCKLang.Templates);
	}
	else
	{
		var B=new window.frames[0].FCKToolbarButton(\'Templates\',window.frames[0].FCKLang.Templates);
	}
	return B.ClickFrame();
};

</script>';
$htmlHeadXtra[] = $_SESSION['oLP']->create_js();
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$is_allowed_to_edit = api_is_allowed_to_edit(null,true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];
/*
$chapter_id     = $_GET['chapter_id'];
$title          = $_POST['title'];
$description   = $_POST['description'];
$Submititem     = $_POST['Submititem'];
$action         = $_REQUEST['action'];
$id             = (int) $_REQUEST['id'];
$type           = $_REQUEST['type'];
$direction      = $_REQUEST['direction'];
$moduleid       = $_REQUEST['moduleid'];
$prereq         = $_REQUEST['prereq'];
$type           = $_REQUEST['type'];
*/
/*
==============================================================================
		MAIN CODE
==============================================================================
*/
// using the resource linker as a tool for adding resources to the learning path
if ($action=="add" and $type=="learnpathitem")
{
	 $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ( (! $is_allowed_to_edit) or ($isStudentView) )
{
	error_log('New LP - User not authorized in lp_add_item.php');
	header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id";
$result=Database::query($sql_query);
$therow=Database::fetch_array($result);

// Get item info
$item_id = Security::remove_XSS($_GET['id']);

// Get the item type
$sql = "SELECT item_type FROM " . $tbl_lp_item . "
		WHERE id = " . Database::escape_string($item_id);
$rs_item = Database::query($sql,__FILE__,__LINE__);
$row_item = Database::fetch_array($rs_item,'ASSOC');
$item_type = $row_item['item_type'];

//$admin_output = '';
/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students - always available in this case (page only shown to admin)
-----------------------------------------------------------
*/
/*==================================================
			SHOWING THE ADMIN TOOLS
 ==================================================*/



/*==================================================
	prerequisites setting end
 ==================================================*/
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
$interbreadcrumb[]= array ("url"=>api_get_self()."?action=build&lp_id=$learnpath_id", "name" =>stripslashes("{$therow['name']}"));
//Theme calls
$show_learn_path=true;
$lp_theme_css=$_SESSION['oLP']->get_theme();

Display::display_tool_header(null,'Path');
//api_display_tool_title($therow['name']);

$suredel = trim(get_lang('AreYouSureToDelete'));
//$suredelstep = trim(get_lang('AreYouSureToDeleteSteps'));
?>
<script type='text/javascript'>
/* <![CDATA[ */
function stripslashes(str) {
	str=str.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
}
function confirmation(name)
{
	name=stripslashes(name);
	if (confirm("<?php echo $suredel; ?> " + name + " ?"))
	{
		return true;
	}
	else
	{
		return false;
	}
}
</script>
<?php

//echo $admin_output;
/*
-----------------------------------------------------------
	DISPLAY SECTION
-----------------------------------------------------------
*/

//echo $_SESSION['oLP']->build_action_menu();
echo '<div class="actions">';
$gradebook = Security::remove_XSS($_GET['gradebook']);
echo '<a href="' . api_get_self() . '?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '">' . Display::return_icon('pixel.gif', get_lang('Author'), array('class' => 'toolactionplaceholdericon toolactionback')).' '.get_lang('Author') . '</a>';
echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display::return_icon('pixel.gif', get_lang('Content'),array('class'=>'toolactionplaceholdericon toolactionauthorcontent')).get_lang('Content') . '</a>';
echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&gradebook=&action=admin_view&lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display::return_icon('pixel.gif', get_lang('Scenario'),array('class'=>'toolactionplaceholdericon toolactionauthorscenario')).get_lang("Scenario") . '</a>';
echo '</div>';

echo '<div id="content_with_secondary_actions">';
echo '<table style="width:100%" cellpadding="0" cellspacing="0" class="lp_build">';
		echo '<tr>';
		/*echo '<td class="tree" valign="top" style="width:18%">';

		$path_item = isset($_GET['path_item'])?$_GET['path_item']:0;
		$path_item = Database::escape_string($path_item);
		$tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
		$sql_doc = "SELECT path FROM " . $tbl_doc . " WHERE id = '". $path_item."' ";
		$res_doc=Database::query($sql_doc, __FILE__, __LINE__);
		$path_file=Database::result($res_doc,0,0);
		$path_parts = pathinfo($path_file);

		if (Database::num_rows($res_doc) > 0 && $path_parts['extension']=='html'){
			$count_items = count($_SESSION['oLP']->ordered_items);
			$style = ($count_items > 12)?' style="height:250px;width:230px;overflow-x : auto; overflow : scroll;" ':' class="lp_tree" ';
			echo '<div '.$style.'>';
			//build the tree with the menu items in it
			echo $_SESSION['oLP']->build_tree();
			echo '</div>';
			// show the template list
			echo '<p style="border-bottom:1px solid #999999; margin:0; padding:2px;"></p>'; //line
			echo '<br>';
			echo '<div id="frmModel" style="display:block; height:890px;width:100px; position:relative;"></div>';
		} else {
			echo '<div class="lp_tree" style="height:90%" >';
			//build the tree with the menu items in it
			//echo $_SESSION['oLP']->build_tree();
			echo '</div>';
		}

		echo '</td>';*/
		echo '<td class="workspace" valign="top" style="width:100%">';
			if(isset($is_success) && $is_success === true) {
				$msg = '<div class="lp_message" style="margin-bottom:10px;">';
					$msg .= 'The item has been edited.';
				$msg .= '</div>';
			//	echo $_SESSION['oLP']->display_item($_GET['id'], $msg);
				echo '<script>window.location.href="lp_controller.php?'.api_get_cidReq().'&action=admin_view&lp_id='.$learnpath_id.'"</script>';
			} else {
				echo $_SESSION['oLP']->display_edit_item($_GET['id']);
			}
		echo '</td>';
	echo '</tr>';
echo '</table>';
echo '</div>';

//Bottom bar
echo '<div class="actions">';

if ($item_type != 'dokeos_chapter' && $item_type != 'chapter') {
	echo '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=edit_item_prereq&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . Security::remove_XSS($_GET['lp_id']) . '" title="' . get_lang('Prerequisites') . '">'.Display::return_icon('pixel.gif',get_lang('Prerequisites'),array('class'=>'actionplaceholdericon actionauthorprerequisites','align'=>'absbottom') ). '' . get_lang('Prerequisites') . '</a>';
}
echo '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=delete_item&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . Security::remove_XSS($_GET['lp_id']) . '" onclick="return confirmation(\'' . addslashes($s_title) . '\');" title="Delete the current item">'.Display::return_icon('pixel.gif',get_lang('Delete'),array('class'=>'actionplaceholdericon actiondelete','alt'=>'Delete the current item','align'=>'absbottom')).''. get_lang("Delete") . '</a>';
echo '</div>';
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
