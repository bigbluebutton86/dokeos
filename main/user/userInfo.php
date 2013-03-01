<?php

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.user
* 	@todo cleanup the variables and the duplicates of the variables: 
*			$uInfo = $editMainUserInfo = Security::remove_XSS($_REQUEST['editMainUserInfo']) 
* 			$currentCourse = $currentCourseID = $courseCode = $currentCourseID = $_course['sysCode'];
==============================================================================
*/

// name of the language file that needs to be included
$language_file = array ('registration', 'userInfo');

// including the global Dokeos file
require_once '../inc/global.inc.php';

// the section (for the tabs)
$this_section = SECTION_COURSES;

// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';

// Additional javascript
$htmlHeadXtra[] = '<script type="text/javascript">
function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}
</script>';

$editMainUserInfo = Security::remove_XSS($_REQUEST['editMainUserInfo']);
$uInfo = $editMainUserInfo;

api_protect_course_script(true);
if(api_is_anonymous())
{
	api_not_allowed(true);
}

//prepare variables used in userInfoLib.php functions
$TBL_USERINFO_DEF 	= Database :: get_course_table(TABLE_USER_INFO);
$TBL_USERINFO_CONTENT 	= Database :: get_course_table(TABLE_USER_INFO_CONTENT);

// breadcrumbs
$interbreadcrumb[] = array ('url' => 'user.php', 'name' => get_lang('Users'));

// display the header
Display :: display_header($nameTools, "User");

// display the tool title
// api_display_tool_title(get_lang("Users"));


$currentCourse = $currentCourseID;

/*
 * data  found  in settings  are :
 *	$uid
 *	$isAdmin
 *	$isAdminOfCourse
 *	$_configuration['main_database']
 *	$currentCourseID
 */

//$userIdViewed = $uInfo; // Id of the user we want to view coming from the user.php

//get information about one user
$userIdViewed = Security::remove_XSS($_REQUEST['uInfo']);

/*
-----------------------------------------------------------
	Connection layer between Dokeos and the current script
-----------------------------------------------------------
*/

$mainDB = $_configuration['main_database'];
$courseCode = $currentCourseID = $_course['sysCode'];
$tbl_coursUser = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

$userIdViewer = $_user['user_id']; // id fo the user currently online
//$userIdViewed = $_GET['userIdViewed']; // Id of the user we want to view

$allowedToEditContent = ($userIdViewer == $userIdViewed) || $is_platformAdmin;
$allowedToEditDef = api_is_allowed_to_edit();
$is_allowedToTrack = api_is_allowed_to_edit() && $_configuration['tracking_enabled'];

// Library connection
include ("userInfoLib.php");

/*
==============================================================================
	   FUNCTIONS
==============================================================================
*/

/*
==============================================================================
	   COMMANDS SECTION
==============================================================================
*/

$displayMode = "viewContentList";
$removeDef =  Security::remove_XSS($_GET['removeDef']);
$editDef = Security::remove_XSS($_GET['editDef']);
$moveUpDef = Security::remove_XSS($_GET['moveUpDef']);
$moveDownDef = Security::remove_XSS($_GET['moveDownDef']);

if ($allowedToEditDef)
{
	if (!empty($_POST['submitDef']))
	{
		if (!empty($_POST['id']))
		{
			edit_cat_def($_POST['id'], $_POST['title'], $_POST['comment'], $_POST['nbline']);
		}
		else
		{
			create_cat_def($_POST['title'], $_POST['comment'], $_POST['nbline']);
		}

		$displayMode = "viewDefList";
	}
	elseif (!empty($_GET['removeDef']))
	{
		remove_cat_def($_GET['removeDef'], true);
		$displayMode = "viewDefList";
	}
	elseif (!empty($_GET['editDef']))
	{
		$displayMode = "viewDefEdit";
	}
	elseif (!empty ($_POST['addDef']))
	{
		$displayMode = "viewDefEdit";
	}
	elseif (!empty($_GET['moveUpDef']))
	{
		move_cat_rank($_GET['moveUpDef'], "up");
		$displayMode = "viewDefList";
	}
	elseif (!empty($_GET['moveDownDef']))
	{
		move_cat_rank($_GET['moveDownDef'], "down");
		$displayMode = "viewDefList";
	}
	elseif (!empty($_POST['viewDefList']))
	{
		$displayMode = "viewDefList";
	}
	elseif (!empty($_GET['editMainUserInfo']))
	{
		$userIdViewed = strval(intval($_GET['editMainUserInfo']));
		$displayMode = "viewMainInfoEdit";
	}
	elseif (!empty($_REQUEST['submitMainUserInfo']))
	{
		/*
		if (isset ($_REQUEST['submitMainUserInfo']))
		{
		*/
			$userIdViewed = strval(intval($_REQUEST['submitMainUserInfo']));

			if(!empty($_POST['promoteCourseAdmin']) && $_POST['promoteCourseAdmin']){
				$userProperties['status'] = 1;
			}else{
				$userProperties['status'] = 5;
			}

			if(!empty($_POST['promoteTutor']) && $_POST['promoteTutor']){
				$userProperties['tutor'] = 1;
			}else{
				$userProperties['tutor'] = 0;
			}

			$userProperties['role'] = $_POST['role'];

			update_user_course_properties($userIdViewed, $courseCode, $userProperties);

			$displayMode = "viewContentList";
	}
}

