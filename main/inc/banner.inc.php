<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This script contains the actual html code to display the "header"
*	or "banner" on top of every Dokeos page.
*
*	@package dokeos.include
==============================================================================
*/
$home_path = '';

if (isset($GLOBALS['_cid']) && $GLOBALS['_cid'] != -1) {
    // if We are inside a course
    $css_name = api_get_setting('allow_course_theme') == 'true'?(api_get_course_setting('course_theme', null, true)?api_get_course_setting('course_theme', null, true):api_get_setting('stylesheets')):api_get_setting('stylesheets');
    $css_info = api_get_css_info($css_name);
} else {
    $css_info = api_get_css_info();    
}
// Check if we have a CSS with tablet support
$css_type = !is_null($css_info['type']) ? $css_info['type'] : '';

?>
<div id="wrapper">

<div id="header">
	<div id="header1">
        <?php if (api_get_setting('allow_course_theme') == 'true' && $css_type != 'tablet'): ?>
		<div class="headerinner">
			<div id="top_corner"></div> 
			<div id="languageselector">
			<?php
			if (!($_user['user_id']) || api_is_anonymous($_user['user_id']) ) { // only display if the user isn't logged in
				api_display_language_form(true);
			}
			?>
			</div>
			<div id="institution">
				<a href="<?php echo api_get_path(WEB_PATH);?>index.php" target="_top"><?php echo api_get_setting('siteName') ?></a>
				<?php
				$iurl = api_get_setting('InstitutionUrl');
				$iname = api_get_setting('Institution');
				if (!empty($iname))
				{
			       echo '-&nbsp;<a href="'.$iurl.'" target="_top">'.$iname.'</a>';
				}
				?>
			</div>
                        
                        <!-- Search resources -->
                        <?php if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian') && !api_is_anonymous()) { ?>
                       
                            <div class="head-search" style="position: absolute; top:0px; right:0px;padding: 10px;">
                            <form  method="POST" onsubmit="return h_search();" style="position:relative;">
                                <span>
                                    
                                    <input type="text" id="input" name="input" class="input-h-search" id="example-text-input">                               
                                    </span>
                                    <span>
                                    <input type="image" src="<?php echo api_get_path(WEB_IMG_PATH).'button_search.png'; ?>" id="btn-h-search"/>
                                    <!--button id="submitBt" class="search" style="height:28px;"><php echo get_lang('Search'); ?></button-->
                                    </span>
                            </form>
		</div>
                        
                        <?php } ?>
                        
	</div>
    <?php endif; ?>    
        
        
	</div>



	<div id="header2">
		<div class="headerinner">
			<?php display_tabs(); ?>
		</div>
	</div>
	<?php isset($_course['path']) ? $home_path = api_get_path(WEB_COURSE_PATH).$_course['path'].'/' : $home_path = api_get_path(WEB_PATH); ?>
	<?php if (!empty($_course)){ ?>
	<div id="header3">
		<div class="headerinner">
			<a id="back2home" href="<?php echo $home_path; ?>index.php"><img src="<?php echo api_get_path(WEB_IMG_PATH).'spacer.gif'?>" width="42px" height="37px" alt="" /></a>
			<?php 
			// a toggle to to show/hide the platformheader inside the course
			if (api_get_setting('display_platform_header_in_course') == 'toggle'){
				if ($_SESSION['header_state'] == 'expanded'){
					echo '<span id="headertoggle">-</span>';
				} else{
					echo '<span id="headertoggle">+</span>';
				}
			}
			
			// name of training
			if(isset($_course) && array_key_exists('name', $_course))
				echo '<span id="global_course_name">'.$_course['name'].'</span>';
			?>
			<?php if(isset($GLOBALS['display_learner_view']) && $GLOBALS['display_learner_view'] === true && api_is_allowed_to_edit()) : ?>
			<div class="learner_view">
				<?php if(empty($_GET['learner_view'])) : 
				$GLOBALS['learner_view'] = false;
				?>
				<a href="<?php echo api_get_self() ?>?learner_view=true">
					<?php echo get_lang('ViewHomeAsLearner')?>
				</a>
				<?php 
				else : 
				$GLOBALS['learner_view'] = true;
				?>
				<a href="<?php echo api_get_self() ?>">
					<?php echo get_lang('ViewHomeAsTrainer')?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>	
	</div>
	<?php } ?>

	<?php 
	// only display header4 if there is actually something that needs to be displayed (context help or breadcrumbs)
	if (api_get_setting('display_breadcrumbs')=='true' OR api_get_setting('display_context_help') == 'true') {
	?>
	<div id="header4">
		<div class="headerinner">
			<?php if(api_get_setting('display_context_help') == 'true'){ ?>
			<div id="help-content"><?php echo display_help($help_content) ?></div>
			<div id="help">
				<a href="#" style="background-image: url('<?php echo api_get_path(WEB_PATH);?>main/img/screen-options-right.gif);" id="help-link"><?php echo get_lang('Help');?></a>
			</div>
			<?php } ?>
			<?php if(api_get_setting('display_breadcrumbs') == 'true'){ ?>
			<div id="breadcrumbs"><?php display_breadcrumbs();?></div>
			<?php } ?>
		</div>
	</div>
	<?php 
	}
	?>
