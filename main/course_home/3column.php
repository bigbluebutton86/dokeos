<?php // $Id: 3column.php,v 1.2 2006/08/10 14:26:12 pcool Exp $
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

$hide = isset($_GET['hide']) && $_GET['hide'] == 'yes' ? 'yes' : null;
$restore = isset($_GET['restore']) && $_GET['restore'] == 'yes' ? 'yes' : null;
$id  = isset($_GET['id']) ? intval($_GET['id']) : null;

// Database table definitions
$TABLE_TOOLS = Database::get_main_table(TABLE_MAIN_COURSE_MODULE);
$TBL_ACCUEIL = Database::get_course_table(TABLE_TOOL_LIST);

// action handling
if (api_is_allowed_to_edit(null,true))
{
	// display message to confirm that a tool must be hidden from the list of available tools (visibility 0,1->2)
	if($remove)
	{
		$sql = "SELECT * FROM $TBL_ACCUEIL WHERE id='".Database::escape_string($id)."'";
		$result = Database::query($sql,__FILE__,__LINE__);
		$toolsRow = Database::fetch_array($result);
		$tool_name = htmlspecialchars($toolsRow['name'] != "" ? $toolsRow['name'] : $toolsRow['link'],ENT_QUOTES,$charset);
		if($toolsRow['img'] != "external.gif")
		{
			$toolsRow['link']=api_get_path(WEB_CODE_PATH).$toolsRow['link'];
		}
		$toolsRow['image']=api_get_path(WEB_CODE_PATH)."img/".$toolsRow['image'];

		echo 	"<br><br><br>\n";
		echo	"<table class=\"message\" width=\"70%\" align=\"center\">\n",
				"<tr><td width=\"7%\" align=\"center\">\n",
			   	"<a href=\"".$toolsRow['link']."\">".Display::return_icon($toolsRow['image'], get_lang('Delete')),"</a></td>\n",
				"<td width=\"28%\" height=\"45\"><small>\n",
				"<a href=\"".$toolsRow['link']."\">".$tool_name."</a></small></td>\n";
		echo	"<td align=\"center\">\n",
				"<font color=\"#ff0000\">",
				"&nbsp;&nbsp;&nbsp;",
				"<strong>",get_lang('DelLk'),"</strong>",
				"<br>&nbsp;&nbsp;&nbsp;\n",
				"<a href=\"".api_get_self()."\">",get_lang('No'),"</a>\n",
				"&nbsp;|&nbsp;\n",
				"<a href=\"".api_get_self()."?destroy=yes&amp;id=$id\">",get_lang('Yes'),"</a>\n",
				"</font></td></tr>\n",
				"</table>\n";
		echo 	"<br><br><br>\n";

	}
	// remove tool (visibility 0,1->2)
	elseif ($destroy)
	{
		change_tool_visibility($id,0);
	}
	// make tool invisible (visibility 1,2->0)
	elseif ($hide)
	{
		change_tool_visibility($id,0);
		Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
	}
	// make tool visible (visibility 0,2->1)
	elseif ($restore)
	{
		change_tool_visibility($id,1);
		Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
	}

	/*
	 * editing "apparance" of  a tools  on the course Home Page.
	 */

	elseif (isset ($update) && $update)
	{
		$result 	= Database::query("SELECT * FROM $TBL_ACCUEIL WHERE id=$id");
		$toolsRow 	= Database::fetch_array($result);
		$racine		= $_configuration['root_sys']."/".$currentCourseID."/images/";
		$chemin		= $racine;
		$name	= $toolsRow[1];
		$image		= $toolsRow[3];

		echo	"<tr>\n",
				"<td colspan=\"4\">\n",
				"<table>\n",
				"<tr>\n",
				"<td>\n",
				"<form method=\"post\" action=\"".api_get_self()."\">\n",
				"<input type=\"hidden\" name=\"id\" value=\"$id\">\n",
				"Image : ".Display::return_icon($image)."\n",
				"</td>\n",
				"<td>\n",
				"<select name=\"image\">\n",
				"<option selected>",$image,"</option>\n";

		if ($dir = @opendir($chemin))
		{
			while($file = readdir($dir))
			{
				if($file==".." OR $file==".")
				{
					unset($file);
				}

				echo "<option>",$file,"</option>\n";

			}

			closedir($dir);
		}

		echo	"</select>\n",
				"</td>\n",
				"</tr>\n",
				"<tr>\n",
				"<td>",get_lang('NameOfTheLink')," : </td>\n",
				"<td><input type=\"text\" name=\"name\" value=\"",$name,"\"></td>\n",
				"</tr>\n",
				"<tr>\n",
				"<td>Lien :</td>\n",
				"<td><input type=\"text\" name=\"link\" value=\"",$link,"\"></td>\n",
				"</tr>\n",
				"<tr>\n",
				"<td colspan=\"2\"><input type=\"submit\" name=\"submit\" value=\"",get_lang('Ok'),"\"></td>\n",
				"</tr>\n",
				"</form>\n",
				"</table>\n",
				"</td>\n",
				"</tr>\n";
	}
}


