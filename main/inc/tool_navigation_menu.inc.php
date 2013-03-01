<?php
// $Id: tool_navigation_menu.inc.php 22072 2009-07-14 15:14:42Z jhp1411 $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2005 Dokeos S.A.
	Copyright (c) 2004-2005 Sandra Mathijs, Hogeschool Gent
	Copyright (c) 2005 Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) 2005 Wolfgang Schneider
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Navigation menu display code
*
*	@package dokeos.include
==============================================================================
*/
define('SHORTCUTS_HORIZONTAL',0);
define('SHORTCUTS_VERTICAL',1);
/**
 * Build the navigation items to show in a course menu
 * @param boolean $include_admin_tools
 */
function get_navigation_items($include_admin_tools = false)
{
	global $is_courseMember;
	global $_user;
	global $_course;

	if (!empty($_course['db_name'])) {
            $database = $_course['db_name'];
	}

	$navigation_items = array ();
	$course_id = api_get_course_id();

	if (!empty ($course_id) && ($course_id != -1))
	{
		$user_id = api_get_user_id();

		$course_tools_table = Database :: get_course_table(TABLE_TOOL_LIST,$database);

		/*
		--------------------------------------------------------------
					Link to the Course homepage
		--------------------------------------------------------------
		*/

		$navigation_items['home']['image'] = 'home.gif';
		$navigation_items['home']['link'] = api_get_path(REL_COURSE_PATH).$_SESSION['_course']['path'].'/index.php';
		$navigation_items['home']['name'] = get_lang("CourseHomepageLink");
                $navigation_items['home']['langname'] = 'home';

		/*
		--------------------------------------------------------------
					Link to the different tools
		--------------------------------------------------------------
		*/

		$sql_menu_query = "SELECT * FROM $course_tools_table WHERE visibility='1' and admin='0' ORDER BY id ASC";
		$sql_result = Database::query($sql_menu_query, __FILE__, __LINE__);
		while ($row = Database::fetch_array($sql_result))
		{
			$navigation_items[$row['id']] = $row;
			if (stripos($row['link'], 'http://') === false && stripos($row['link'], 'https://') === false)
			{
				$navigation_items[$row['id']]['link'] = api_get_path(REL_CODE_PATH).$row['link'];
				/*
				$navigation_items[$row['id']]['name'] = $row['image'] == 'scormbuilder.gif' ? $navigation_items[$row['id']]['name'] : get_lang(ucfirst($navigation_items[$row['id']]['name']));
				*/
				if ($row['image'] != 'scormbuilder.gif' && $row['image'] != 'blog.png')
				{
					$navigation_items[$row['id']]['name'] = get_lang(ucfirst($navigation_items[$row['id']]['name']));                                        
                    $navigation_items[$row['id']]['langname'] = $row['name'];
				}                
			}            
		}
		/*
		--------------------------------------------------------------
			Admin (edit rights) only links
			- Course settings (course admin only)
			- Course rights (roles & rights overview)
		--------------------------------------------------------------
		*/

		if ($include_admin_tools)
		{

			$course_settings_sql = "	SELECT name,image FROM $course_tools_table WHERE link='course_info/infocours.php'";
			$sql_result = Database::query($course_settings_sql);
			$course_setting_info = Database::fetch_array($sql_result);
			$course_setting_visual_name = get_lang(ucfirst($course_setting_info['name']));
			if (api_get_session_id()==0) {
				// course settings item
				$navigation_items['course_settings']['image'] = $course_setting_info['image'];
				$navigation_items['course_settings']['link'] = api_get_path(REL_CODE_PATH).'course_info/infocours.php';
				$navigation_items['course_settings']['name'] = $course_setting_visual_name;
                $navigation_items['course_settings']['langname'] = $course_setting_info['name'];                                
			}
		}
	}
	foreach($navigation_items as $key => $navigation_item)
	{
		if (strstr($navigation_item['link'], '?')) {
			//link already contains a parameter, add course id parameter with &
			$parameter_separator = '&amp;';
		}
		else
		{
			//link doesn't contain a parameter yet, add course id parameter with ?
			$parameter_separator = '?';
		}
		$navigation_items[$key]['link'] .= $parameter_separator.api_get_cidreq();
	}
	return $navigation_items;
}

/**
 * Show a navigation menu
 */