</div>


<?php
function display_tabs(){
	global $_user; 

	// the user
	if ($_user['user_id']) {
		$login = '';
		if(api_is_anonymous()) {
			$login = '('.get_lang('Anonymous').')';
		} else {
			$uinfo = api_get_user_info(api_get_user_id());
			$login = '('.$uinfo['username'].')';	
		}
	
		echo '<ul id="logout">';
		echo '	<li><span><a href="'.api_get_path(WEB_PATH).'index.php?logout=logout&uid='.$_user['user_id'].'" target="_top"><span>'.get_lang('Logout').' '.$login.'</span></a></li>';
		echo '</ul>';
	}

	// the tabs
	echo '<ul id="dokeostabs">';
	
	// variable initialisation
	$navigation = array();

	// getting all the possible tabs
	$possible_tabs = get_tabs();

	// Campus Homepage
	if (api_get_setting('show_tabs', 'campus_homepage') == 'true') {
		$navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
	} else {
		$menu_navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
	}

	if ($_user['user_id'] && !api_is_anonymous()) {
		// My Courses
		if (api_get_setting('show_tabs', 'my_courses') == 'true') {
			$navigation['mycourses'] = $possible_tabs['mycourses'];
		} else{
			$menu_navigation['mycourses'] = $possible_tabs['mycourses'];
		}

		// My Profile
		if (api_get_setting('show_tabs', 'my_profile') == 'true') {
			$navigation['myprofile'] = $possible_tabs['myprofile'];
		} else {
			$menu_navigation['myprofile'] = $possible_tabs['myprofile'];
		}

		// My Agenda
		if (api_get_setting('show_tabs', 'my_agenda') == 'true') {
			$navigation['myagenda'] = $possible_tabs['myagenda'];
		} else {
			$menu_navigation['myagenda'] = $possible_tabs['myagenda'];
		}

		// Gradebook
	/*	if (api_get_setting('gradebook_enable') == 'true') {
			if (api_get_setting('show_tabs', 'my_gradebook') == 'true') {
				$navigation['mygradebook'] = $possible_tabs['mygradebook'];
			} else{
				$menu_navigation['mygradebook'] = $possible_tabs['mygradebook'];
			}
		}*/
	
		// Reporting
		if (api_get_setting('show_tabs', 'reporting') == 'true') {
			if(api_is_allowed_to_create_course() || $_user['status'] == DRH) {
				$navigation['session_my_space'] = $possible_tabs['session_my_space'];
			} else {
				$navigation['session_my_space'] = $possible_tabs['session_my_progress'];
			}
		} else {
			if(api_is_allowed_to_create_course() || $_user['status'] == DRH) {
				$menu_navigation['session_my_space'] = $possible_tabs['session_my_space'];
			} else {
				$menu_navigation['session_my_space'] = $possible_tabs['session_my_progress'];
			}
		}
	
	
		// Social Networking 
            if (api_get_setting('allow_social_tool')=='true') {
		if (api_get_setting('show_tabs', 'social') == 'true') {
			$navigation['social'] = $possible_tabs['social'];
		} else{
			$menu_navigation['social'] = $possible_tabs['social'];
		}
            }
		if(api_is_platform_admin(true)) {
			if (api_get_setting('show_tabs', 'platform_administration') == 'true') {
				$navigation['platform_admin'] = $possible_tabs['platform_admin'];
			} else {
				$menu_navigation['platform_admin'] = $possible_tabs['platform_admin'];
			}
		}
	}

	// Displaying the tabs
	foreach($navigation as $section => $navigation_info) {
		if(isset($GLOBALS['this_section'])) {
			$current = ($section == $GLOBALS['this_section'] ? ' id="current" class="tab_'.$section.'_current"' : ' class="tab_'.$section.'"');
			$class_icon_tab = ($section == $GLOBALS['this_section'] ? ' class="icon_tab_'.$section.'_current"' : ' class="icon_tab_'.$section.'"');
                        
                } else {
			$current = '';
		}
               echo "<a href='".$navigation_info['url']."' target='_top'><li ".$current."><div><span>".$navigation_info['title']."</span></div></li></a>";
	}


	echo '</ul>';
	echo '<div style="clear: both;" class="clear"> </div>';
	}