// work with data post askable by admin of  course

if ($is_platformAdmin && api_is_allowed_to_edit(null,true) && !api_is_course_coach())
{
	// Show message to confirm that a tools must be hide  from aivailable tools
	// visibility 0,1->2
	if($askDelete)
	{
		echo	"<table align=\"center\"><tr>\n",
				"<td colspan=\"4\">\n",
				"<br><br>\n",
				"<font color=\"#ff0000\">",
				"&nbsp;&nbsp;&nbsp;",
				"<strong>",get_lang('DelLk'),"</strong>",
				"<br>&nbsp;&nbsp;&nbsp;\n",
				"<a href=\"".api_get_self()."\">",get_lang('No'),"</a>\n",
				"&nbsp;|&nbsp;\n",
				"<a href=\"".api_get_self()."?delete=yes&amp;id=$id\">",get_lang('Yes'),"</a>\n",
				"</font>\n",
				"<br><br><br>\n",
				"</td>\n",
				"</tr>",
				"</table>\n";
	} // if remove

	/*
	 * Process hiding a tools from aivailable tools.
	 * visibility=2 are only viewed by Dokeos Administrator visibility 0,1->2
	 */

	elseif (isset($delete) && $delete)
	{
		Database::query("DELETE FROM $TBL_ACCUEIL WHERE id='".Database::escape_string($id)."' AND added_tool=1");
	}
}


//echo "<div class='actions' style='width:100%;margin: 25px auto 10px;'><table class=\"item\" align=\"center\" border=\"0\" width=\"95%\">\n";

/*
-----------------------------------------------------------
	Tools for everybody
-----------------------------------------------------------
*/
/*echo	"<tr>\n<td colspan=\"6\">&nbsp;</td>\n</tr>\n";
echo	"<tr>\n<td colspan=\"6\">";
showtools2('Basic');
showtools2('External');
echo 	"</td>\n</tr>\n";*/

echo '<div class="section main_activity"><span class="sectiontitle">'.get_lang('Basic').'</span><table>';
showtools2('Basic');
showtools2('External');
echo '</table></div>';


/*
-----------------------------------------------------------
	Tools for course admin only
-----------------------------------------------------------
*/
/*if (api_is_allowed_to_edit(null,true) && !api_is_coach())
{
	echo	"<tr><td colspan=\"6\"><hr noshade size=\"1\" /></td></tr>\n",
			"<tr>\n","<td colspan=\"6\">\n",
			"<font color=\"#F66105\">\n",get_lang('CourseAdminOnly'),"</font>\n",
			"</td>\n","</tr>\n";
	echo	"<tr>\n<td colspan=\"6\">";
	showtools2('courseAdmin');
	echo 	"</td>\n</tr>\n";
}*/

if (api_is_allowed_to_edit(null,true) && !api_is_course_coach())
{
echo '<div class="section main_activity"><span class="sectiontitle">'.get_lang('CourseAdminOnly').'</span><table>';
showtools2('courseAdmin');
echo '</table></div>';
}


/*
-----------------------------------------------------------
	Tools for platform admin only
-----------------------------------------------------------
*/
/*if ($is_platformAdmin && api_is_allowed_to_edit(null,true) && !api_is_coach())
{
	echo	"<tr>","<td colspan=\"6\">",
			"<hr noshade size=\"1\" />",
			"</td>","</tr>\n",
			"<tr>\n","<td colspan=\"6\">\n",
			"<font color=\"#F66105\" >",get_lang('PlatformAdminOnly'),"</font>\n",
			"</td>\n","</tr>\n";
	echo	"<tr>\n<td colspan=\"6\">";
	showtools2('platformAdmin');
	echo 	"</td>\n</tr>\n";
}

echo	"</table></div>\n";*/

if ($is_platformAdmin && api_is_allowed_to_edit(null,true) && !api_is_course_coach())
{
echo '<div class="section main_activity"><span class="sectiontitle">'.get_lang('PlatformAdminOnly').'</span><table>';
showtools2('platformAdmin');
echo '</table></div>';
}
?>