// COMMON COMMANDS

if ($allowedToEditContent)
{
	if (isset($_POST['submitContent']))
	{
		if ($_POST['cntId']) // submit a content change
		{
			edit_cat_content($_POST['catId'], $userIdViewed, $_POST['content'], $_SERVER['REMOTE_ADDR']);

		}
		else // submit a totally new content
		{
			fill_new_cat_content($_POST['catId'], $userIdViewed, $_POST['content'], $_SERVER['REMOTE_ADDR']);

		}

		$displayMode = "viewContentList";
	}
	elseif (!empty($_GET['editContent']))
	{
		$displayMode = "viewContentEdit";
		$userIdViewed = $userIdViewed;
	}
}

// Actions
echo '<div class="actions">';
$return_back = '<a href="user.php?'.api_get_cidreq().'&amp;origin='.$origin.'">'.Display::return_icon('pixel.gif',get_lang('BackUser'),array('class'=>'toolactionplaceholdericon toolactionback')).get_lang('BackUser').'</a>';
if (isset($_GET['from_page']) && $_GET['from_page']=='wiki') {
  $return_back = '<a href="../wiki/index.php?'.api_get_cidreq().'&action=allpages&group_id=">'.Display::return_icon('wiki.png',get_lang('Back')).get_lang('Back').'</a>';
}
echo $return_back;
if (api_is_allowed_to_edit()) {
	if (!is_numeric($_GET['editMainUserInfo'])) {
		echo '<a href="userInfo.php?'.api_get_cidreq().'&amp;origin='.$origin.'&amp;editMainUserInfo='.$userIdViewed.'">'.Display::return_icon('pixel.gif',get_lang('EditUser'),array('class'=>'toolactionplaceholdericon tooledithome')).get_lang('EditUser').'</a>';
	} else {
		echo '<a href="userInfo.php?'.api_get_cidreq().'&amp;origin='.$origin.'&amp;uInfo='.$userIdViewed.'">'.Display::return_icon('pixel.gif',get_lang('ViewUser'),array('class'=>'toolactionplaceholdericon toolactionunknown')).get_lang('ViewUser').'</a>';
	}
	echo '<a href="../mySpace/myStudents.php?'.api_get_cidreq().'&amp;origin=user_course&amp;student='.$userIdViewed.'&amp;details=true&amp;course='.$_course['id'].'">'.Display::return_icon('pixel.gif',get_lang('UserStatistics'),array('class'=>'toolactionplaceholdericon toolactionstatistics')).get_lang('UserStatistics').'</a>';
}
echo '</div>';

echo '<div id="content">';