function display_breadcrumbs(){
	global $interbreadcrumb, $_course, $_cid, $nameTools;


	if (api_get_setting('display_breadcrumbs')=='true'){
		// variable initialisation
		$navigation = array();

		// part 1: Course Homepage. If we are in a course then the first breadcrumb is a link to the course homepage
		//hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
		$session_id     = api_get_session_id();
		$session_name   = api_get_session_name($my_session_id);
		$my_session_name= ($session_name==null) ? '' : '&nbsp;('.$session_name.')';
		if (isset ($_cid) and $_cid!=-1 and isset($_course) and !isset($_GET['hide_course_breadcrumb'])) {
			$navigation_item['url'] = api_get_path(WEB_COURSE_PATH) . $_course['path'].'/index.php';
			switch(api_get_setting('breadcrumbs_course_homepage')) {
				case 'get_lang':
					$navigation_item['title'] =  get_lang('CourseHomepageLink');
					break;
				case 'course_code':
					$navigation_item['title'] =  $_course['official_code'];
					break;
				case 'session_name_and_course_title':
					$navigation_item['title'] =  $_course['name'].$my_session_name;
					break;
				default:
					$navigation_item['title'] =  $_course['name'];
					break;
			}
			$navigation[] = $navigation_item;
		}

		// part 2: Interbreadcrumbs. If there is an array $interbreadcrumb defined then these have to appear before the last breadcrumb (which is the tool itself)
		if (isset($interbreadcrumb) && is_array($interbreadcrumb)) {
			foreach($interbreadcrumb as $breadcrumb_step) {
				$sep = (strrchr($breadcrumb_step['url'], '?') ? '&amp;' : '?');
				$navigation_item['url'] = $breadcrumb_step['url'].$sep.api_get_cidreq();
				$navigation_item['title'] = $breadcrumb_step['name'];
				$navigation[] = $navigation_item;
			}
		}
		// part 3: The tool itself. If we are on the course homepage we do not want to display the title of the course because this
		// is the same as the first part of the breadcrumbs (see part 1)
		if (isset ($nameTools) AND $language_file<>"course_home") {
			$navigation_item['url'] = '#';
			$navigation_item['title'] = $nameTools;
			$navigation[] = $navigation_item;
		}

		$final_navigation = array();
		foreach($navigation as $index => $navigation_info) {
			if(!empty($navigation_info['title'])) {
				$final_navigation[$index] = '<a href="'.$navigation_info['url'].'" class="breadcrumb breadcrumb'.$index.'" target="_top">'.$navigation_info['title'].'</a>';
			}
		}

		if (!empty($final_navigation)) {
			echo '<div id="header5">';	
			echo implode(' &gt; ',$final_navigation);
			echo '</div>';
		}
	}
}



function display_help($help_content, $help_subtopic=''){
	global $help;

	if(empty($help[(string)$help_content]))
		return '';

	$return .= '<ul>';
	if (empty($help_subtopic))
	{
		foreach ($help[$help_content] as $subtopic=>$helptopic){
			// we are having subtopics
			if (is_array($helptopic)){
				$return .= '<li><strong>'.get_lang($subtopic).'</strong></li>';
				$return .= '<li>'.display_help($help_content,$subtopic).'</li>';
			} else {
				$return .= '<li>'.$helptopic.'</li>';
			}
		}
	} else {
		foreach ($help[$help_content][$help_subtopic] as $subtopic=>$helptopic){
			//echo '<br>hier:'.$help_content.'/'.$help_subtopic.'/'.$subtopic.'/'.$helptopic.'<br/>';
			$return .= '<li>'.$helptopic.'</li>';
		}
	}
	$return .= '</ul>';
	return $return;
}

