<?php // $Id: 2column.php,v 1.5 2006/08/10 14:34:54 pcool Exp $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	For licensing terms, see "dokeos_license.txt"

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	http://www.dokeos.com
==============================================================================
*/

/**
==============================================================================
*                  HOME PAGE FOR EACH COURSE
*
*	This page, included in every course's index.php is the home
*	page. To make administration simple, the teacher edits his
*	course from the home page. Only the login detects that the
*	visitor is allowed to activate, deactivate home page links,
*	access to the teachers tools (statistics, edit forums...).
*
*	@package dokeos.course_home
==============================================================================
*/

// header
Display::display_header($course_title, "Home");


//statistics
if (!isset($coursesAlreadyVisited[$_cid])) {
	event_access_course();
	$coursesAlreadyVisited[$_cid] = 1;
	api_session_register('coursesAlreadyVisited');
}

// database table definition
$tool_table = Database::get_course_table(TABLE_TOOL_LIST);

$temps = time();
$reqdate = "&reqdate=$temps";


//display course title for course home page (similar to toolname for tool pages)
//echo '<h3>'.api_display_tool_title($nameTools) . '</h3>';

// introduction section
Display::display_introduction_section(TOOL_COURSE_HOMEPAGE, array(
		'CreateDocumentWebDir' => api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document/',
		'CreateDocumentDir' => 'document/',
		'BaseHref' => api_get_path('WEB_COURSE_PATH').api_get_course_path().'/'
	)
);

// action handling
if (api_is_allowed_to_edit(null,true))
{
	// display message to confirm that a tool must be hidden from the list of available tools (visibility 0,1->2)
	if($_GET['remove'])
	{
		$msgDestroy=get_lang('DelLk').'<br />';
		$msgDestroy.='<a href="'.api_get_self().'">'.get_lang('No').'</a>&nbsp;|&nbsp;';
		$msgDestroy.='<a href="'.api_get_self().'?destroy=yes&amp;id='.$_GET["id"].'">'.get_lang('Yes').'</a>';
		Display :: display_confirmation_message($msgDestroy,false);
	}
	// remove tool (visibility 0,1->2)
	elseif ($_GET['destroy'])
	{
		change_tool_visibility($_GET['id'],2);
	}
	// make tool invisible (visibility 1,2->0)
	elseif ($_GET['hide'])
	{
		change_tool_visibility($_GET['id'],0);
		Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
	}
	// make tool visible (visibility 0,2->1)
	elseif ($_GET['restore'])
	{
		change_tool_visibility($_GET['id'],1);
		Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
	}
}

// work with data post askable by admin of course

if (api_is_platform_admin())
{
	// Show message to confirm that a tools must be hide from available tools
	// visibility 0,1->2
	if($_GET["askDelete"])
	{
		?>
			<div id="toolhide">
			<?php echo get_lang("DelLk"); ?>
			<br />&nbsp;&nbsp;&nbsp;
			<a href="<?php echo api_get_self(); ?>"><?php echo get_lang("No"); ?></a>&nbsp;|&nbsp;
			<a href="<?php echo api_get_self(); ?>?delete=yes&id=<?php echo $_GET["id"]; ?>"><?php echo get_lang("Yes"); ?></a>
			</div>
		<?php
	}

	/*
	 * Process hiding a tools from available tools.
	 * visibility=2 are only view  by Dokeos Administrator visibility 0,1->2
	 */

	elseif (isset($_GET["delete"]) && $_GET["delete"])
	{
		Database::query("DELETE FROM $tool_table WHERE id='$id' AND added_tool=1",__FILE__,__LINE__);
	}
}

/*
-----------------------------------------------------------
	Tools for everybody
-----------------------------------------------------------
*/
/*echo '<div class="actions" style="width:80%;margin: 25px auto 10px;">';
echo "<table width=\"100%\">";
show_tools(TOOL_PUBLIC);
echo "</table>";
echo "</div>";*/

echo '<div class="section main_activity"><span class="sectiontitle">'.get_lang('Basic').'</span><table>';
show_tools(TOOL_PUBLIC);
echo '</table></div>';


/*
-----------------------------------------------------------
	Tools for course admin only
-----------------------------------------------------------
*/
/*if (api_is_allowed_to_edit(null,true) && !api_is_coach())
{
	echo '<div class="actions" style="width:80%;margin: 25px auto 10px;">';
	echo	"<span class=\"viewcaption\">";
	echo	get_lang("CourseAdminOnly");
	echo	"</span>";
	echo	"<table width=\"100%\">";
	show_tools(TOOL_COURSE_ADMIN);

	// inactive tools - hidden (grey) link
	echo	"<tr><td colspan=\"4\"><hr style='color:\"#4171B5\"' noshade=\"noshade\" size=\"1\" /></td></tr>\n",
			"<tr>\n",
			"<td colspan=\"4\">\n",
			"<div style=\"margin-bottom: 10px;\"><font color=\"#808080\">\n",get_lang("InLnk"),"</font></div>",
			"</td>\n",
			"</tr>\n";
	show_tools(TOOL_PUBLIC_BUT_HIDDEN);
	echo	"</table>";
	echo	"</div> ";
}*/

if (api_is_allowed_to_edit(null,true) && !api_is_course_coach())
{
echo '<div class="section main_activity"><span class="sectiontitle">'.get_lang('CourseAdminOnly').'</span><table>';
show_tools(TOOL_COURSE_ADMIN);
echo	"<tr><td colspan=\"4\"><hr style='color:\"#4171B5\"' noshade=\"noshade\" size=\"1\" /></td></tr>\n",
			"<tr>\n",
			"<td colspan=\"4\">\n",
			"<div style=\"margin-bottom: 10px;\"><font color=\"#808080\">\n",get_lang("InLnk"),"</font></div>",
			"</td>\n",
			"</tr>\n";
	show_tools(TOOL_PUBLIC_BUT_HIDDEN);
echo '</table></div>';
}

/*
-----------------------------------------------------------
	Tools for platform admin only
-----------------------------------------------------------
*/
//if (api_is_platform_admin() && api_is_allowed_to_edit(null,true) && !api_is_coach())
//{
	?>
	<!--	<div class="actions" style="width:80%;margin: 25px auto 10px;">
		<span class="viewcaption"><?php echo get_lang("PlatformAdminOnly"); ?></span>
		<table width="100%">
		<?php show_tools(TOOL_PLATFORM_ADMIN);	?>
		</table>
		</div>-->
	<?php
//}
if (api_is_platform_admin() && api_is_allowed_to_edit(null,true) && !api_is_course_coach())
{
echo '<div class="section main_activity"><span class="sectiontitle">'.get_lang('PlatformAdminOnly').'</span><table>';
show_tools(TOOL_PLATFORM_ADMIN);
echo '</table></div>';
}
?>