function show_navigation_menu()
{
	$navigation_items = get_navigation_items(true);
	$course_id = api_get_course_id();
	if(api_get_setting('show_navigation_menu') == 'icons')
	{
		echo '<div style="float:right;width: 40px;position:absolute;right:10px;top:10px;">';
		show_navigation_tool_shortcuts($orientation = SHORTCUTS_VERTICAL);
		echo '</div>';
	} else {
  // Here we have no a fixed width
		echo '<div style="float:right;position:absolute;right:10px;top:10px;">';
		show_navigation_tool_shortcuts($orientation = SHORTCUTS_VERTICAL);
		echo '</div>';
 }
/*	else
	{
	    echo '<div id="toolnav"> <!-- start of #toolnav -->';*/
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		function createCookie(name,value,days)
		{
			if (days)
			{
				var date = new Date();
				date.setTime(date.getTime()+(days*24*60*60*1000));
				var expires = "; expires="+date.toGMTString();
			}
			else var expires = "";
			document.cookie = name+"="+value+expires+"; path=/";
		}
		function readCookie(name)
		{
			var nameEQ = name + "=";
			var ca = document.cookie.split(';');
			for(var i=0;i < ca.length;i++)
			{
				var c = ca[i];
				while (c.charAt(0)==' ') c = c.substring(1,c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
			}
			return null;
		}
		function swap_menu()
		{
			toolnavlist_el = document.getElementById('toolnavlist');
			center_el = document.getElementById('center');
			swap_menu_link_el = document.getElementById('swap_menu_link');

			if(toolnavlist_el.style.display == 'none')
			{
				toolnavlist_el.style.display = '';
				if (center_el)
				{
					center_el.style.margin = '0 190px 0 0';
				}
				swap_menu_link_el.innerHTML = '<?php echo get_lang('Hide'); ?> &raquo;&raquo;';
				createCookie('dokeos_menu_state',1,10);
			}
			else
			{
				toolnavlist_el.style.display = 'none';
				if (center_el)
				{
					center_el.style.margin = '0 0 0 0';
				}
				swap_menu_link_el.innerHTML = '&laquo;&laquo; <?php echo get_lang('Show'); ?>';
				createCookie('dokeos_menu_state',0,10);
			}
		}
		//document.write('<a href="javascript: void(0);" id="swap_menu_link" onclick="javascript: swap_menu();"><?php echo get_lang('Hide'); ?> &raquo;&raquo;<\/a>');
		/* ]]> */
		</script>
		<?php
		/*echo '<div id="toolnavbox">';
		echo '<div id="toolnavlist"><dl>';
		foreach ($navigation_items as $key => $navigation_item)
		{
			echo '<dd>';
			$url_item = parse_url($navigation_item['link']);
			$url_current = parse_url($_SERVER['REQUEST_URI']);

			if (strpos($navigation_item['link'],'chat')!==false && api_get_course_setting('allow_open_chat_window',$course_id)==true)
			{
				echo '<a href="javascript: void(0);" onclick="window.open(\''.$navigation_item['link'].'\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $navigation_item['target'] . '"';
			}
			else
			{
				echo '<a href="'.$navigation_item['link'].'" target="_self" ';
			}

			if (stristr($url_item['path'],$url_current['path']))
			{
				if(! isset($_GET['learnpath_id']) || strpos($url_item['query'],'learnpath_id='.$_GET['learnpath_id']) === 0)
				{
					echo ' id="here"';
				}
			}
			echo ' title="'.$navigation_item['name'].'">';
			if (api_get_setting('show_navigation_menu') != 'text')
			{
				echo '<div align="left"><img src="'.api_get_path(WEB_IMG_PATH).$navigation_item['image'].'" alt="'.$navigation_item['name'].'"/></div>';
			}
			if (api_get_setting('show_navigation_menu') != 'icons')
			{
				echo $navigation_item['name'];
			}
			echo '</a>';
			echo '</dd>';
			echo "\n";
		}
		echo '</dl></div></div>';
	    echo '</div> <!-- end "#toolnav" -->';*/
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		if(readCookie('dokeos_menu_state') == 0)
		{
			swap_menu();
		}
		/* ]]> */
		</script>
		<?php
	//}
}

/**
 * Show a toolbar with shortcuts to the course tool
 */
function show_navigation_tool_shortcuts($orientation = SHORTCUTS_HORIZONTAL)
{
	$navigation_items = get_navigation_items(false);    
	foreach ($navigation_items as $key => $navigation_item)
	{

        if (empty($navigation_item['langname'])) {continue;}
        
		$class = 'toolactionplaceholdericon toolshortcut_'.strtolower($navigation_item['langname']);
                
                //echo $navigation_item['langname'] .'<br />';
                
		if (strpos($navigation_item['link'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true)
	    {
	    	/*
		  	echo '<a href="#" onclick="window.open(\''.$navigation_item['link'].'\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $navigation_item['target'] . '"';
		  	*/
		  	echo '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$navigation_item['link'].'\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $navigation_item['target'] . '"';
	    }
	    else
	    {
		  	echo '<a href="'.$navigation_item['link'].'"';
	    }

		if (strpos(api_get_self(), $navigation_item['link']) !== false)
		{
			echo ' id="here"';
		}
		echo ' target="_self" title="'.$navigation_item['name'].'">';
  if (api_get_setting('show_navigation_menu') == 'text') {
   echo $navigation_item['name'];
  } elseif (api_get_setting('show_navigation_menu') == 'iconstext') {
		 //echo '<img src="'.api_get_path(WEB_IMG_PATH).$navigation_item['image'].'" alt="'.$navigation_item['name'].'"/>'.'&nbsp;&nbsp;'.$navigation_item['name'];
		 echo Display::return_icon('pixel.gif', $navigation_item['name'], array('class' => $class)).$navigation_item['name'];
  } else {
		 //echo '<img src="'.api_get_path(WEB_IMG_PATH).$navigation_item['image'].'" alt="'.$navigation_item['name'].'"/>';
		 echo Display::return_icon('pixel.gif', $navigation_item['name'], array('class' => $class));
  }
		echo '</a>';
		if($orientation == SHORTCUTS_VERTICAL)
		{
			echo '<br />';
		}
	}
}
?>
