<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * Learning Path
 * @package dokeos.learnpath
 */

// Language files that should be included
//$language_file []= 'languagefile1';
//$language_file []= 'languagefile2';

// setting the help
$help_content = 'learningpath';

// including the global Dokeos file
require_once '../inc/global.inc.php';

// including additional libraries
require_once('back_compat.inc.php');
require_once('learnpathList.class.php');
require_once('learnpath.class.php');
require_once('learnpathItem.class.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// setting the tabs
$this_section=SECTION_COURSES;

// Add additional javascript, css
$htmlHeadXtra[] =
"<script language='javascript' type='text/javascript'>
	function confirmation(name)
	{
		if (confirm(\" ".trim(get_lang('AreYouSureToDelete'))." \"+name+\"?\"))		return true;
		else																		return false;
	}
</script>";
// Add additional javascript, css
$htmlHeadXtra[] ='<script type="text/javascript">
$(document).ready(function(){
	$(function() {
		$(".sort").sortable({ opacity: 0.6,cursor: "move",cancel: ".nodrag", update: function(event, ui) {
                var current_lp_id = ui.item.attr("id");
                var current_lp_data = current_lp_id.split("lp_row_");
                var Lp_id = current_lp_data[1]; // This is the current LP ID of the selected row

                var sorted_list = $(this).sortable("serialize");
                    sorted_list = sorted_list.replace(/&/g,"");
                var sorted_data = sorted_list.split("lp_row[]=");

               // get new order of this lp
                var newOrder = 0;
                for(var i=0; i<sorted_data.length; i++){
                  if(sorted_data[i] == Lp_id){
                    newOrder = i;
                  }
                }
		  // call ajax to save new position
		  $.ajax({
			   type: "GET",
			   url: "lp_ajax_change_position.php?' . api_get_cidreq() . '&action=change_lp_position&lp_id="+Lp_id+"&new_order="+newOrder,
			   success: function(response){
                                document.location="lp_controller.php?' . api_get_cidreq() . '";
                           }
                        })
                    }
		});
	});

});
</script> ';

// Unregister the session if it exists
if(isset($_SESSION['lpobject'])) {
  $oLP = unserialize($_SESSION['lpobject']);
    if(is_object($oLP)){
      api_session_unregister('oLP');
      api_session_unregister('lpobject');
    }
  } elseif (is_null($_SESSION['lpobject']) && isset($_SESSION['oLP'])) {
    api_session_unregister('oLP');
 }
// setting the breadcrumbs
$interbreadcrumb[] = array ("url"=>"overview.php", "name"=> get_lang('OverviewOfAllCodeTemplates'));
$interbreadcrumb[] = array ("url"=>"coursetool.php", "name"=> get_lang('CourseTool'));

// Display the header
Display::display_tool_header(get_lang('CourseTool'));

/*------------------------------*/

if(empty($lp_controller_touched) || $lp_controller_touched!=1){
	header('location: lp_controller.php?action=list');
}

$courseDir   = api_get_course_path().'/scorm';
$baseWordDir = $courseDir;
$display_progress_bar = true;

/**
 * Display initialisation and security checks
 */
$nameTools = get_lang(ucfirst(TOOL_LEARNPATH));
event_access_tool(TOOL_LEARNPATH);

if (! $is_allowed_in_course) api_not_allowed();

/**
 * Display
 */
/* Require the search widget and prepare the header with its stuff */
if (api_get_setting('search_enabled') == 'true') {
	require api_get_path(LIBRARY_PATH).'search/search_widget.php';
	search_widget_prepare($htmlHeadXtra);
}

/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/
Display::display_introduction_section(TOOL_LEARNPATH, array(
		'CreateDocumentWebDir' => api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document/',
		'CreateDocumentDir' => '../../courses/'.api_get_course_path().'/document/',
		'BaseHref' => api_get_path('WEB_COURSE_PATH').api_get_course_path().'/'
	)
);

// variable initialisation
$is_allowed_to_edit = api_is_allowed_to_edit(null,true);
$current_session = api_get_session_id();

if($is_allowed_to_edit)
{
  /*--------------------------------------
    DIALOG BOX SECTION
    --------------------------------------*/

  if (!empty($dialog_box))
  {
	  switch ($_GET['dialogtype'])
	  {
		case 'confirmation':	Display::display_confirmation_message($dialog_box);		break;
		case 'error':			Display::display_error_message($dialog_box);			break;
		case 'warning':			Display::display_warning_message($dialog_box);			break;
		default:	    		Display::display_normal_message($dialog_box);			break;
	  }
  }

	if (api_failure::get_last_failure()){
		Display::display_normal_message(api_failure::get_last_failure());
	}

	echo '<div class="actions">';
		echo '<a class="" href="#">'.Display::return_icon('pixel.gif', get_lang('Author'), array('class' => 'toolactionplaceholdericon toolactionauthor')) . get_lang("Author").'</a>';
	echo '</div>';
}

/*---------------------------------------------------------------------------------------------------------------------------------*/
?>


<div id="content">
	<?php
		if (api_is_allowed_to_edit()) {
			/* CREATE NEW, SCORM, WORD, POWERPOINT */
			$common_params = api_get_cidreq()."&curdirpath=/&tool=".TOOL_LEARNPATH;
			$href_create_new =	"\"".api_get_self()."?".api_get_cidreq()."&action=add_lp\"";
			$href_scorm = 		"\"../upload/index.php?".$common_params."\"";
			$href_word = 		"\"../upload/upload_word.php?".$common_params."\"";
			$href_ppt = 		"\"../upload/upload_ppt.php?".$common_params."\"";
	
			$common_classes = "big_button four_buttons rounded grey_border";
			echo "<a href=$href_create_new class=\"$common_classes new_button\" >".get_lang("New")."</a>";
			echo "<a href=$href_scorm class=\"$common_classes scorm_button\" >".get_lang("UploadScorm")."</a>";
			echo "<a href=$href_word class=\"$common_classes serious_game_button \" >".get_lang("SeriousGameConvert")."</a>";
			if(api_get_setting('service_ppt2lp', 'active') === 'true')
			{
				echo "<a href=$href_ppt class=\"$common_classes ppt_button\" >".get_lang("PowerPointConvert")."</a>";
			}
		}

	?>

	<!-- list of courses -->
	<div class="bg_white clear_b">
		<table class="data_table" id="table_lp_list">
			<thead>
				<tr class="row_odd nodrop nodrag">
					<th>	<?php echo get_lang("Move"); ?></th>
					<?php
					if($is_allowed_to_edit){
					?>
					<th>	<?php echo get_lang("Edit"); ?></th>
					<?php
					}
					?>
					<th align="left">	<?php echo get_lang("ExistingCourses"); ?></th>
					<?php
					if($is_allowed_to_edit){
					?>
					<th>	<?php echo get_lang("_delete"); ?></th>
					<?php
					}
					else
					{
					?>
					<th>	<?php echo get_lang("Progress"); ?></th>
					<?php
					}
					?>
                                        <?php
					if($is_allowed_to_edit){
					?>
					<th>	<?php echo get_lang("Visible"); ?></th>
					<?php
					}
					if($is_allowed_to_edit){
                                        ?>
					<th>	<?php echo get_lang("Configure"); ?></th>
					<?php
					}
                                        ?>
                                        
				</tr>
			</thead>
			<tbody class="sort">
				<?php /* liste des courses... */

					$list = new LearnpathList(api_get_user_id());
					$flat_list = $list->get_flat_list();
					$test_mode = api_get_setting('server_type');
					$max = count($flat_list);
					if (is_array($flat_list) && !empty($flat_list))
					{
						$counter = 0;
						$current = 0;
						foreach ($flat_list as $id => $details)
						{
							$progress_bar = learnpath::get_db_progress($id,api_get_user_id());
 							$html = "<div  style='width:$progress_bar;background:url(\"../img/navigation/bg_progress_bar.gif\") repeat-x 0 0;height:20px;'></div>";

							$name = Security::remove_XSS($details['lp_name']);
							$open_link = 'lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$id;

							$edit_link = Display::return_icon('pixel.gif', get_lang('Edit'), array('class' => 'actionplaceholdericon actionedit'));
							$delete_link = "";

							if($is_allowed_to_edit)
							{
								$delete_title = get_lang('_delete_learnpath');
								if ($current_session == $details['lp_session']){
									/* EDIT/BUILD COMMAND */
									if($details['lp_type']==1 || $details['lp_type']==2 || $details['lp_type']==3){
										$edit_link =	'<a href="lp_controller.php?'.api_get_cidreq().'&amp;action=add_item&amp;type=step&amp;lp_id='.$id.'">' .
															Display::return_icon('pixel.gif', get_lang('Edit'), array('class' => 'actionplaceholdericon actionedit')).
														'</a>';
									}

									/* DELETE COMMAND */
									$delete_link =	"<a href=\"lp_controller.php?".api_get_cidreq()."&action=delete&lp_id=$id\" onClick=\"return confirmation('".addslashes($name)."');\" >" .
														Display::return_icon('pixel.gif', get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete')).
													"</a>";
								} else {
									$delete_link = 	Display::return_icon('pixel.gif', get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete invisible'));
								}
							}	// endif $is_allowed_to_edit
       $css_class = ($counter%2 == 0) ? 'row_even' : 'row_odd';
							$line =		'<tr id="lp_row_'.$id.'" class='.$css_class.'>'.
		         '<td class="dragHandle" align="center" style="cursor:pointer;width:40px;">'.Display::return_icon('pixel.gif', get_lang('Move'), array('class' => 'actionplaceholdericon actionsdraganddrop')).'</td>';
							if($is_allowed_to_edit){
								$line .=	"<td class='nodrag' align='center' style='width:60px;'>".$edit_link."</td>";
							}
								$line .=	"<td class='nodrag' align='left' style='width:770px;'><a href='".$open_link."' class='blue_link'>".$name."</a></td>";
							if($is_allowed_to_edit){
								$line .=	"<td class='nodrag' align='center' style='width:60px;'>".$delete_link."</td>";
							}
							else
							{
								$line .= "<td>".$html."</td>";
							}
							if($is_allowed_to_edit){
                                                                $coment =  ($details['lp_visibility'] == 1 ? get_lang('Enabled') : get_lang('Hidden')) ;
                                                                $visible = ($details['lp_visibility'] == 1 ? Display::return_icon('pixel.gif', get_lang('Visible'), array('class' => 'actionplaceholdericon actionvisible')) : Display::return_icon('pixel.gif', get_lang('Invisible'), array('class' => 'actionplaceholdericon actionvisible invisible'))) ;
                                                                $new_status = ($details['lp_visibility'] == 1 ? 0 : 1);
								$line .= "<td class='nodrag' align='center' style='width:60px;'><a href='?action=toggle_visible&lp_id=".$id."&new_status=".$new_status."'>".$visible."</a></td>";
								$line .= "<td class='nodrag' align='center' style='width:60px;'>";
                                                                
                                                                if($details['lp_prevent_reinit']==1){
                                                                        $line .= '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
                                                                                        Display::return_icon('pixel.gif',get_lang('AllowMultipleAttempts'),array('class'=>'actionplaceholdericon actionreload_na'));
                                                                }else{
                                                                        $line .= '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
                                                                                        Display::return_icon('pixel.gif',get_lang('PreventMultipleAttempts'),array('class'=>'actionplaceholdericon actionreload'));
                                                                                        '</a>&nbsp;';
							}     
                                                                $line .= "</td>";
                                                                
							}     
                                                        $line .= "</tr>";

							echo $line;
       $counter++;
						}
					} else {
						// TODO set multilang message
						$line =		"<tr>".
											"<td class='button'></td>".
											"<td class='button'></td>".
									 		"<td><a href='".$open_link."' class='blue_link'>".get_lang("LpNoCourses")."</a></td>".
           "<td class='button'></td>".
										"</tr>";

							echo $line;
					}
				?>
			</tbody>
		</table>
	</div>

</div><!--end of div#content-->

<?php
//// start the content div
//echo '<div id="content">';
//echo '<table width="100%" border="0" cellspacing="2" class="data_table">';
//$is_allowed_to_edit ? $colspan = 9 : $colspan = 3;
//
//if (!empty($curDirPath))
//{
//  if(substr($curDirPath,1,1)=='/')  	$tmpcurDirPath=substr($curDirPath,1,strlen($curDirPath));
//  else							  		$tmpcurDirPath = $curDirPath;
  ?>
  <!--
  <tr>
    <td colspan="<?php echo $colspan ?>" align="left" bgcolor="#4171B5">
      <img src="../img/opendir.gif" align="absbottom" vspace="2" hspace="3" alt="open_dir" />
      <font color="#ffffff"><b><?php echo $tmpcurDirPath ?></b></font>
    </td>
  </tr>
  -->
  <?php
//}
//
///* CURRENT DIRECTORY */
//
//echo	'<tr>';
//echo	'<th>'.get_lang('Title').'</th>'."\n" .
//		'<th>'.get_lang('Progress')."</th>\n";
//if ($is_allowed_to_edit)
//{
//  echo '<th>'.get_lang('CourseSettings')."</th>\n" .
//  		//xport now is inside "Edit"
//		'<th>'.get_lang('AuthoringOptions')."</th>\n";
//
//	// only available for not session mode
//	if ($current_session == 0) {
//		echo'<th>'.get_lang('Move')."</th>\n";
//	}
//}
//
//echo		"</tr>\n";
//
///*--------------------------------------
//	  DISPLAY SCORM LIST
//  --------------------------------------*/
//$list = new LearnpathList(api_get_user_id());
//$flat_list = $list->get_flat_list();
//$test_mode = api_get_setting('server_type');
//$max = count($flat_list);
//if (is_array($flat_list))
//{
//	$counter = 0;
//	$current = 0;
//	foreach ($flat_list as $id => $details)
//	{
//		//validacion when belongs to a session
//		$session_img = api_get_session_image($details['lp_session'], $_user['status']);
//
//	    if(!$is_allowed_to_edit && $details['lp_visibility'] == 0)
//	    {
//	    	// This is a student and this path is invisible, skip
//	    	continue;
//	    }
//		$counter++;
//	    if (($counter % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }
//
//		$url_start_lp = 'lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$id;
//		$name = Security::remove_XSS($details['lp_name']);
//		$image='<img src="../img/kcmdf.gif" border="0" align="absmiddle" alt="' . $name . '">'."\n";
//	    $dsp_line =	'<tr align="center" class="'.$oddclass.'">'."\n" .
//        				'<td align="left" valign="top">' .
//							'<div style="float: left; width: 35px; height: 22px;"><a href="'.$url_start_lp.'">' .
//		$image . '</a></div><a href="'.$url_start_lp.'">' . $name . '</a>' . $session_img .
//			"</td>\n";
//	    $dsp_desc = '';
//	    $dsp_export = '';
//	    $dsp_edit = '';
//	    $dsp_edit_close = '';
//	    $dsp_delete = '';
//	    $dsp_visible = '';
//	    $dsp_default_view = '';
//	    $dsp_debug = '';
//	    $dsp_order = '';
//
//	    // Select course theme
//		if (!empty($platform_theme))		$mystyle=$platform_theme;
//		if (!empty($user_theme))			$mystyle=$user_theme;
//		if (!empty($mycoursetheme))			$mystyle=$mycoursetheme;
//
//		$lp_theme_css=$mystyle;
//
//	    if($display_progress_bar)
//	    	$dsp_progress = '<td>'.learnpath::get_progress_bar('%',learnpath::get_db_progress($id,api_get_user_id()),'').'</td>';
//	    else
//			$dsp_progress = '<td style="padding-top:1em;">'.learnpath::get_db_progress($id,api_get_user_id(),'both').'</td>';
//
//	    if($is_allowed_to_edit) {
//	    	if ($current_session == $details['lp_session']) {
//		    	$dsp_desc = '<td valign="middle" style="color: grey; padding-top:1em;"><em>'.$details['lp_maker'].'</em>  &nbsp;&nbsp; '.$details['lp_proximity'].' &nbsp;&nbsp; '.$details['lp_encoding'].'<a href="lp_controller.php?'.api_get_cidreq().'&action=edit&lp_id='.$id.'">&nbsp;&nbsp;<img src="../img/edit.png" border="0" title="'.get_lang('_edit_learnpath').'"></a></td>'."\n";
//	    	} else {
//				$dsp_desc = '<td valign="middle" style="color: grey; padding-top:1em;"><em>'.$details['lp_maker'].'</em>  &nbsp;&nbsp; '.$details['lp_proximity'].' &nbsp;&nbsp; '.$details['lp_encoding'].'<img src="../img/edit_na.gif" border="0" title="'.get_lang('_edit_learnpath').'"></td>'."\n";
//	    	}
//
//			/* edit title and description */
//
//			$dsp_edit = '<td align="center">';
//	    	$dsp_edit_close = '</td>';
//
//			/*   BUILD    */
//			if ($current_session == $details['lp_session']) {
//				if($details['lp_type']==1 || $details['lp_type']==2){
//					$dsp_build = '<a href="lp_controller.php?'.api_get_cidreq().'&amp;action=build&amp;lp_id='.$id.'"><img src="../img/wizard.gif" border="0" title="'.get_lang("Build").'"></a>&nbsp;';
//				} else {
//					$dsp_build = '<img src="../img/wizard_gray.gif" border="0" title="'.get_lang("Build").'">&nbsp;';
//				}
//			} else {
//				$dsp_build = '<img src="../img/wizard_gray.gif" border="0" title="'.get_lang("Build").'">&nbsp;';
//			}
//
//
//
//			/* VISIBILITY COMMAND */
//
//			if ($current_session == $details['lp_session']) {
//				if ($details['lp_visibility'] == 0)
//				{
//				    $dsp_visible =	"<a href=\"".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=1\">" .
//					"<img src=\"../img/invisible.gif\" border=\"0\" title=\"".get_lang('Show')."\" />" .
//					"</a>" .
//					"";
//				}
//				else
//				{
//					$dsp_visible =	"<a href='".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=0'>" .
//					"<img src=\"../img/visible.gif\" border=\"0\" title=\"".get_lang('Hide')."\" />" .
//					"</a>".
//					"";
//				}
//			} else {
//				$dsp_visible = '<img src="../img/invisible.gif" border="0" title="'.get_lang('Show').'" />';
//			}
//
//
//			/* PUBLISH COMMAND */
//
//			if ($current_session == $details['lp_session']) {
//				if ($details['lp_published'] == "i")
//				{
//				        $dsp_publish =	"<a href=\"".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_publish&new_status=v\">" .
//					"<img src=\"../img/invisible_LP_list.gif\" border=\"0\" title=\"".get_lang('_publish')."\" />" .
//					"</a>" .
//					"";
//				}
//				else
//				{
//					$dsp_publish =	"<a href='".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_publish&new_status=i'>" .
//					"<img src=\"../img/visible_LP_list.gif\" border=\"0\" title=\"".get_lang('_no_publish')."\" />" .
//					"</a>".
//					"";
//				}
//			} else {
//				$dsp_publish = '<img src="../img/invisible_LP_list.gif" border="0" title="'.get_lang('_no_publish').'" />';
//			}
//
//
//			/*  MULTIPLE ATTEMPTS    */
//			// we can't allow multiple attempts for a dokeos builtin course
//			if($details['lp_type'] != 1){
//				if ($current_session == $details['lp_session']) {
//					if($details['lp_prevent_reinit']==1){
//						$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
//								'<img src="../img/kaboodleloop_gray.gif" border="0" alt="Allow reinit" title="'.get_lang("AllowMultipleAttempts").'"/>' .
//								'</a>&nbsp;';
//					}else{
//						$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
//								'<img src="../img/kaboodleloop.gif" border="0" alt="Prevent reinit" title="'.get_lang("PreventMultipleAttempts").'"/>' .
//								'</a>&nbsp;';
//					}
//				} else {
//						$dsp_reinit = '<img src="../img/kaboodleloop_gray.gif" border="0" alt="Allow reinit" title="'.get_lang("AllowMultipleAttempts").'"/>';
//				}
//			}
//
//
//
//
//			/* FUll screen VIEW */
//
//			if ($current_session == $details['lp_session']) {
//
//				/* Default view mode settings (fullscreen/embedded) */
//				if($details['lp_view_mode'] == 'fullscreen'){
//					$dsp_default_view = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_view_mode&lp_id='.$id.'">' .
//							'<img src="../img/view_fullscreen.gif" border="0" alt="'.get_lang("ViewModeEmbedded").'" title="'.get_lang("ViewModeEmbedded").'"/>' .
//							'</a>&nbsp;';
//				}else{
//					$dsp_default_view = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_view_mode&lp_id='.$id.'">' .
//							'<img src="../img/view_choose.gif" border="0" alt="'.get_lang("ViewModeFullScreen").'" title="'.get_lang("ViewModeFullScreen").'"/>' .
//							'</a>&nbsp;';
//				}
//			} else {
//				if($details['lp_view_mode'] == 'fullscreen')
//					$dsp_default_view = '<img src="../img/view_fullscreen_na.gif" border="0" alt="'.get_lang("ViewModeEmbedded").'" title="'.get_lang("ViewModeEmbedded").'"/>';
//				else
//					$dsp_default_view = '<img src="../img/view_choose_na.gif" border="0" alt="'.get_lang("ViewModeEmbedded").'" title="'.get_lang("ViewModeFullScreen").'"/>';
//			}
//
//			/*  DEBUG  */
//
//			if($test_mode == 'test' or api_is_platform_admin()) {
//				if($details['lp_scorm_debug']==1){
//					$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
//							'<img src="../img/bug.gif" border="0" alt="'.get_lang("HideDebug").'" title="'.get_lang("HideDebug").'"/>' .
//							'</a>&nbsp;';
//				}else{
//					$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
//							'<img src="../img/bug_gray.gif" border="0" alt="'.get_lang("ShowDebug").'" title="'.get_lang("ShowDebug").'"/>' .
//							'</a>&nbsp;';
//				}
//		 	}
//
//
//		 	/* DELETE COMMAND */
//			if ($current_session == $details['lp_session']) {
//				$dsp_delete = "<a href=\"lp_controller.php?".api_get_cidreq()."&action=delete&lp_id=$id\" " .
//				"onClick=\"return confirmation('".addslashes($name)."');\">" .
//				"<img src=\"../img/delete.png\" border=\"0\" title=\"".get_lang('_delete_learnpath')."\" />" .
//				"</a>";
//			} else {
//				$dsp_delete = '<img src="../img/delete_na.gif" border="0" title="'.get_lang('_delete_learnpath').'" />';
//			}
//
//			// we can't allow multiple attempts for a dokeos builtin course
//			if($details['lp_type'] != 1){
//				if($details['lp_prevent_reinit']==1){
//					$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
//							'<img src="../img/kaboodleloop_gray.gif" border="0" alt="Allow reinit" title="'.get_lang("AllowMultipleAttempts").'"/>' .
//							'</a>&nbsp;';
//				}else{
//					$dsp_reinit = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_reinit&lp_id='.$id.'">' .
//							'<img src="../img/kaboodleloop.gif" border="0" alt="Prevent reinit" title="'.get_lang("PreventMultipleAttempts").'"/>' .
//							'</a>&nbsp;';
//				}
//			}
//			else {
//				$dsp_reinit = '';
//			}
//			if($details['lp_type']==1 || $details['lp_type']==2){
//				$dsp_build = '<a href="lp_controller.php?'.api_get_cidreq().'&amp;action=build&amp;lp_id='.$id.'"><img src="../img/wizard.gif" border="0" title="'.get_lang("Build").'"></a>&nbsp;';
//			}else{
//				$dsp_build = '<img src="../img/wizard_gray.gif" border="0" title="'.get_lang("Build").'">&nbsp;';
//			}
//			if($test_mode == 'test' or api_is_platform_admin())
//			{
//				if($details['lp_scorm_debug']==1){
//					$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
//							'<img src="../img/bug.gif" border="0" alt="'.get_lang("HideDebug").'" title="'.get_lang("HideDebug").'"/>' .
//							'</a>&nbsp;';
//				}else{
//					$dsp_debug = '<a href="lp_controller.php?'.api_get_cidreq().'&action=switch_scorm_debug&lp_id='.$id.'">' .
//							'<img src="../img/bug_gray.gif" border="0" alt="'.get_lang("ShowDebug").'" title="'.get_lang("ShowDebug").'"/>' .
//							'</a>&nbsp;';
//				}
//		 	}
//		 	/*   Export  */
//	    	if($details['lp_type']==1){
//				$dsp_disk =
//					"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id'>" .
//						"<img src=\"../img/cd.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
//					"</a>" .
//					"";
//			}elseif($details['lp_type']==2){
//				$dsp_disk =
//					"<a href='".api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id&export_name=".replace_dangerous_char($name,'strict').".zip'>" .
//						"<img src=\"../img/cd.gif\" border=\"0\" title=\"".get_lang('Export')."\">" .
//					"</a>" .
//					"";
//			}else{
//				$dsp_disk = "<img src=\"../img/cd_gray.gif\" border=\"0\" title=\"".get_lang('Export')."\">";
//			}
//
//			/* COLUMN ORDER	 */
//			// only active in a not session mode
//
//			if ($current_session == 0) {
//
//				if($details['lp_display_order'] == 1 && $max != 1)
//		    	{
//		    		$dsp_order .= '<td><a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_down&lp_id='.$id.'">' .
//		    				'<img  src="../img/arrow_down_0.gif" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
//		    				'</a><img src="../img/blanco.png" border="0" alt="" title="" /></td>';
//		    	}
//		    	elseif($current == $max-1 && $max != 1) //last element
//		    	{
//		    		$dsp_order .= '<td><img src="../img/blanco.png" border="0" alt="" title="" /><a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_up&lp_id='.$id.'">' .
//		    				'<img src="../img/arrow_up_0.gif" border="0" alt="'.get_lang("MoveUp").'" title="'.get_lang("MoveUp").'"/>' .
//		    				'</a></td>';
//		    	}
//		    	elseif($max == 1)
//		    	{
//		    		$dsp_order = '<td></td>';
//		    	}
//		    	else
//		    	{
//		    		$dsp_order .= '<td><a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_down&lp_id='.$id.'">' .
//		    				'<img src="../img/arrow_down_0.gif" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
//		    				'</a>&nbsp;';
//		    		$dsp_order .= '<a href="lp_controller.php?'.api_get_cidreq().'&action=move_lp_up&lp_id='.$id.'">' .
//		    				'<img src="../img/arrow_up_0.gif" border="0" alt="'.get_lang("MoveUp").'" title="'.get_lang("MoveUp").'"/>' .
//		    				'</a></td>';
//		    	}
//			}
//	    }	// end if($is_allowedToEdit)
//	    echo $dsp_line.$dsp_progress.$dsp_desc.$dsp_export.$dsp_edit.$dsp_build.$dsp_visible.$dsp_publish.$dsp_reinit.$dsp_default_view.$dsp_debug.$dsp_delete.$dsp_disk.$dsp_order;
//	    echo	"</tr>\n";
//		$current ++; //counter for number of elements treated
//	}	// end foreach ($flat_list)
//	//TODO print some user-friendly message if counter is still = 0 to tell nothing can be displayd yet
//}// end if ( is_array($flat_list)
//echo "</table>";
//
//// close the content div
//echo '</div>';
/*-----------------------------------------------------------------------------------------------------------------------*/


// bottom actions bar
echo '<div class="actions">';
echo '</div>';

// display the footer
Display::display_footer();
?>
