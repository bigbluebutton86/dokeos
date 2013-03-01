<?php

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

/**
 * This function changes the visibility of a tool inside the current course
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @since June 2010
 * @version 1.0
 */
function change_tool_visibility($tool_id,$requested_visible){
	// Database table definition
	$tool_table = Database::get_course_table(TABLE_TOOL_LIST);

	if(api_is_allowed_to_edit(null,true)) {
		$sql="UPDATE $tool_table SET visibility='".Database::escape_string($requested_visible)."' WHERE id='".Database::escape_string(intval($tool_id))."'";
		Database::query($sql,__FILE__,__LINE__);
	}
}

/**
 * Gets the tools of a certain category. Returns an array expected
 * by show_tools_category()
 * @param string $course_tool_category	contains the category of tools to
 * display: "toolauthoring", "toolinteraction", "tooladmin", "tooladminplatform"
 * @return array
 */
function get_tools_category($course_tool_category) {
	global $_user;

	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
	$is_allowed_to_edit = api_is_allowed_to_edit(null,true);
	$is_platform_admin = api_is_platform_admin();
	$all_tools_list = array();

	//condition for the session
	$session_id = api_get_session_id();
	$condition_session = api_get_session_condition($session_id,true,true);

	switch ($course_tool_category) {

		case TOOL_STUDENT_VIEW:

				//$visibility_codition = !api_is_coach()?" visibility = '1' AND ":" ";
				$sql = "SELECT * FROM $course_tool_table WHERE visibility = '1' AND (category = 'authoring' OR category = 'interaction') AND (name != 'author' AND name != 'oogie') $condition_session ORDER BY id";
				$result = Database::query($sql,__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_AUTHORING:
				$sql = "SELECT * FROM $course_tool_table WHERE category = 'authoring' $condition_session ORDER BY id";
				$result = Database::query($sql,__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_INTERACTION:
				$sql = "SELECT * FROM $course_tool_table WHERE category = 'interaction' $condition_session ORDER BY id";
				$result = Database::query($sql,__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_ADMIN_VISIBLE:
				$sql = "SELECT * FROM $course_tool_table WHERE category = 'admin' AND visibility ='1' $condition_session ORDER BY id";
				$result = Database::query($sql,__FILE__,__LINE__);
				$colLink ="##003399";
				break;
		case TOOL_ADMIN_PLATEFORM:
				$sql = "SELECT * FROM $course_tool_table WHERE category = 'admin' $condition_session ORDER BY id";
				$result = Database::query($sql,__FILE__,__LINE__);
				$colLink ="##003399";
				break;


	}

    $cont = 0;
	while ($temp_row = Database::fetch_array($result)) {
		$all_tools_list[]=$temp_row;

        if ($temp_row['image'] == 'oogie.png' && api_get_setting('service_ppt2lp', 'active') !== 'true') {
          unset($all_tools_list[$cont]);
        }

        // Calendar course inside was dissabled
        if ( $temp_row['name'] == 'calendar_event') {
            if (!api_is_allowed_to_edit()) { // Only remove the calenda to students
                unset($all_tools_list[$cont]);
            }   
        }
        // No display search icon
        if ($temp_row['name'] == 'search') {
          unset($all_tools_list[$cont]);
        }
        $cont ++;
	}

	/*if(api_is_course_coach())
	{
		$result = Database::query("SELECT * FROM $course_tool_table WHERE name='tracking'",__FILE__,__LINE__);
		$all_tools_list[]=Database :: fetch_array($result);
	}*/

	$i=0;
	// grabbing all the links that have the property on_homepage set to 1
	$course_link_table = Database::get_course_table(TABLE_LINK);
	$course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);

	switch ($course_tool_category) {
			case TOOL_AUTHORING:
			$sql_links="SELECT tl.*, tip.visibility
					FROM $course_link_table tl
					LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1' $condition_session";

			break;

			case TOOL_INTERACTION:
			$sql_links = null;
			/*
			$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1' ";
			*/
			break;

			case TOOL_STUDENT_VIEW:
				$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1' $condition_session";
			break;

			case TOOL_ADMIN:
				$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1' $condition_session";
			break;

		default:
			$sql_links = null;
			break;
	}

	//Edit by Kevin Van Den Haute (kevin@develop-it.be) for integrating Smartblogs
	if ($sql_links != null) {
		$result_links = Database::query($sql_links,__FILE__,__LINE__);
		$properties = array();
		if (Database::num_rows($result_links) > 0) {
			while ($links_row = Database::fetch_array($result_links)) {
				unset($properties);
				$properties['name'] = $links_row['title'];
				$properties['session_id'] = $links_row['session_id'];
				$properties['link'] = $links_row['url'];
				$properties['visibility'] = $links_row['visibility'];
				$properties['image'] = ($links_row['visibility']== '0') ? "file_html.gif" : "file_html.gif";
				$properties['adminlink'] = api_get_path(WEB_CODE_PATH) . "link/link.php?".api_get_cidreq()."&action=editlink&id=".$links_row['id'];
				$properties['target'] = $links_row['target'];
				$tmp_all_tools_list[] = $properties;
			}
		}
	}

	if (isset($tmp_all_tools_list)) {
		foreach ($tmp_all_tools_list as $toolsRow) {
			if ($toolsRow['image'] == 'blog.gif') {
				// Init
				$tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);

				// Get blog id
				$blog_id = substr($toolsRow['link'], strrpos($toolsRow['link'], '=') + 1, strlen($toolsRow['link']));

				// Get blog members
				if($is_platform_admin) {
					$sql_blogs = "
						SELECT *
						FROM " . $tbl_blogs_rel_user . " blogs_rel_user
						WHERE blog_id = " . $blog_id;
				} else {
					$sql_blogs = "
						SELECT *
						FROM " . $tbl_blogs_rel_user . " blogs_rel_user
						WHERE
							blog_id = " . $blog_id . " AND
							user_id = " . api_get_user_id();
				}

				$result_blogs = Database::query($sql_blogs, __FILE__, __LINE__);

				if (Database::num_rows($result_blogs) > 0) {
					$all_tools_list[] = $toolsRow;
				}
			} else {
				$all_tools_list[] = $toolsRow;
			}
		}
	}
	return $all_tools_list;
}

/**
 * Displays the tools of a certain category.
 * @param array List of tools as returned by get_tools_category()
 * @return void
 */

function show_tools_category($all_tools_list)
{
	global $_user, $course_home_visibility_type;

	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
	$is_allowed_to_edit = api_is_allowed_to_edit(null,true);
	$is_platform_admin = api_is_platform_admin();
	$i=0;
	$j=1;
	if (isset($all_tools_list)) {
		$lnk = '';

		// looping through all the tools
		foreach ($all_tools_list as $toolsRow) {

			// not displaying the course_maintenance and course_setting when no session id.
			// WHY ?
			if (api_get_session_id()!=0 && in_array($toolsRow['name'],array('course_maintenance','course_setting'))) {
				continue;
			}

			// starting a new row in the table layout
			if (!($i%3)) {
				echo	'<tr valign="top">' . PHP_EOL;
			}

			// start a new table cell
			echo '<td width="30%">' . PHP_EOL;

			// This part displays the links to hide or remove a tool.
			// These links are only visible by the course manager.
			unset($lnk);
			if ($is_allowed_to_edit && !api_is_course_coach()) {

				if ($toolsRow['visibility'] == '1' && $toolsRow['admin'] !='1') {
					$link['name'] = Display::return_icon('visible_link.png', get_lang('Deactivate'),array('id'=>'linktool_'.$toolsRow['id']));
                                        if ($course_home_visibility_type) {
					  $link['name'] = Display::return_icon('pixel.gif', get_lang('Deactivate'),array('class' => 'actionplaceholderminiicon toolactionview','id'=>'linktool_'.$toolsRow['id']));
                                        }

					$link['cmd'] = "hide=yes";
					$lnk[] = $link;
				}

				if ($toolsRow['visibility'] == '0' && $toolsRow['admin'] !='1') {
					$link['name'] = Display::return_icon('closedeye_tr.png', get_lang('Activate'),array('id'=>'linktool_'.$toolsRow['id']));
                                        if ($course_home_visibility_type) {
					  $link['name'] = Display::return_icon('pixel.gif', get_lang('Activate'),array('class' => 'actionplaceholderminiicon toolactionhide','id'=>'linktool_'.$toolsRow['id']));
                                        }
                                        $link['cmd'] = "restore=yes";
					$lnk[] = $link;
				}
				if (!empty($toolsRow['adminlink'])) {
					   //echo	Display::return_icon('pixel.gif', get_lang('Edit'),array('class'=>'toolactionplaceholdericon tool edit')).'</a>';
                        echo	'<a href="'.$toolsRow['adminlink'].'">'.Display::return_icon('pixel.gif', get_lang('Edit'),array('class'=>'toolactionplaceholdericon tool edit')).'</a>';
				}

			}

			// Both checks are necessary as is_platform_admin doesn't take student view into account
			if ($is_platform_admin && $is_allowed_to_edit) {
 				if ($toolsRow['admin'] !='1') {
					$link['cmd'] = 'hide=yes';

				}
			}
			
			// display the link to show/hide/remove the tool
			if (isset($lnk) && is_array($lnk)) {
				foreach ($lnk as $this_link)	{
					if (empty($toolsRow['adminlink'])) {
							echo '<a class="make_visible_and_invisible"  href="' .api_get_self(). '?'.api_get_cidreq().'&amp;id=' . $toolsRow['id'] . '&amp;' . $this_link['cmd'] . '">' .	$this_link['name'] . '</a>';
							//echo "<a  class=\"make_visible_and_invisible\" href=\"javascript:void(0)\" >" .	$this_link['name'] . "</a>";

					}
				}
			} else { 
				echo '&nbsp;&nbsp;&nbsp;&nbsp;';
			}

			// setting the name (and class attribute) of the tool (for files, learningpaths, blogs, external links we do not need to translate the name of the tool
			// but for other tools we do need to translate the name of the tool
			if ($toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'file_html_na.gif'
				|| $toolsRow['image'] == 'scormbuilder.gif' || $toolsRow['image'] == 'scormbuilder_na.gif'
				|| $toolsRow['image'] == 'blog.png' || $toolsRow['image'] == 'blog_na.png'
				|| $toolsRow['image'] == 'external.gif' || $toolsRow['image'] == 'external_na.gif')
			{
				switch ($toolsRow['image']){
					case 'file_html.gif':
					case 'file_html_na.gif':
						$tool_class = 'file';
						break;
					case 'scormbuilder.gif':
					case 'scormbuilder_na.gif':
						$tool_class = 'learnpath';
						break;
					case 'blog.png':
					case 'blog_na.png':
						$tool_class = 'blog';
						break;
					case 'external.gif':
					case 'external_na.gif':
						$tool_class = 'external';
						break;
				}
				$tool_name = stripslashes($toolsRow['name']);
			} else {
				$tool_class = $toolsRow['name'];
				$tool_name = get_lang(ucfirst($toolsRow['name']));
			}

			// add the class 'invisible' when the tool is invisible
			if ($toolsRow['visibility'] == '0' && $toolsRow['admin'] != '1') {
				$tool_class .= ' invisible';
			}

			// setting the link. If there is no http, https or ftp in the link we prepend the web code path 
			if (!stristr($toolsRow['link'], 'http://')
                    && !stristr($toolsRow['link'], 'https://')
                    && !stristr($toolsRow['link'],'ftp://')) {
				$toolsRow['link'] = api_get_path(WEB_CODE_PATH) . $toolsRow['link'];
            }

			// determine if the link already contains a question mark (?). If this is the case we only need to &amp; to the url when we want to add parameters
			$qm_or_amp = ((strpos($toolsRow['link'], '?') === FALSE) ? '?' : '&amp;');

			// constructing the link. Special cases: link added to course homepage, visio, classroom
			if ($toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'file_html_na.gif') {
				$toolsRow['link'] = $toolsRow['link'].$qm_or_amp;
			} else {
				$toolsRow['link'] = $toolsRow['link'].$qm_or_amp.api_get_cidreq();
			}
			if (strpos($toolsRow['name'],'visio_')!==false) {
				$toollink 		= "\t" . '<a id="tooldesc_'.$toolsRow["id"].'"  ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+1024+\', width=\'+1280+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
				//$my_tool_link 	= "\t" . '<a id="istooldesc_'.$toolsRow["id"].'"  ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . Security::remove_XSS($toolsRow['link']) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+1024+\', width=\'+1280+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
			} elseif (strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true) {
				$toollink 		= "\t" . '<a id="tooldesc_'.$toolsRow["id"].'" ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . Security::remove_XSS($toolsRow['link']) . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+500+\', width=\'+930+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
				//$my_tool_link	= "\t" . '<a id="istooldesc_'.$toolsRow["id"].'" ' . $class . ' href="javascript: void(0);" onclick="window.open(\'' . Security::remove_XSS($toolsRow['link']) . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+500+\', width=\'+930+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';

			} else {
				if (count(explode('type=classroom',$toolsRow['link']))==2 || count(explode('type=conference',$toolsRow['link']))==2) {
					$toollink 		= "\t" . '<a id="tooldesc_'.$toolsRow['id'].'" ' . $class . ' href="' . $toolsRow['link'] . '" target="_blank">';
					//$my_tool_link 	= "\t" . '<a id="istooldesc_'.$toolsRow["id"].'" ' . $class . ' href="' . $toolsRow['link'] . '" target="_blank">';

				} else {
					$toollink 		= "\t" . '<a id="tooldesc_'.$toolsRow["id"].'" ' . $class . ' href="' . Security::remove_XSS($toolsRow['link']) . '" target="' . $toolsRow['target'] . '">';
					//$my_tool_link 	= "\t" . '<a id="istooldesc_'.$toolsRow["id"].'" ' . $class . ' href="' . Security::remove_XSS($toolsRow['link']) . '" target="' . $toolsRow['target'] . '">';
				}
			}
			
			// when the tool belongs to a session? ????
			$session_img = api_get_session_image($toolsRow['session_id'], $_user['status']);
                       
			// Display the tool
			echo '<div class="tool '.$tool_class.'"  id="tool_'.$toolsRow['id'].'">' . PHP_EOL;
			echo '<div class="toolplaceholder">' . $toollink . Display::return_icon('pixel.gif', $toolsRow['name'], array('class' => 'placeholdericon')).'</a></div>' . PHP_EOL;
			echo '<div class="tooltitle">' . $toollink . $tool_name.$session_img.'</a></div>' . PHP_EOL;
			echo '</div>' . PHP_EOL;

			// end the table cell
			echo '</td>' . PHP_EOL;

			// end the row
			if($j == 3)
			{
				echo '</tr>' . PHP_EOL;
				$j = 0;
			}

			$i++;
			$j++;
		}
	}

	if ($i%3) {
		echo	"<td width=\"10%\">&nbsp;</td>\n",
				"</tr>\n";
	}
}

/**
 * Displays the tools of a certain category.
 *
 * @return void
 * @param string $course_tool_category	contains the category of tools to display:
 * "Public", "PublicButHide", "courseAdmin", "claroAdmin"
 */
function show_tools($course_tool_category)
{
	global $charset;
	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);

	switch ($course_tool_category)
	{
		case TOOL_PUBLIC:

				$sql = "SELECT * FROM $course_tool_table WHERE visibility=1 AND admin=0";
				if(!api_is_allowed_to_edit())
				{
					$sql .= " AND (name != 'author' AND name != 'oogie')";
				}
				$sql .= " ORDER BY id";
				$result = Database::query($sql,__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_PUBLIC_BUT_HIDDEN:

				$result = Database::query("SELECT * FROM $course_tool_table WHERE visibility=0 AND admin=0 ORDER BY id",__FILE__,__LINE__);
				$colLink ="##808080";
				break;

		case TOOL_COURSE_ADMIN:

				$result = Database::query("SELECT * FROM $course_tool_table WHERE admin=1 AND visibility != 2 ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
				break;

		case TOOL_PLATFORM_ADMIN:

				$result = Database::query("SELECT * FROM $course_tool_table WHERE visibility = 2 ORDER BY id",__FILE__,__LINE__);
				$colLink ="##003399";
	}

	$i=0;
    $cont = 0;
	// grabbing all the tools from $course_tool_table
	while ($temp_row = Database::fetch_array($result))
	{
		if($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN && $temp_row['image'] != 'scormbuilder.gif')
		{
			$temp_row['image']=str_replace('.gif','_na.gif',$temp_row['image']);
		}
		$all_tools_list[]=$temp_row;

        if ($temp_row['image'] == 'oogie.png' && api_get_setting('service_ppt2lp', 'active') !== 'true') {
          unset($all_tools_list[$cont]);
        }
        // No display search icon
        if ($temp_row['name'] == 'search') {
          unset($all_tools_list[$cont]);
        }
        $cont++;
	}

	// grabbing all the links that have the property on_homepage set to 1
	$course_link_table = Database::get_course_table(TABLE_LINK);
	$course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
	switch ($course_tool_category)
	{
		case TOOL_PUBLIC:
			$sql_links="SELECT tl.*, tip.visibility
					FROM $course_link_table tl
					LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
					WHERE tl.on_homepage='1' AND tip.visibility = 1";
			break;
		case TOOL_PUBLIC_BUT_HIDDEN:
			$sql_links="SELECT tl.*, tip.visibility
				FROM $course_link_table tl
				LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
				WHERE tl.on_homepage='1' AND tip.visibility = 0";
			break;
		default:
			$sql_links = null;
			break;
	}
	if( $sql_links != null )
	{
		$properties = array();
		$result_links=Database::query($sql_links,__FILE__,__LINE__);
		while ($links_row=Database::fetch_array($result_links))
		{
			unset($properties);
			$properties['name']=$links_row['title'];
			$properties['link']=$links_row['url'];
			$properties['visibility']=$links_row['visibility'];
			$properties['image']=($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN)?"external_na.gif":"external.gif";
			$properties['adminlink']=api_get_path(WEB_CODE_PATH)."link/link.php?action=editlink&id=".$links_row['id'];
			$all_tools_list[]=$properties;
		}
	}

	if (isset($all_tools_list))
	{
		$lnk = array();
		foreach ($all_tools_list as $toolsRow)
		{
			if (api_get_session_id()!=0 && in_array($toolsRow['name'],array('course_maintenance','course_setting'))) {
				continue;
			}

			if (!($i%2))
			{
				echo	"<tr valign=\"top\">\n";
			}

			// NOTE : table contains only the image file name, not full path
			if(!stristr($toolsRow['link'],'http://') && !stristr($toolsRow['link'],'https://') && !stristr($toolsRow['link'],'ftp://'))
			{
				$toolsRow['link']=api_get_path(WEB_CODE_PATH).$toolsRow['link'];
			}
			if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN)
			{
			    $class="class=\"invisible\"";
			}
			$qm_or_amp = ((strpos($toolsRow['link'],'?')===FALSE)?'?':'&');

			$toolsRow['link'] = $toolsRow['link'];
			echo	'<td width="50%" height="30">';

			if(strpos($toolsRow['name'],'visio_')!==false)
			{
				echo '<a  '.$class.' href="javascript: void(0);" onclick="window.open(\'' . Security::remove_XSS($toolsRow['link']).(($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif") ? '' : $qm_or_amp.api_get_cidreq()) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+1024+\', width=\'+1280+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '">';
			}

			else if(strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true)
			{
				/*
				echo  '<a href="#" onclick="window.open(\'' . htmlspecialchars($toolsRow['link']) .(($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif") ? '' : $qm_or_amp.api_get_cidreq()). '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"'.$class.'>';
				*/
				echo  '<a href="javascript: void(0);" onclick="window.open(\'' . Security::remove_XSS($toolsRow['link']).$qm_or_amp.api_get_cidreq() . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+500+\', width=\'+930+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"'.$class.'>';
			}
			else
			{
				echo	'<a href="'. Security::remove_XSS($toolsRow['link']).(($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif") ? '' : $qm_or_amp.api_get_cidreq()).'" target="' , $toolsRow['target'], '" '.$class.'>';
			}

			/*
			echo Display::return_icon($toolsRow['image'], get_lang(ucfirst($toolsRow['name']))),'&nbsp;', ($toolsRow['image']=="external.gif" || $toolsRow['image']=="external_na.gif" || $toolsRow['image']=="scormbuilder.gif" || $toolsRow['image']=="blog.gif") ? htmlspecialchars( $toolsRow['name'],ENT_QUOTES,$charset) : get_lang(ucfirst($toolsRow['name'])),'</a>';
			*/
			if ($toolsRow['image'] == 'file_html.gif' || $toolsRow['image'] == 'file_html_na.gif'
				|| $toolsRow['image'] == 'scormbuilder.gif' || $toolsRow['image'] == 'scormbuilder_na.gif'
				|| $toolsRow['image'] == 'blog.png' || $toolsRow['image'] == 'blog_na.png'
				|| $toolsRow['image'] == 'external.gif' || $toolsRow['image'] == 'external_na.gif')
			{
				$tool_name = htmlspecialchars($toolsRow['name'], ENT_QUOTES, $charset);
			}
			else
			{
				$tool_name = get_lang(ucfirst($toolsRow['name']));
			}
			$tools_img = explode('.',$toolsRow['image']);
			$toolsRow['image'] = $tools_img[0].'.png';
			echo Display::return_icon($toolsRow['image'], $tool_name),'&nbsp;', $tool_name,'</a>';

			// This part displays the links to hide or remove a tool.
			// These links are only visible by the course manager.
			unset($lnk);
			if (api_is_allowed_to_edit(null,true) && !api_is_coach())
			{
				if ($toolsRow["visibility"] == '1')
				{
					$link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'));
					$link['cmd'] = "hide=yes";
					$lnk[] = $link;
				}

				if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN)
				{
					$link['name'] = Display::return_icon('add.gif', get_lang('Activate'));
					$link['cmd']  = "restore=yes";
					$lnk[] = $link;

					if($toolsRow["added_tool"] == 1)
					{
						$link['name'] = Display::return_icon('delete.png', get_lang('Remove'));
						$link['cmd']  = "remove=yes";
						$lnk[] = $link;
					}
				}
				if ($toolsRow['adminlink'])
				{
					echo	'<a href="'.$toolsRow['adminlink'].'">'.Display::return_icon('edit.png', get_lang('Edit')).'</a>';
					//echo "edit link:".$properties['adminlink'];
				}

			}
			if ( api_is_platform_admin() )
			{
				if ($toolsRow["visibility"]==2)
				{
					$link['name'] = Display::return_icon('undelete.png', get_lang('Activate'));

					$link['cmd']  = "hide=yes";
					$lnk[] = $link;

					if($toolsRow["added_tool"] == 1)
					{
						$link['name'] = get_lang("Delete");
						$link['cmd'] = "askDelete=yes";
						$lnk[] = $link;
					}
				}

				if ($toolsRow["visibility"] == 0 && $toolsRow["added_tool"] == 0)
				{
					$link['name'] = Display::return_icon('delete.png', get_lang('Remove'));
					$link['cmd'] = "remove=yes";
					$lnk[] = $link;
				}
			}
			if (is_array($lnk))
			{
				foreach($lnk as $this_link)
				{
					if (!$toolsRow['adminlink'])
						{
							echo "<a href=\"" .api_get_self(). "?".api_get_cidreq()."&amp;id=" . $toolsRow["id"] . "&amp;" . $this_link['cmd'] . "\">" .	$this_link['name'] . "</a>";
						}
				}
			}

			// Allow editing of invisible homepage links (modified external_module)
			/*
			if ($toolsRow["added_tool"] == 1 &&
					api_is_allowed_to_edit() && !$toolsRow["visibility"])
			*/
			if ($toolsRow["added_tool"] == 1 && api_is_allowed_to_edit(null,true) && !$toolsRow["visibility"]
				&& $toolsRow['image'] != 'scormbuilder.gif' && $toolsRow['image'] != 'scormbuilder_na.gif')
				echo	"<a class=\"nobold\" href=\"" . api_get_path(WEB_PATH) .
						'main/external_module/external_module.php' .
						"?".api_get_cidreq()."&amp;id=".$toolsRow["id"]."\">". get_lang("Edit"). "</a>";

			echo "</td>\n";

			if($i%2)
			{
				echo "</tr>\n";
			}

			$i++;
		}
	}

	if($i%2)
	{
		echo	"<td width=\"50%\">&nbsp;</td>\n",
				"</tr>\n";
	}
}

/**
 * Shows the general data for a particular meeting
 *
 * @param id	session id
 * @return string	session data
 *
 */
function show_session_data($id_session) {
	$session_table = Database::get_main_table(TABLE_MAIN_SESSION);
	$user_table = Database::get_main_table(TABLE_MAIN_USER);
	$session_category_table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

	if ($id_session!=strval(intval($id_session))) {
		return '';
	} else {
		$id_session = intval($id_session);
	}

	$sql = 'SELECT name, nbr_courses, nbr_users, nbr_classes, DATE_FORMAT(date_start,"%d-%m-%Y") as date_start, DATE_FORMAT(date_end,"%d-%m-%Y") as date_end, lastname, firstname, username, session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end, session_category_id, visibility
				FROM '.$session_table.'
			LEFT JOIN '.$user_table.'
				ON id_coach = user_id
			WHERE '.$session_table.'.id='.$id_session;

	$rs = Database::query($sql, __FILE__, __LINE__);
	$session = Database::store_result($rs);
	$session = $session[0];

	$sql_category = 'SELECT name FROM '.$session_category_table.' WHERE id = "'.intval($session['session_category_id']).'"';
	$rs_category = Database::query($sql_category, __FILE__, __LINE__);
	$session_category = '';
	if (Database::num_rows($rs_category) > 0) {
		$rows_session_category = Database::store_result($rs_category);
		$rows_session_category = $rows_session_category[0];
		$session_category = $rows_session_category['name'];
	}

	if ($session['date_start'] == '00-00-0000') {
		$msg_date = get_lang('NoTimeLimits');
	} else {
		$msg_date = get_lang('From').' '.$session['date_start'].' '.get_lang('To').' '.$session['date_end'];
	}

	$output  = '';
	if (!empty($session_category)) {
		$output .= '<tr><td>'. get_lang('SessionCategory') . ': ' . '<b>' . $session_category .'</b></td></tr>';
	}
	$output .= '<tr><td style="width:50%">'. get_lang('SessionName') . ': ' . '<b>' . $session['name'] .'</b></td><td>'. get_lang('GeneralCoach') . ': ' . '<b>' . $session['lastname'].' '.$session['firstname'].' ('.$session['username'].')' .'</b></td></tr>';
	$output .= '<tr><td>'. get_lang('SessionIdentifier') . ': '. Display::return_icon('star.png', ' ', array('align' => 'absmiddle')) .'</td><td>'. get_lang('Date') . ': ' . '<b>' . $msg_date .'</b></td></tr>';

	return $output;
}

function showtools2($cat)
{
	global $_user, $charset;

	$TBL_ACCUEIL = Database :: get_course_table(TABLE_TOOL_LIST);
	$TABLE_TOOLS = Database :: get_main_table(TABLE_MAIN_COURSE_MODULE);

	$numcols = 3;
	$table = new HTML_Table('width="100%"');
	$toolsRow_all = array ();
	switch ($cat)
	{
		case 'Basic' :
			$sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
									WHERE a.link=t.link AND t.position='basic'";
			if(!api_is_allowed_to_edit())
		    {
				$sql .= " AND (a.name != 'author' AND a.name != 'oogie')";
			}
            $sql .= " ORDER BY t.row, t.column";
			break;

		case 'External' :
			if (api_is_allowed_to_edit())
			{
				$sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
										WHERE (a.link=t.link AND t.position='external')
										OR (a.visibility <= 1 AND (a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image)
										ORDER BY a.id";
			}
			else
			{
				$sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
										WHERE a.visibility = 1 AND ((a.link=t.link AND t.position='external')
										OR ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image))
										 ORDER BY a.id";
			}
			break;

		case 'courseAdmin' :
			$sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
									WHERE admin=1 AND a.link=t.link ORDER BY t.row, t.column";
			break;

		case 'platformAdmin' :
			$sql = "SELECT *, image img FROM $TBL_ACCUEIL WHERE visibility = 2 ORDER BY id";
	}
	$result = Database::query($sql, __FILE__, __LINE__);

	// grabbing all the tools from $course_tool_table
	while ($tempRow = Database::fetch_array($result))
	{
		/*
		if ($tempRow['img'] !== "scormbuilder.gif" AND $tempRow['img'] !== "blog.gif")
		*/
		if ($tempRow['img'] != 'file_html.gif' && $tempRow['img'] != 'file_html_na.gif'
			&& $tempRow['img'] != 'scormbuilder.gif' && $tempRow['img'] != 'scormbuilder_na.gif'
			&& $tempRow['img'] != 'blog.gif' && $tempRow['img'] != 'blog_na.gif'
			&& $tempRow['img'] != 'external.gif' && $tempRow['img'] != 'external_na.gif')
		{
			$tempRow['name_translated'] = get_lang(ucfirst($tempRow['name']));
		}
		$toolsRow_all[] = $tempRow;
	}
	// grabbing all the links that have the property on_homepage set to 1
	if ($cat == "External")
	{
		$tbl_link = Database :: get_course_table(TABLE_LINK);
		$tbl_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		if (api_is_allowed_to_edit(null,true))
		{
			$sql_links = "SELECT tl.*, tip.visibility
								FROM $tbl_link tl
								LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
								WHERE tl.on_homepage='1' AND tip.visibility != 2";
		}
		else
		{
			$sql_links = "SELECT tl.*, tip.visibility
								FROM $tbl_link tl
								LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
								WHERE tl.on_homepage='1' AND tip.visibility = 1";
		}
		$result_links = Database::query($sql_links);
		while ($links_row = Database::fetch_array($result_links))
		{
			$properties = array ();
			$properties['name'] = $links_row['title'];
			$properties['link'] = $links_row['url'];
			$properties['visibility'] = $links_row['visibility'];
			$properties['img'] = 'external.gif';
			$properties['adminlink'] = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&amp;id='.$links_row['id'];
			$toolsRow_all[] = $properties;
		}
	}

	$cell_number = 0;
	// draw line between basic and external, only if there are entries in External
	if ($cat == "External" && count($toolsRow_all))
	{
		$table->setCellContents(0, 0, '<hr noshade="noshade" size="1"/>');
		$table->updateCellAttributes(0, 0, 'colspan="3"');
		$cell_number += $numcols;
	}

	foreach ($toolsRow_all as $toolsRow)
	{
		if (api_get_session_id()!=0 && in_array($toolsRow['name'],array('course_maintenance','course_setting'))) {
				continue;
		}

		$cell_content = '';
		// the name of the tool
		$tool_name = ($toolsRow['name_translated'] != "" ? $toolsRow['name_translated'] : htmlspecialchars($toolsRow['name'],ENT_QUOTES,$charset)); // RH: added htmlspecialchars

		$link_annex = '';
		// the url of the tool
		if ($toolsRow['img'] != "external.gif")
		{
			$toolsRow['link'] = api_get_path(WEB_CODE_PATH).$toolsRow['link'];
			$qm_or_amp = ((strpos($toolsRow['link'], '?') === FALSE) ? '?' : '&amp;');
			$link_annex = $qm_or_amp.api_get_cidreq();
		}
		else // if an external link ends with 'login=', add the actual login...
			{
			$pos = strpos($toolsRow['link'], "?login=");
			$pos2 = strpos($toolsRow['link'], "&amp;login=");
			if ($pos !== false or $pos2 !== false)
			{
				$link_annex = $_user['username'];
			}
		}

		// setting the actual image url
		//$toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img'];
		$tools_img = explode('.',$toolsRow['image']);
		$toolsRow['img'] = api_get_path(WEB_IMG_PATH).$tools_img[0].'.png';
		// VISIBLE
		if ($toolsRow['visibility'] or $cat == 'courseAdmin' or $cat == 'platformAdmin')
		{
			if(strpos($toolsRow['name'],'visio_')!==false)
			{
				$cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\'' . $toolsRow['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+1024+\', width=\'+1280+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.$toolsRow['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
			}
			else if(strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true)
			{
				/*
				$cell_content .= '<a href="#" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.$toolsRow['img'].'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				*/
				$cell_content .= '<a href="javascript: void(0);" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+500+\', width=\'+930+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.$toolsRow['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
			}
			else
			{
				/*
				$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'"><img src="'.$toolsRow['img'].'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				*/
				$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'"><img src="'.$toolsRow['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
			}
		}
		// INVISIBLE
		else
		{
			if (api_is_allowed_to_edit(null,true))
			{
				if(strpos($toolsRow['name'],'visio_')!==false)
				{
					$cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\'' . $toolsRow['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+1024+\', width=\'+1280+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n";
				}
				else if(strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true)
				{
					/*
					$cell_content .= '<a href="#" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
					*/
					$cell_content .= '<a href="javascript: void(0);" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+500+\', width=\'+930+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				}
				else
				{
					/*
					$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n";// don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
					*/
					$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n";// don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				}
			}
			else
			{
				/*
				$cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				*/
				$cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				$cell_content .= '<span class="invisible">'.$tool_name.'</span>';
			}
		}

		$lnk = array ();
		if (api_is_allowed_to_edit(null,true) && $cat != "courseAdmin" && !strpos($toolsRow['link'], 'learnpath_handler.php?learnpath_id') && !api_is_course_coach())
		{
			if ($toolsRow["visibility"])
			{
				$link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'), array('style' => 'vertical-align:middle;'));
				$link['cmd'] = "hide=yes";
				$lnk[] = $link;
			}
			else
			{
				$link['name'] = Display::return_icon('add.gif', get_lang('Activate'), array('style' => 'vertical-align:middle;'));
				$link['cmd'] = "restore=yes";
				$lnk[] = $link;

				/*if($toolsRow["img"] == $dokeosRepositoryWeb."img/external.gif")
				{
					$link['name'] = get_lang('Remove'); $link['cmd']  = "remove=yes";
					if ($toolsRow["visibility"]==2 and $cat=="platformAdmin")
					{
						$link['name'] = get_lang('Delete'); $link['cmd'] = "askDelete=yes";
						$lnk[] = $link;
					}
				}*/
			}
			//echo "<div class=courseadmin>";
			if (is_array($lnk))
			{
				foreach ($lnk as $thisLnk)
				{
					if ($toolsRow['adminlink'])
					{
						$cell_content .= '<a href="'.$properties['adminlink'].'">'.Display::return_icon('edit.png', get_lang('Edit')).'</a>';
						//echo "edit link:".$properties['adminlink'];
					}
					else
					{
						$cell_content .= "<a href=\"".api_get_self()."?id=".$toolsRow["id"]."&amp;".$thisLnk['cmd']."\">".$thisLnk['name']."</a>";
					}
				}
			}

			// RH: Allow editing of invisible homepage links (modified external_module)
			/*
			if ($toolsRow["added_tool"] == 1 && api_is_allowed_to_edit() && !$toolsRow["visibility"])
			*/
			if ($toolsRow["added_tool"] == 1 && api_is_allowed_to_edit() && !$toolsRow["visibility"]
				&& $toolsRow['image'] != 'scormbuilder.gif' && $toolsRow['image'] != 'scormbuilder_na.gif')
			{
				$cell_content .= "<a class=\"nobold\" href=\"".api_get_path(WEB_CODE_PATH).'external_module/external_module.php'."?id=".$toolsRow["id"]."\">".get_lang("Edit")."</a>";
			}
		}
		$table->setCellContents($cell_number / $numcols, ($cell_number) % $numcols, $cell_content);
		$table->updateCellAttributes($cell_number / $numcols, ($cell_number) % $numcols, 'width="32%" height="42"');
		$cell_number ++;
	}
	$table->display();
}
?>