if (isset ($dokeos_database_connection)) {
	// connect to the main database.
	// if single database, don't pefix table names with the main database name in SQL queries
	// (ex. SELECT * FROM `table`)
	// if multiple database, prefix table names with the course database name in SQL queries (or no prefix if the table is in
	// the main database)
	// (ex. SELECT * FROM `table_from_main_db`  -  SELECT * FROM `courseDB`.`table_from_course_db`)
	mysql_select_db($_configuration['main_database'], $dokeos_database_connection);
}
?>

</div> <!-- end of the whole #header section -->
<div class="clear">&nbsp;</div>
<?php
//to mask the main div, set $header_hide_main_div to true in any script just before calling Display::display_header();
global $header_hide_main_div;
if (!empty($header_hide_main_div) && $header_hide_main_div===true) {
	//do nothing
} else {
?>
<div id="main"> <!-- start of #main wrapper for #content and #menu divs -->
<?php
}

/*
-----------------------------------------------------------------------------
	Navigation menu section
-----------------------------------------------------------------------------
*/
if(api_get_setting('show_navigation_menu') != 'false' && api_get_setting('show_navigation_menu') != 'icons') {
	Display::show_course_navigation_menu($_GET['isHidden']);	
	$course_id = api_get_course_id();   
   if (!empty($course_id) && ($course_id != -1)) {
		echo '<div id="menuButton">';
 		echo $output_string_menu;
 		echo '</div>';
		if(isset($_SESSION['hideMenu'])) {
			if($_SESSION['hideMenu'] =="shown") {
 				if (isset($_cid) ) {
					echo '<div id="centerwrap"> <!-- start of #centerwrap -->';
					echo '<div id="center"> <!-- start of #center -->';
				}
			}
 		} else {
			if (isset($_cid) ) {
				echo '<div id="centerwrap"> <!-- start of #centerwrap -->';
				echo '<div id="center"> <!-- start of #center -->';
			}
 		}
 	}
}

/**
 * Determines the possible tabs (=sections) that are available.
 * This function is used when creating the tabs in the third header line and all the sections
 * that do not appear there (as determined by the platform admin on the Dokeos configuration settings page)
 * will appear in the right hand menu that appears on several other pages
 *
 * @return array containing all the possible tabs
 *
 * @version Dokeos 1.8.4
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function get_tabs() {
	global $_course, $rootAdminWeb, $_user;

	// Campus Homepage
	$navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
	$navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');

	// My Courses
	if(api_get_setting('use_session_mode')=='true') {
		if(api_is_allowed_to_create_course()) {
			// Link to my courses for teachers
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		} else {
			// Link to my courses for students
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		}

	} else {
		// Link to my courses
		$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
		$navigation['mycourses']['title'] = get_lang('MyCourses');
	}

	// My Profile
	$navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myprofile']['title'] = get_lang('ModifyProfile');

	// Link to my agenda
	$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myagenda']['title'] = get_lang('MyAgenda');

	// Gradebook
	if (api_get_setting('gradebook_enable') == 'true') {
		$navigation['mygradebook']['url'] = api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );	
		$navigation['mygradebook']['title'] = get_lang('MyGradebook');
	}

	// Reporting
	if(api_is_allowed_to_create_course() || $_user['status']==DRH) {
		// Link to my space
		$navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/';
		$navigation['session_my_space']['title'] = get_lang('MySpace');
	} else {
		// Link to my progress
		$navigation['session_my_progress']['url'] = api_get_path(WEB_CODE_PATH).'auth/my_progress.php';
		$navigation['session_my_progress']['title'] = get_lang('MySpace');
	}

	// Social
	if (api_get_setting('allow_social_tool')=='true') {
			$navigation['social']['url'] = api_get_path(WEB_CODE_PATH).'social/home.php';
			$navigation['social']['title'] = get_lang('SocialNetwork');

	}

	// Platform administration
	if (api_is_platform_admin(true)) {
		//$navigation['platform_admin']['url'] = $rootAdminWeb;
		$navigation['platform_admin']['url'] = api_get_path(WEB_CODE_PATH).'admin/';
		$navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
	}

	return $navigation;
}
?>
<!--   Begin Of script Output   -->