if ($displayMode == "viewDefEdit")
{
	/*>>>>>>>>>>>> CATEGORIES DEFINITIONS : EDIT <<<<<<<<<<<<*/

	$catToEdit = get_cat_def($_GET['editDef']);
	$edit_heading_form = new FormValidator('edit_heading_form');
	$edit_heading_form->addElement('hidden', 'id');
	$edit_heading_form->add_textfield('title', get_lang('Title'));
	$edit_heading_form->addElement('textarea', 'comment', get_lang('Comment'), array ('cols' => 60, 'rows' => 4));
	$possible_line_nrs[1] = '1 '.get_lang('Line');
	$possible_line_nrs[3] = '3 '.get_lang('Lines');
	$possible_line_nrs[5] = '5 '.get_lang('Lines');
	$possible_line_nrs[10] = '10 '.get_lang('Lines');
	$possible_line_nrs[15] = '15 '.get_lang('Lines');
	$edit_heading_form->addElement('select', 'nbline', get_lang('LineNumber'), $possible_line_nrs);
	$edit_heading_form->addElement('submit', 'submitDef', get_lang('Ok'));
	$edit_heading_form->setDefaults($catToEdit);
	$edit_heading_form->display();
}
elseif ($displayMode == "viewDefList")
{
	/*>>>>>>>>>>>> CATEGORIES DEFINITIONS : LIST <<<<<<<<<<<<*/

	$catList = get_cat_def_list();

	if ($catList)
	{

		foreach ($catList as $thisCat)
		{
			// displays Title and comments

			echo "<p>", "<b>".htmlize($thisCat['title'])."</b><br>\n", "<i>".htmlize($thisCat['comment'])."</i>\n", "</p>";

			// displays lines

			echo "<blockquote>\n", "<font color=\"gray\">\n";

			for ($i = 1; $i <= $thisCat['nbline']; $i ++)
			{
				echo "<br>__________________________________________\n";
			}

			echo "</font>\n", "</blockquote>\n";

			// displays commands

			echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&removeDef=", $thisCat['catId'], "\">", "<img src=\"../img/delete.png\" border=\"0\" alt=\"".get_lang('Remove')."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">", "</a>", "<a href=\"".api_get_self()."?".api_get_cidreq()."&editDef=", $thisCat['catId'], "\">", Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit')).get_lang('Edit')."\" />", "</a>", "<a href=\"".api_get_self()."?".api_get_cidreq()."&moveUpDef=", $thisCat['catId'], "\">", "<img src=\"../img/up.gif\" border=\"0\" alt=\"".get_lang('MoveUp')."\">", "</a>", "<a href=\"".api_get_self()."?".api_get_cidreq()."&moveDownDef=", $thisCat['catId'], "\">", "<img src=\"../img/down.gif\" border=\"0\" alt=\"".get_lang('MoveDown')."\">", "</a>\n";
		} // end for each

	} // end if ($catList)

	echo "<center>\n",
			"<form method=\"post\" action=\"".api_get_self()."\">",
			"<input type=\"submit\" name=\"addDef\" value=\"".get_lang('AddNewHeading')."\" />",
			"</form>\n",
			"<center>\n";
}
elseif ($displayMode == "viewContentEdit")
{
	/*>>>>>>>>>>>> CATEGORIES CONTENTS : EDIT <<<<<<<<<<<<*/

	$catToEdit = get_cat_content($userIdViewed, $_GET['editContent']);
	$content_heading_form = new FormValidator('content_heading_form');
	$content_heading_form->addElement('hidden', 'cntId');
	$content_heading_form->addElement('hidden', 'catId');
	$content_heading_form->addElement('hidden', 'uInfo');
	$content_heading_form->addElement('static', null, $catToEdit['title'], htmlize($catToEdit['comment']));
	if ($catToEdit['nbline'] == 1)
	{
		$content_heading_form->addElement('text', 'content', null, array ('size' => 80));
	}
	else
	{
		$content_heading_form->addElement('textarea', 'content', null, array ('cols' => 60, 'rows' => $catToEdit['nbline']));
	}
	$content_heading_form->addElement('submit', 'submitContent', get_lang('Ok'));
	$defaults = $catToEdit;
	$defaults['cntId'] = $catToEdit['contentId'];
	$defaults['uInfo'] = $userIdViewed;
	$content_heading_form->setDefaults($defaults);
	$content_heading_form->display();
}
elseif ($displayMode == "viewMainInfoEdit")
{

	/*>>>>>>>>>>>> CATEGORIES MAIN INFO : EDIT <<<<<<<<<<<<*/

	$mainUserInfo = get_main_user_info($userIdViewed, $courseCode);

	if ($mainUserInfo)
	{
		($mainUserInfo['status'] == 1) ? $courseAdminChecked = "checked" : $courseAdminChecked = "";
		($mainUserInfo['tutor_id'] == 1) ? $tutorChecked = "checked" : $tutorChecked = "";

		$image_array=UserManager::get_user_picture_path_by_id($userIdViewed,'web',false,true);
		// get the path,width and height from original picture
		$big_image = $image_array['dir'].'big_'.$image_array['file'];
		$big_image_size = api_getimagesize($big_image);
		$big_image_width= $big_image_size[0];
		$big_image_height= $big_image_size[1];
		$url_big_image = $big_image.'?rnd='.time();

		if ($image_array['file']=='unknown.jpg') {
		echo '<img src="'.$image_array['dir'].$image_array['file'].'" border="1">';
		} else {
		echo '<input type="image" src="'.$image_array['dir'].$image_array['file'].'" onclick="return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
		}

		//"<td>", get_lang('Tutor'), "</td>\n",
		echo "<form action=\"".api_get_self()."\" method=\"post\">\n",
				"<input type=\"hidden\" name=\"submitMainUserInfo\" value=\"$userIdViewed\" />\n",
				"<table width=\"80%\" border=\"0\">",
					"<tr align=\"center\" bgcolor=\"#E6E6E6\">\n",
						"<td align=\"left\">", get_lang('Name'), "</td>\n",
						"<td width=\"100px\" align=\"left\">", get_lang('Description'), "</td>\n",
						//"<td>", get_lang('Tutor'), "</td>\n",
						"<td>", get_lang('CourseManager'), "</td>\n",
					"</tr>\n",
					"<tr align=\"center\">",
						"<td align=\"left\"><b>", htmlize(api_get_person_name($mainUserInfo['firstName'], $mainUserInfo['lastName'])), "</b></td>\n",
						"<td align=\"left\"><input type=\"text\" name =\"role\" value=\"", $mainUserInfo['role'], "\" maxlength=\"40\" /></td>";
						//"<td><input class=\"checkbox\" type=\"checkbox\" name=\"promoteTutor\" value=\"1\" ", $tutorChecked, " /></td>";

		if (!($is_courseAdmin && $_user['user_id'] == $userIdViewed))
		{
			echo "<td><input class=\"checkbox\" type=\"checkbox\" name=\"promoteCourseAdmin\" value=\"1\"", $courseAdminChecked, " /></td>\n";
		}
		else
		{
			echo "<td>", get_lang('CourseManager'), "</td>\n";

		}

		echo "<td><button class=\"save\" type=\"submit\" name=\"submit\">".get_lang('SaveChanges')."</button></td>\n", "</tr>", "</table>", "</form>\n";

		echo "<p>".Display :: encrypted_mailto_link($mainUserInfo['email'], $mainUserInfo['email'])."</p>";

				if (api_get_setting('extended_profile') == 'true')
				{
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyCompetences').'</strong></div><div>'.$mainUserInfo['competences'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div><div>'.$mainUserInfo['diplomas'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyTeach').'</strong></div><div>'.$mainUserInfo['teach'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div><div>'.$mainUserInfo['openarea'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyProductions').'</strong></div><div>'.UserManager::build_production_list($mainUserInfo['user_id']).'</div>';
				}

	}
	else
	{
		Display :: display_normal_message(get_lang('ThisStudentIsSubscribeThroughASession'));
	}
}
elseif ($displayMode == "viewContentList") // default display
{
	/*>>>>>>>>>>>> CATEGORIES CONTENTS : LIST <<<<<<<<<<<<*/

	$virtual_course_code = $_GET["virtual_course"];
	if (isset ($virtual_course_code))
	{
		$courseCode = $virtual_course_code;
		//not supported yet: editing users of virtual courses
		$allowedToEditDef = false;
	}

	$mainUserInfo = get_main_user_info($userIdViewed, $courseCode);

	if ($mainUserInfo)
	{
		$image_array=UserManager::get_user_picture_path_by_id($userIdViewed,'web',false,true);
		// get the path,width and height from original picture
		$big_image = $image_array['dir'].'big_'.$image_array['file'];
		$big_image_size = api_getimagesize($big_image);
		$big_image_width= $big_image_size[0];
		$big_image_height= $big_image_size[1];
		$url_big_image = $big_image.'?rnd='.time();

		if ($image_array['file']=='unknown.jpg') {
		echo '<img src="'.$image_array['dir'].$image_array['file'].'" border="1">';
		} else {
		echo '<input type="image" src="'.$image_array['dir'].$image_array['file'].'" onclick="return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
		}

		// is the user online ?
		$statistics_database = Database :: get_statistic_database();	
		$users_online = WhoIsOnline($userIdViewed, $statistics_database, 30);
		foreach ($users_online as $online) {
			if (in_array($userIdViewed, $online)) {	
				
				
				$online = Display::return_icon('online.gif', get_lang('OnLine'),array('style'=>'with="8"; height="8"'));
				break;
			}
			else
			{
				$online ='';
			}
				
		}

		//DISPLAY TABLE HEADING
		if ($origin == 'learnpath') { $allowedToEditDef=false; $is_allowedToTrack=false; }

				//"<td>",get_lang('Tutor'),"</td>\n",
		echo	"<table width=\"80%\" border=\"0\">",

				"<tr align=\"center\" bgcolor=\"#E6E6E6\">\n",
				"<td align=\"left\">",get_lang('Name'),"</td>\n",
				"<td width=\"100px\" align=\"left\">",get_lang('Description'),"</td>\n",
				//"<td>",get_lang('Tutor'),"</td>\n",
				"<td>",get_lang('CourseManager'),"</td>\n",
				($allowedToEditDef?"<td>".get_lang('Edit')."</td>\n":""),
                ($is_allowedToTrack?"<td>".get_lang('Tracking')."</td>\n":""),
				"</tr>\n",

				"<tr align=\"center\">\n",

				"<td  align=\"left\"><b>".$online.' '.htmlize(api_get_person_name($mainUserInfo['firstName'], $mainUserInfo['lastName']))."</b></td>\n",
				"<td  align=\"left\">".htmlize($mainUserInfo['role'])."</td>";

				//DISPLAY TABLE CONTENT

				// deprecated feature
				if ($mainUserInfo['tutor_id'] == 1)
				{
					//echo "<td>",get_lang('Tutor'),"</td>\n";
				}
				else
				{
					echo "<td> - </td>\n";
				}

				if ($mainUserInfo['status'] == 1)
				{
					echo "<td>",get_lang('CourseManager'),"</td>";
				}
				else
				{
					echo "<td> - </td>\n";
				}

				if ($allowedToEditDef)
				{
					echo	"<td>",
							"<a href=\"".api_get_self()."?".api_get_cidreq()."&editMainUserInfo=$userIdViewed\">",
                            Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit')),
							"</a>",
							"</td>";
				}
                                if ($is_allowedToTrack) {
                                   echo	"<td>",
                                    "<a href=\"../mySpace/myStudents.php?".api_get_cidreq()."&origin=user_course&student=$userIdViewed&details=true&course=".$_course['id']."\">",
                                    Display::return_icon('pixel.gif',get_lang('Tracking').' : '.$userIdViewed,array('class'=>'actionplaceholdericon actiontracking')),             
                                    "</a>",
                                    "</td>";
                                  
                                }
				echo "</tr>",
				"</table>";

				if (api_get_setting("show_email_addresses") == "true")
				{
					echo "<p>". Display::encrypted_mailto_link($mainUserInfo['email'],$mainUserInfo['email']). "</p>";
				}
				else
				{
					if (api_is_allowed_to_edit())
					{
						echo "<p>". Display::encrypted_mailto_link($mainUserInfo['email'],$mainUserInfo['email']). "</p>";
					}
				}

				if (api_get_setting('extended_profile') == 'true')
				{
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyCompetences').'</strong></div><div>'.$mainUserInfo['competences'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div><div>'.$mainUserInfo['diplomas'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyTeach').'</strong></div><div>'.$mainUserInfo['teach'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div><div>'.$mainUserInfo['openarea'].'</div>';
					echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyProductions').'</strong></div><div>'.UserManager::build_production_list($mainUserInfo['user_id']).'</div>';
				}
	}
	else{
		Display :: display_normal_message(get_lang('ThisStudentIsSubscribeThroughASession'));
	}

	if (api_get_setting('allow_user_headings') == 'true' && $allowedToEditDef) // only course administrators see this line
	{
		echo	"<div align=right>",
				"<form method=\"post\" action=\"".api_get_self()."\">",
				get_lang('CourseAdministratorOnly')," : ",
				"<input type=\"submit\" name=\"viewDefList\" value=\"".get_lang('DefineHeadings')."\" />",
				"</form>",
				"<hr noshade size=\"1\" style=\"color:#99CCFF\">",
				"</div>\n";
	}

	$catList = get_course_user_info($userIdViewed);

	if ($catList)
	{
		foreach ($catList as $thisCat)
		{
			// Category title

			echo	"<p><b>",$thisCat['title'],"</b></p>\n";

			// Category content

			echo	"<blockquote>\n";

			if ($thisCat['content'])
			{
				echo htmlize($thisCat['content'])."\n";
			}
			else
			{
				echo "....";
			}

			// Edit command

			if ($allowedToEditContent)
			{
				echo	"<br><br>\n",
						"<a href=\"".api_get_self()."?".api_get_cidreq()."&editContent=",$thisCat['catId'],"&uInfo=",$userIdViewed,"\">",
						//"<img src=\"../img/edit.png\" border=\"0\" alt=\"edit\">",
                        Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit')),
						"</a>\n";
			}

			echo	"</blockquote>\n";
		}
	}
}

// End main content
echo '</div>';

// Display the footer
Display :: display_footer();
?>
