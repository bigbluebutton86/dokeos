<?php

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
 *	@package dokeos.main
 *	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Refactoring
 *	@version $Id: index.php 22368 2009-07-24 23:25:57Z iflorespaz $
 *	@todo check the different @todos in this page and really do them
 *	@todo check if the news management works as expected
 */

// only this script should have this constant defined. This is used to activate the javascript that
// gives the login name automatic focus in header.inc.html.
/** @todo Couldn't this be done using the $HtmlHeadXtra array? */
define('DOKEOS_HOMEPAGE', true);

// the language file
$language_file = array ('courses', 'index', 'admin');

/* Flag forcing the 'current course' reset, as we're not inside a course anymore */
// maybe we should change this into an api function? an example: Coursemanager::unset();
$cidReset = true;

/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/

/** @todo make all the library files consistent, use filename.lib.php and not filename.lib.inc.php */
require_once 'main/inc/global.inc.php';
include_once api_get_path(LIBRARY_PATH).'course.lib.php';
include_once api_get_path(LIBRARY_PATH).'debug.lib.inc.php';
include_once api_get_path(LIBRARY_PATH).'events.lib.inc.php';
include_once api_get_path(LIBRARY_PATH).'system_announcements.lib.php';
include_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
include_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once 'main/chat/chat_functions.lib.php';
require_once (api_get_path(LIBRARY_PATH).'language.lib.php');

$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.5.1.min.js" language="javascript"></script>';
//Code changed like this for testing.
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/slides.min.jquery.js" language="javascript"></script>';

$loginFailed = isset($_GET['loginFailed']) ? true : isset($loginFailed);
$setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

// the section (for the tabs)
$this_section = SECTION_CAMPUS;


// Check if we have a CSS with tablet support
$css_info = array();
$css_info = api_get_css_info();
$css_type = !is_null($css_info['type']) ? $css_info['type'] : '';

/*
-----------------------------------------------------------
	Action Handling
-----------------------------------------------------------
*/

/** @todo 	Wouldn't it make more sense if this would be done in local.inc.php so that local.inc.php become the only place where authentication is done?
 * 			by doing this you could logout from any page instead of only from index.php. From the moment there is a logout=true in the url you will be logged out
 * 			this can be usefull when you are on an open course and you need to log in to edit something and you immediately want to check how anonymous users
 * 			will see it.
 */
$my_user_id = api_get_user_id();

if (!empty($_GET['logout'])) {
	logout();
}

/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
$main_category_table 	= Database :: get_main_table(TABLE_MAIN_CATEGORY);
$track_login_table 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

/*
-----------------------------------------------------------
	Constants and CONFIGURATION parameters
-----------------------------------------------------------
*/
/** @todo these configuration settings should move to the dokeos config settings */
/** defines wether or not anonymous visitors can see a list of the courses on the Dokeos homepage that are open to the world */
$_setting['display_courses_to_anonymous_users'] = 'true';

/** @todo remove this piece of code because this is not used */
if (isset($_user['user_id'])) {
	$nameTools = api_get_setting('siteName');
}

/*
==============================================================================
		LOGIN
==============================================================================
*/

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login / logout procedure is handled.
 * @todo consider removing this piece of code because does nothing.
 */

if (isset($_GET['submitAuth']) && $_GET['submitAuth'] == 1) {
	// nice lie!!!
	echo 'Attempted breakin - sysadmins notified.';
	session_destroy();
	die();
}

//Delete session neccesary for legal terms
if (api_get_setting('allow_terms_conditions')=='true') {
	unset($_SESSION['update_term_and_condition']);
	unset($_SESSION['info_current_user']);
}

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
 * @todo check if this code is used. I think this code is never executed because after clicking the submit button
 * 		 the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
 * 		 on api_get_setting('page_after_login')
 */

if (!empty($_POST['submitAuth'])) {
	// the user is already authenticated, we now find the last login of the user.
	if (isset ($_user['user_id'])) {
		$sql_last_login = "SELECT UNIX_TIMESTAMP(login_date)
								FROM $track_login_table
								WHERE login_user_id = '".$_user['user_id']."'
								ORDER BY login_date DESC LIMIT 1";
		$result_last_login = Database::query($sql_last_login, __FILE__, __LINE__);
		if (!$result_last_login) {
			if (Database::num_rows($result_last_login) > 0) {
				$user_last_login_datetime = Database::fetch_array($result_last_login);
				$user_last_login_datetime = $user_last_login_datetime[0];
				api_session_register('user_last_login_datetime');
			}
		}
		mysql_free_result($result_last_login);

		//event_login();
		if (api_is_platform_admin()) {
			// decode all open event informations and fill the track_c_* tables
			include api_get_path(LIBRARY_PATH).'stats.lib.inc.php';
			decodeOpenInfos();
		}
	}

} // end login -- if ($_POST['submitAuth'])
else {
	// only if login form was not sent because if the form is sent the user was already on the page.

	event_open();
}

// the header
Display :: display_header('', 'dokeos');
echo '<div id="content" class="maxcontent">';

// Plugins for loginpage_main AND campushomepage_main
if (!api_get_user_id()) {
	api_plugin('loginpage_main');
} else {
	api_plugin('campushomepage_main');
}

$home = 'home/';
if ($_configuration['multiple_access_urls']) {
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1){
		$url_info = api_get_access_url($access_url_id);
		// "http://" and the final "/" replaced
		$url = substr($url_info['url'], 7, strlen($url_info['url']) - 8);
		$clean_url = replace_dangerous_char($url);
		$clean_url = str_replace('/', '-', $clean_url);
		$clean_url = $clean_url.'/';
		$home_old = 'home/';
		$home= 'home/'.$clean_url;
	}
}

// Including the page for the news
$page_included = false;

echo '<div id="content_with_menu">';
if(api_is_platform_admin())
{
	echo '<div id="edit_homepage_bloc">
			<a href="'.api_get_path(WEB_CODE_PATH).'admin/configure_homepage.php">'.Display::return_icon('pixel.gif',get_lang('EditPublicPages'),array('class'=>'actionplaceholdericon actionedit','align'=>'absmiddle','&nbsp;')).get_lang('EditPublicPages').'</a>
		  </div>';
}

$slides_management_table = Database :: get_main_table(TABLE_MAIN_SLIDES_MANAGEMENT);

$sql = "SELECT * FROM $slides_management_table LIMIT 1";
$rs = Database::query($sql,__FILE__,__LINE__);
while($row = Database::fetch_array($rs)){
	$slide_speed = $row['slide_speed'];
	$show_slide  = $row['show_slide'];
}
$slide_speed_millisec = $slide_speed * 1000;

if($slide_speed_millisec == 0){
	$slide_speed_millisec = 6000;
}

echo "<script type='text/javascript'>
		$(function(){
			$('#slides').mouseover(function () {
				$('#nextbutton').show();
		});
		$('#slides').mouseout(function () {
				$('#nextbutton').hide();
		});
			$('#slides').slides({
				preload: true,
				preloadImage: 'img/loading.gif',
				play: ".$slide_speed_millisec.",
				pause: 2000,
				hoverPause: true,
				animationStart: function(current){
					$('.slide_caption').animate({
						bottom:-35
					},100);
					if (window.console && console.log) {
						// example return of current slide number
						console.log('animationStart on slide: ', current);
					};
				},
				animationComplete: function(current){
					$('.slide_caption').animate({
						bottom:0
					},200);
					if (window.console && console.log) {
						// example return of current slide number
						console.log('animationComplete on slide: ', current);
					};
				},
				slidesLoaded: function() {
					$('.slide_caption').animate({
						bottom:0
					},200);
				}
			});
		});
	</script>";

$slides_table = Database :: get_main_table(TABLE_MAIN_SLIDES);

if(isset($_REQUEST['language'])){
	$language = $_REQUEST['language'];
}
else {
	$language = api_get_interface_language();
}

$sql = "SELECT * FROM $slides_table WHERE language = '".$language."' ORDER BY display_order";
$res = Database::query($sql,__FILE__,__LINE__);	
$num_of_slides = Database::num_rows($res);
$slides = array();
$img_dir = api_get_path(WEB_PATH). 'home/default_platform_document/';

if($num_of_slides <> 0){
	while($row = Database::fetch_array($res)){	
		$image = $img_dir.$row['image'];
		if(empty($row['title'])){
			$title = get_lang('Title');
		}
		else {
			$title = $row['title'];
		}
		if(empty($row['link'])){
			$link = '#';
		}
		else {
			$link = $row['link'];
		}
		if(empty($row['alternate_text'])){
			$alternate_text = get_lang('AltText');
		}
		else {
			$alternate_text = $row['alternate_text'];
		}
		$slides[] = array('image'=>$image,'title'=>$title,'link'=>$link,'caption'=>$row['caption'],'alttext'=>$alternate_text);
	}
}
else {
	$slides[] = array('image'=>'main/img/slide01.jpg','title'=>get_lang('YourTitle1'),'link'=>'#','caption'=>get_lang('YourCaption1'),'alttext'=>get_lang('AltText1'));
	$slides[] = array('image'=>'main/img/slide02.jpg','title'=>get_lang('YourTitle2'),'link'=>'#','caption'=>get_lang('YourCaption2'),'alttext'=>get_lang('AltText2'));
	$slides[] = array('image'=>'main/img/slide03.jpg','title'=>get_lang('YourTitle3'),'link'=>'#','caption'=>get_lang('YourCaption3'),'alttext'=>get_lang('AltText3'));
}
if (empty($_GET['include']) && $show_slide == 1) {
echo '<div id="container">
		<div id="example">
			
			<div id="slides">
				<div class="slides_container">';

					foreach($slides as $slide){
						echo '<div class="slide">';	
						if(!empty($slide['link']) && $slide['link'] != '#'){
						echo '<a href="'.$slide['link'].'" title="'.$slide['title'].'" target="_blank"><img src="'.$slide['image'].'" width="720" height="240" alt="'.$slide['alttext'].'"></a>';
						}
						else {
						echo '<img src="'.$slide['image'].'" width="720" height="240" title="'.$slide['title'].'" alt="'.$slide['alttext'].'">';
						}
						echo '<div class="slide_caption" style="bottom:0">';
						if(!empty($slide['caption'])){
							echo '<p>'.$slide['caption'].'</p>';
						}
						echo '</div>';
						
						echo '</div>';
					}

			echo '</div>
				<div id="nextbutton" style="display:none;">
				<a href="#" class="prev"><img src="main/img/arrow-prev.png" width="24" height="43" alt="Arrow Prev"></a>
				<a href="#" class="next"><img src="main/img/arrow-next.png" width="24" height="43" alt="Arrow Next"></a>
				</div>
			</div>
		</div>		
	</div>';
//slider ends here
}

// Display courses and category list
if (api_get_setting('show_catalogue') == 'true') {
    
    $topic_table 			= Database :: get_main_table(TABLE_MAIN_TOPIC);
    $tbl_session_category 	= Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
    $catalogue_table 		= Database :: get_main_table(TABLE_MAIN_CATALOGUE);

    $res_topic = Database::query("SELECT * FROM $topic_table WHERE visible = 1");
    $num_topic = Database::num_rows($res_topic);

    $res_catalogue = Database::query("SELECT title FROM $catalogue_table");
    $row_catalogue = Database::fetch_array($res_catalogue);    
    if ($num_topic)  {    
        echo '<div style="width:95%;padding-left:20px;"><div class="section" style="padding-left:10px;line-height:2.5em;">';
        echo '<div class="row"><div class="form_header">'.$row_catalogue['title'].'</div></div>';
        echo '<div><table width="100%">';
        $i = 0;
        while ($row_topic = Database::fetch_array($res_topic)) {
            $topic_id = $row_topic['id'];
            $topic = $row_topic['topic'];
            $sql_programme = "SELECT id,name FROM $tbl_session_category WHERE visible = 1 AND topic = ".$topic_id;			
            $rs_programme = Database::query($sql_programme,__FILE__,__LINE__);
            $num_programme = Database::num_rows($rs_programme);
            echo '<script>
            $(document).ready(function(){			
            $("a#show'.$i.'").click(function () {								
                    for(var k=0;k<='.$num_topic.';k++){							
                            if(k == '.$i.'){								
                                    $("#show_programme"+k).show();								
                            }
                            else {
                                    $("#show_programme"+k).hide();	
                            }
                    }
                });			
            });       
            </script>';
            if($i%2 == 0){
            echo '<tr>';
            }
            echo '<td width="50%" valign="top"><div><a href="javascript:void(0);" id="show'.$i.'"><img align="middle" src="main/img/topic.png">&nbsp;&nbsp;<font size="2">'.$topic.'&nbsp;&nbsp;('.$num_programme.')</font></a></div>';
            echo '<div id= "show_programme'.$i.'" style="display:none;padding-left:12px;">';
            $j = 1;
            echo '<ul>';
                    while ($row_programme = Database::fetch_row($rs_programme)) {
                        echo '<li><a href="main/catalogue/programme_details.php?id='.$row_programme[0].'">'.$row_programme[1].'</a></li>';
                        $j++;
                    }
            echo '</ul>';
            echo '</div></td>';
            if($i%2 <> 0){
                    echo '</tr>';
            }
            $i++;
        }
        echo '</table></div>';
        echo '</div></div>';    
    }
}


/* Home custom html */
if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
	$contents = file_get_contents('./'.$home.$_GET['include']);
        $contents=str_replace('{DEFAULT_CSS_PATH}',api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/default.css',$contents);
	$contents = str_replace('{CURRENT_CSS_PATH}',api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/templates.css', $contents);
        $contents = str_replace('{WEB_PATH}',api_get_path(WEB_PATH), $contents);
        // replace to curren template css path
        if (preg_match('!/css/(.+)/templates.css!', $contents, $matches)) {                
            $contents = str_replace('/'.$matches[1].'/templates.css', '/'.api_get_setting('stylesheets').'/templates.css', $contents);
        }
        // replace to curren default css path
        if (preg_match('!/css/(.+)/default.css!', $contents, $matches)) {                
            $contents = str_replace('/'.$matches[1].'/default.css', '/'.api_get_setting('stylesheets').'/default.css', $contents);
        }	
	echo $contents;
	$page_included = true;
} else {
        $count_lang_availables = LanguageManager::count_available_languages();
        if(!empty($_SESSION['user_language_choice']) && $count_lang_availables > 1) {
                $user_selected_language=$_SESSION['user_language_choice'];
        }else {
                $user_selected_language=api_get_setting('platformLanguage');
        }
        $file_sys_path = api_get_path(SYS_PATH);
        $file_full_path = $file_sys_path.$home.'home_news_'.$user_selected_language.'.html';
        if (!file_exists($file_full_path)) {
            $home_top_file = $file_sys_path.$home.'home_top.html';
            if ($_configuration['multiple_access_urls'] == true) {
                $home_top_file = $file_sys_path.$home.'home_top_'.$user_selected_language.'.html';
            }
            if (file_exists($home_top_file)) {
                    $home_top_temp = file($home_top_file);
            } else {
                    $home_top_temp = file($home_old.'home_top.html');
            }
            $home_top_temp = implode('', $home_top_temp);
            $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
            $open = str_replace('{WEB_PATH}',api_get_path(WEB_PATH), $open);
            $open=str_replace('{CURRENT_CSS_PATH}',api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/templates.css',$open);
            $open=str_replace('{DEFAULT_CSS_PATH}',api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/default.css',$open);
            // replace to curren template css path
            if (preg_match('!/css/(.+)/templates.css!', $open, $matches)) {                
                $open = str_replace('/'.$matches[1].'/templates.css', '/'.api_get_setting('stylesheets').'/templates.css', $open);
            }
            // replace to curren default css path
            if (preg_match('!/css/(.+)/default.css!', $open, $matches)) {                
                $open = str_replace('/'.$matches[1].'/default.css', '/'.api_get_setting('stylesheets').'/default.css', $open);
            }
            echo $open;
	} else {
            if (file_exists($home.'home_top_'.$user_selected_language.'.html')) {
                    $home_top_temp = file_get_contents($home.'home_top_'.$user_selected_language.'.html');
            } else {
                    $home_top_temp = file_get_contents($home.'home_top.html');
            }
            $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
            $open = str_replace('{WEB_PATH}',api_get_path(WEB_PATH), $open);
            $open=str_replace('{CURRENT_CSS_PATH}',api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/templates.css',$open);
            $open=str_replace('{DEFAULT_CSS_PATH}',api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/default.css',$open);
            
            // replace to curren template css path
            if (preg_match('!/css/(.+)/templates.css!', $open, $matches)) {                
                $open = str_replace('/'.$matches[1].'/templates.css', '/'.api_get_setting('stylesheets').'/templates.css', $open);
            }
            // replace to curren default css path
            if (preg_match('!/css/(.+)/default.css!', $open, $matches)) {                
                $open = str_replace('/'.$matches[1].'/default.css', '/'.api_get_setting('stylesheets').'/default.css', $open);
            }
            echo $open;
	}
}



// Display System announcements
$announcement = isset($_GET['announcement']) ? $_GET['announcement'] : -1;
$announcement = intval($announcement);

if (isset($_user['user_id'])) {	
	$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
	$number_of_announcement = SystemAnnouncementManager :: count_announcements($visibility, $announcement);
	if($number_of_announcement > 0){
	echo '<div class="sectiontitle portal_news">';	
	SystemAnnouncementManager :: display_announcements($visibility, $announcement);
	echo '</div>';
	}
} else {
	$number_of_announcement = SystemAnnouncementManager :: count_announcements($visibility, $announcement);
	if($number_of_announcement > 0){
	echo '<div class="sectiontitle portal_news">';
	SystemAnnouncementManager :: display_announcements(VISIBLE_GUEST, $announcement);
	echo '</div>';
	}
}
echo '</div>';

// Display courses and category list
if (!$page_included) {
	if (api_get_setting('display_categories_on_homepage') == 'true') {
		echo '<div class="home_cats">';
	//	display_anonymous_course_list();
		echo '</div>';
	}
}


// display menu: login section + useful weblinks
echo '<div class="menu" id="menu">';
display_anonymous_menu();
echo '</div>';

$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT * FROM $tbl_course WHERE visibility = 3";
$res = Database::query($sql,__FILE__,__LINE__);
$num_rows = Database::num_rows($res);
if($num_rows <> 0){
	echo '<div class="menu" id="menu">';
	echo "<div class=\"section\">";
	echo '<div class="row"><div class="form_header">'.get_lang('OpenCourses').'</div></div><br />';
	echo "	<div class=\"sectioncontent\">";
	while($row = Database::fetch_array($res)){
		$title = $row['title'];
		$directory = $row['directory'];

		echo '<a href="'.api_get_path(WEB_COURSE_PATH).$directory.'/?id_session=0"><img src="main/img/catalogue_22.png" style="vertical-align:text-bottom;">&nbsp;'.$title.'</a><br />';
	}
	echo '</div></div></div>';
	echo '</div>';
}

echo '</div>';

// display the footer
Display :: display_footer();

/**
 * This function handles the logout and is called whenever there is a $_GET['logout']
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function logout()
{
	global $_configuration, $extAuthSource;
	// variable initialisation
	$query_string = '';

	if (!empty($_SESSION['user_language_choice'])) {
		$query_string = '?language='.$_SESSION['user_language_choice'];
	}

	// Database table definition
	$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

	// selecting the last login of the user
	$uid = intval($_GET['uid']);
	$sql_last_connection = "SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='$uid' ORDER BY login_date DESC LIMIT 0,1";
	$q_last_connection = Database::query($sql_last_connection, __FILE__, __LINE__);
	if (Database::num_rows($q_last_connection) > 0) {
		$i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');
	}

	if (!isset($_SESSION['login_as'])) {
		$current_date = date('Y-m-d H:i:s', time());
		$s_sql_update_logout_date = "UPDATE $tbl_track_login SET logout_date='".$current_date."' WHERE login_id='$i_id_last_connection'";
		Database::query($s_sql_update_logout_date, __FILE__, __LINE__);
	}
	LoginDelete($uid, $_configuration['statistics_database']); //from inc/lib/online.inc.php - removes the "online" status

	//the following code enables the use of an external logout function.
	//example: define a $extAuthSource['ldap']['logout']="file.php" in configuration.php
	// then a function called ldap_logout() inside that file
	// (using *authent_name*_logout as the function name) and the following code
	// will find and execute it
	$uinfo = api_get_user_info($uid);
	if (($uinfo['auth_source'] != PLATFORM_AUTH_SOURCE) && is_array($extAuthSource)) {
		if (is_array($extAuthSource[$uinfo['auth_source']])) {
			$subarray = $extAuthSource[$uinfo['auth_source']];
			if (!empty($subarray['logout']) && file_exists($subarray['logout'])) {
				include_once ($subarray['logout']);
				$logout_function = $uinfo['auth_source'].'_logout';
				if (function_exists($logout_function)) {
					$logout_function($uinfo);
				}
			}
		}
	}
	if (api_get_setting('cas_activate') == 'true' )  {
		require_once(api_get_path(SYS_PATH).'main/auth/cas/authcas.php');
		if (cas_is_authenticated() != false ) {
			error_log('cas log out');
			cas_logout();
		}	
	}	
	exit_of_chat($uid);
	api_session_destroy();
	header("Location: index.php$query_string");
	exit();
}

/**
 * This function checks if there are courses that are open to the world in the platform course categories (=faculties)
 *
 * @param unknown_type $category
 * @return boolean
 */
function category_has_open_courses($category) {
	global $setting_show_also_closed_courses;

	$user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
	$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
	$sql_result = Database::query($sql_query, __FILE__, __LINE__);
	while ($course = Database::fetch_array($sql_result)) {
		if (!$setting_show_also_closed_courses) {
			if ((api_get_user_id() > 0
				&& $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
				|| ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
				return true; //at least one open course
			}
		} else {
			if (isset($course['visibility'])){
				return true; //at least one course (does not matter weither it's open or not because $setting_show_also_closed_courses = true
			}
		}
	}
	return false;
}

function display_create_course_link() {
	echo "<li><a href=\"main/create_course/add_course.php\">".get_lang("CourseCreate")."</a></li>";
}

function display_edit_course_list_links() {
	echo "<li><a href=\"main/auth/courses.php\">".get_lang("SortMyCourses")."</a></li>";
}

/**
 * Displays the menu for anonymous users:
 * login form, useful links, help section
 * Warning: function defines globals
 * @version 1.0.1
 * @todo does $_plugins need to be global?
 */
function display_anonymous_menu() {
	global $loginFailed, $_plugins, $_user, $menu_navigation, $css_type;

	$platformLanguage = api_get_setting('platformLanguage');

	if (!($_user['user_id']) || api_is_anonymous($_user['user_id']) ) { // only display if the user isn't logged in
		display_login_form();

		if ($loginFailed) {
			handle_login_failed();
		}

		if (api_number_of_plugins('loginpage_menu') > 0) {
			echo '<div class="note" style="background: none">';
			api_plugin('loginpage_menu');
			echo '</div>';
		}
	}


	// My Account section
	if (isset($_SESSION['_user']['user_id']) && $_SESSION['_user']['user_id'] != 0) {
		// tabs that are deactivated are added here

		$show_menu = false;
		$show_create_link = false;
		$show_course_link = false;

	/*	$display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION["studentview"] != "studentenview");

		if ($display_add_course_link) {
			//display_create_course_link();
			$show_menu = true;
			$show_create_link = true;
		}*/

		if((api_is_allowed_to_create_course() || api_is_session_admin()) && ($_SESSION["studentview"] != "studentenview")){
			$show_menu = true;
			$show_create_link = true;
		}

		if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
				$show_menu = true;
				$show_course_link = true;
		} else {
			if (api_get_setting('allow_students_to_browse_courses') == 'true') {
				$show_menu = true;
				$show_course_link = true;
			}
		}

                if ($css_type == 'tablet') {
                    
                    echo api_display_tool_title(get_lang('MenuUser'),'tablet_title');
                    if ($show_create_link) {
                            display_create_course_link_tablet();
                    }
                    display_edit_course_list_links_tablet();
                    if ($show_digest_link) {
                            display_digest($toolsList, $digest, $orderKey, $courses);
                    }  
                    
                } else {
                    if ($show_menu){                    
			echo "<div class=\"section\">";
			echo "	<div class=\"sectiontitle\">".get_lang("MenuUser")."</div>";
			echo "	<div class=\"sectioncontent\">";
			echo "		<ul class=\"menulist nobullets\">";
			if ($show_create_link) {
				display_create_course_link();
			}
			if ($show_course_link) {
				display_edit_course_list_links();
			}
			echo "		</ul>";
			echo "	</div>";
			echo "</div>";
                    }
                }
                
		
                if (!empty($menu_navigation)) {
                    echo "<div class=\"section\">";
                    echo "<div class=\"sectiontitle\">".get_lang("MainNavigation")."</div>";
                    echo '<div class="sectioncontent">';
                    echo "<ul class=\"menulist nobullets\">";
                    foreach ($menu_navigation as $section => $navigation_info) {
                            $current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
                            echo '<li'.$current.'>';
                            echo '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
                            echo '</li>';
                            echo "\n";
                    }
                    echo "</ul>";
                    echo '</div>';
                    echo '</div>';
                }

		
	}

	// help section
	/*** hide right menu "general" and other parts on anonymous right menu *****/

	$user_selected_language = api_get_interface_language();
	global $home, $home_old;
	if (!isset($user_selected_language)) {
		$user_selected_language = $platformLanguage;
	}

	$menu_content = '';
	if (!file_exists($home.'home_menu_'.$user_selected_language.'.html') && file_exists($home.'home_menu.html') && file_get_contents($home.'home_menu.html') != '') {
		if (file_exists($home.'home_menu_'.$user_selected_language.'.html')) {
			$menu_content = file_get_contents ($home.'home_menu_'.$user_selected_language.'.html');
		} else {
			$menu_content = file_get_contents ($home.'home_menu.html');
		}
	} // More section
	elseif(file_exists($home.'home_menu_'.$user_selected_language.'.html') && file_get_contents($home.'home_menu_'.$user_selected_language.'.html') != '') {
		$menu_content = file_get_contents ($home.'home_menu_'.$user_selected_language.'.html');
	}
        
        if(!empty($menu_content)) {
            $menu_content = str_replace('{WEB_PATH}',api_get_path(WEB_PATH), $menu_content);
            if ($css_type == 'tablet') {     
                echo '<div class="menu-general nobullets">';
                echo api_display_tool_title(get_lang('MenuGeneral'),'tablet_title');
                echo $menu_content;            
                echo '</div>';
            } else {
                echo "<div class=\"section menu-more\">", "<div class=\"sectiontitle\">".get_lang("MenuGeneral")."</div>";
                echo '<div class="sectioncontent nobullets">';
                echo $menu_content;
                echo '</div>';
                echo '</div>';
            }
        }        
	
	if ($_user['user_id'] && api_number_of_plugins('campushomepage_menu') > 0) {
		echo '<div class="note" style="background: none">';
		api_plugin('campushomepage_menu');
		echo '</div>';
	}

	// includes for any files to be displayed below anonymous right menu

	if (!file_exists($home.'home_notice_'.$user_selected_language.'.html') && file_exists($home.'home_notice.html') && file_get_contents($home.'home_notice.html') != '') {
		echo '<div class="actions">';
		if (file_exists($home.'home_notice.html')) {
			include ($home.'home_notice.html');
		} else {
			include ($home_old.'home_notice.html');
		}
		echo '</div>';
	}
	elseif(file_exists($home.'home_notice_'.$user_selected_language.'.html') && file_get_contents($home.'home_notice_'.$user_selected_language.'.html') != '') {
		echo '<div class="actions">';
		include($home.'home_notice_'.$user_selected_language.'.html');
		echo '</div>';
	}
}

/**
*	Reacts on a failed login:
*	displays an explanation with
*	a link to the registration form.
*
*	@version 1.0.1
*/
function handle_login_failed() {
	if (!isset($_GET['error'])) {
		$message = get_lang("InvalidId");
		if (api_is_self_registration_allowed()) {
			$message = get_lang("InvalidForSelfRegistration");
		}
	} else {
		switch ($_GET['error']) {
			case '':
				$message = get_lang('InvalidId');
				if (api_is_self_registration_allowed()) {
					$message = get_lang('InvalidForSelfRegistration');
				}
				break;
			case 'account_expired':
				$message = get_lang('AccountExpired');
				break;
			case 'account_inactive':
				$message = get_lang('AccountInactive');
				break;
			case 'user_password_incorrect':
				$message = get_lang('InvalidId');
				break;
			case 'access_url_inactive':
				$message = get_lang('AccountURLInactive');
				break;
			case 'AdminNotifiedWrongLogin':
				$message = get_lang('AdminNotifiedWrongLogin');
		}
	}
	echo "<div id=\"login_fail\">".$message."</div>";
}

/**
*	Adds a form to let users login
*	@version 1.1
*/
function display_login_form() {
	$form = new FormValidator('formLogin');
	$form->addElement('text', 'login', get_lang('UserName'));
	$form->addElement('password', 'password', get_lang('Pass'));
	$form->addElement('style_submit_button','submitAuth', get_lang('langEnter'), array('class' => 'login'));
	$renderer =& $form->defaultRenderer();
	$renderer->setElementTemplate('<div ><label>{label}</label></div><div>{element}</div>');

    // Add register and lost password in login form - Bug #6821
    if (api_get_setting('allow_lostpassword') == 'true' || api_get_setting('allow_registration') == 'true') {
        $html_ul ='<br/><br/><br/><br/>'; // Space due to that login button is floating
        $html_ul .= '<ul class="menulist nobullets">';
        if (api_get_setting('allow_registration') <> 'false') {
           $html_ul.= '<li><a href="main/auth/inscription.php">'.get_lang('Reg').'</a></li>';
        }
        if (api_get_setting('allow_lostpassword') == 'true') {
          $html_ul.= '<li><a href="main/auth/lostPassword.php">'.get_lang("LostPassword").'</a></li>';
        }
        $html_ul.='</ul>';
        // Add html element, Button
	    $form->addElement('html',$html_ul);
    }

    $form->display();

	if (api_get_setting('openid_authentication') == 'true') {
		include_once 'main/auth/openid/login.php';
                echo '<div class="section">
                        <div class="sectiontitle">'.get_lang('OpenIdAuthentication').'</div>
                        <div class="sectioncontent nobullets">';
                        echo openid_form();
                        echo '<br/><br/>
                        </div>';

                echo '</div>';
	}
        //Enter with cas for Paris5
        if (api_get_setting('cas_activate') == 'true') {
                echo '<div class="section">
                        <div class="sectiontitle">'.get_lang('HomeExternalAuthentication').'</div>
                        <div class="sectioncontent nobullets">';
                        echo '<form action="main/auth/cas/logincas.php" method="post" id="loginform" name="formLogin">
                            <input type="hidden" name="isportalcas"  value="" />
                            <button type="submit" name="submitAuth" class="login">'.get_lang('CasLogin').'</button>
                        </form>
                        <br/><br/>
                        </div>';

                echo '</div>';

        }
}

/**
 * Displays a link to the lost password section
 * Possible deprecated function
 */
function display_lost_password_info() {
	echo "<li><a href=\"main/auth/lostPassword.php\">".get_lang("LostPassword")."</a></li>";
}

/**
* Display list of courses in a category.
* (for anonymous users)
*
* @version 1.1
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
*/
function display_anonymous_course_list() {
	$ctok = $_SESSION['sec_token'];
	$stok = Security::get_token();

	//init
	$user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
	$web_course_path = api_get_path(WEB_COURSE_PATH);
	$category = Database::escape_string($_GET['category']);
	global $setting_show_also_closed_courses;

	// Database table definitions
	$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
	$main_category_table 	= Database :: get_main_table(TABLE_MAIN_CATEGORY);

	$platformLanguage = api_get_setting('platformLanguage');

	//get list of courses in category $category
	$sql_get_course_list = "SELECT * FROM $main_course_table cours
								WHERE category_code = '".Database::escape_string($_GET["category"])."'
								ORDER BY title, UPPER(visual_code)";

	//showing only the courses of the current access_url_id
	global $_configuration;
	if ($_configuration['multiple_access_urls'] == true) {
		$url_access_id = api_get_current_access_url_id();
		if ($url_access_id !=-1) {
			$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
			$sql_get_course_list = "SELECT * FROM $main_course_table as course INNER JOIN $tbl_url_rel_course as url_rel_course
					ON (url_rel_course.course_code=course.code)
					WHERE access_url_id = $url_access_id AND category_code = '".Database::escape_string($_GET["category"])."' ORDER BY title, UPPER(visual_code)";
		}
	}

	//removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
	$sql_result_courses = Database::query($sql_get_course_list, __FILE__, __LINE__);

	while ($course_result = Database::fetch_array($sql_result_courses)) {
		$course_list[] = $course_result;
	}

	$platform_visible_courses = '';
	// $setting_show_also_closed_courses
	if($user_identified) {
		if ($setting_show_also_closed_courses) {
			$platform_visible_courses = '';
		} else {
			$platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' OR t3.visibility='".COURSE_VISIBILITY_OPEN_PLATFORM."' )";
		}
	} else {
		if ($setting_show_also_closed_courses) {
			$platform_visible_courses = '';
		} else {
			$platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' )";
		}
	}
	$sqlGetSubCatList = "
				SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
				FROM $main_category_table t1
				LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
				LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
				WHERE t1.parent_id ". (empty ($category) ? "IS NULL" : "='$category'")."
				GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";


	//showing only the category of courses of the current access_url_id
	global $_configuration;
	if ($_configuration['multiple_access_urls'] == true) {
		$url_access_id = api_get_current_access_url_id();
		if ($url_access_id != -1) {
			$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
			$sqlGetSubCatList = "
				SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
				FROM $main_category_table t1
				LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
				LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
				INNER JOIN $tbl_url_rel_course as url_rel_course
					ON (url_rel_course.course_code=t3.code)
				WHERE access_url_id = $url_access_id AND t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
				GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";
		}
	}

	$resCats = Database::query($sqlGetSubCatList, __FILE__, __LINE__);
	$thereIsSubCat = false;
	if (Database::num_rows($resCats) > 0) {
		$htmlListCat = "<h4 style=\"margin-top: 0px;\">".get_lang("CatList")."</h4>"."<ul>";
		while ($catLine = Database::fetch_array($resCats)) {
			if ($catLine['code'] != $category) {

				$category_has_open_courses = category_has_open_courses($catLine['code']);
				if ($category_has_open_courses) {
					//the category contains courses accessible to anonymous visitors
					$htmlListCat .= "<li>";
					$htmlListCat .= "<a href=\"".api_get_self()."?category=".$catLine['code']."\">".$catLine['name']."</a>";
					if (api_get_setting('show_number_of_courses') == 'true') {
						$htmlListCat .= " (".$catLine['nbCourse']." ".get_lang("Courses").")";
					}
					$htmlListCat .= "</li>\n";
					$thereIsSubCat = true;
				} elseif ($catLine['children_count'] > 0) {
					//the category has children, subcategories
					$htmlListCat .= "<li>";
					$htmlListCat .= "<a href=\"".api_get_self()."?category=".$catLine['code']."\">".$catLine['name']."</a>";
					$htmlListCat .= "</li>\n";
					$thereIsSubCat = true;
				}
				/************************************************************************
				 end changed code to eliminate the (0 courses) after empty categories
				 ************************************************************************/
				elseif (api_get_setting('show_empty_course_categories') == 'true') {
					$htmlListCat .= "<li>";
					$htmlListCat .= $catLine['name'];
					$htmlListCat .= "</li>\n";
					$thereIsSubCat = true;
				} //else don't set thereIsSubCat to true to avoid printing things if not requested
			} else {
				$htmlTitre = "<p>";
				if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
					$htmlTitre .= "<a href=\"".api_get_self()."\">"."&lt;&lt; ".get_lang("BackToHomePage")."</a>";
				}
				if (!is_null($catLine['parent_id']) || (api_get_setting('show_back_link_on_top_of_tree') <> 'true' && !is_null($catLine['code']))) {
					$htmlTitre .= "<a href=\"".api_get_self()."?category=".$catLine['parent_id']."\">"."&lt;&lt; ".get_lang('Up')."</a>";
				}
				$htmlTitre .= "</p>\n";
				if ($category != "" && !is_null($catLine['code'])) {
					$htmlTitre .= "<h3>".$catLine['name']."</h3>\n";
				} else {
					$htmlTitre .= "<h3>".get_lang("Categories")."</h3>\n";
				}
			}
		}
		$htmlListCat .= "</ul>\n";
	}
	echo $htmlTitre;
	if ($thereIsSubCat) {
		echo $htmlListCat;
	}
	while ($categoryName = Database::fetch_array($resCats)) {
		echo "<h3>", $categoryName['name'], "</h3>\n";
	}
	$numrows = Database::num_rows($sql_result_courses);
	$courses_list_string = '';
	$courses_shown = 0;
	if ($numrows > 0) {
		if ($thereIsSubCat) {
			$courses_list_string .= "<hr size=\"1\" noshade=\"noshade\">\n";
		}
		$courses_list_string .= "<h4 style=\"margin-top: 0px;\">".get_lang("CourseList")."</h4>\n"."<ul>\n";

		if (api_get_user_id()) {
			$courses_of_user = get_courses_of_user(api_get_user_id());
		}

		foreach ($course_list as $course) {
			// $setting_show_also_closed_courses

			if ($setting_show_also_closed_courses == false) {
				// if we do not show the closed courses
				// we only show the courses that are open to the world (to everybody)
				// and the courses that are open to the platform (if the current user is a registered user
				if( ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
					$courses_shown++;
					$courses_list_string .= "<li>\n";
				$courses_list_string .= "<a href=\"".$web_course_path.$course['directory']."/\">".$course['title']."</a><br />";
				if (api_get_setting('display_coursecode_in_courselist') == 'true') {
					$courses_list_string .= $course['visual_code'];
				}
				if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
					$courses_list_string .= ' - ';
				}
				if (api_get_setting('display_teacher_in_courselist') == 'true') {
					$courses_list_string .= $course['tutor_name'];
				}
					if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
						$courses_list_string .= ' - '.$course['course_language'];
					}
					$courses_list_string .= "</li>\n";
				}
			}
			// we DO show the closed courses.
			// the course is accessible if (link to the course homepage)
			// 1. the course is open to the world (doesn't matter if the user is logged in or not): $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)
			// 2. the user is logged in and the course is open to the world or open to the platform: ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
			// 3. the user is logged in and the user is subscribed to the course and the course visibility is not COURSE_VISIBILITY_CLOSED
			// 4. the user is logged in and the user is course admin of te course (regardless of the course visibility setting)
			// 5. the user is the platform admin api_is_platform_admin()
			//
			else {
				$courses_shown++;
				$courses_list_string .= "<li>\n";
				if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
						|| ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
						|| ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
						|| $courses_of_user[$course['code']]['status'] == '1'
						|| api_is_platform_admin()) {
					$courses_list_string .= "<a href=\"".$web_course_path.$course['directory']."/\">";
				}
				$courses_list_string .= $course['title'];
				if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
						|| ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
						|| ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
						|| $courses_of_user[$course['code']]['status'] == '1'
						|| api_is_platform_admin()) {
					$courses_list_string .= "</a><br />";
				}
				if (api_get_setting('display_coursecode_in_courselist') == 'true') {
					$courses_list_string .= $course['visual_code'];
				}
				if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
					$courses_list_string .= ' - ';
				}
				if (api_get_setting('display_teacher_in_courselist') == 'true') {
					$courses_list_string .= $course['tutor_name'];
				}
				if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
					$courses_list_string .= ' - '.$course['course_language'];
				}
				if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
					$courses_list_string .= ' - '.$course['course_language'];
				}
				// We display a subscription link if
				// 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
				// 2
				if ($user_identified && !key_exists($course['code'], $courses_of_user)) {
					if ($course['subscribe'] == '1') {
						$courses_list_string .= "<form action=\"main/auth/courses.php?action=subscribe&category=".$_GET['category']."\" method=\"post\">";
						$courses_list_string .= '<input type="hidden" name="sec_token" value="'.$stok.'">';
						$courses_list_string .= "<input type=\"hidden\" name=\"subscribe\" value=\"".$course['code']."\" />";
						$courses_list_string .= "<input type=\"image\" name=\"unsub\" src=\"main/img/enroll.gif\" alt=\"".get_lang("Subscribe")."\" />".get_lang("Subscribe")."</form>";
					} else {
						$courses_list_string .= '<br />'.get_lang("SubscribingNotAllowed");
					}
				}
				$courses_list_string .= "</li>\n";
			}
		}
		$courses_list_string .= "</ul>\n";
	} else {
		// echo "<blockquote>",get_lang('_No_course_publicly_available'),"</blockquote>\n";
	}
	if ($courses_shown > 0) { //only display the list of courses and categories if there was more than
		// 0 courses visible to the world (we're in the anonymous list here)
		echo $courses_list_string;
	}
	if ($category != '') {
		echo "<p>", "<a href=\"".api_get_self()."\"> ", Display :: return_icon('back.png', get_lang('BackToHomePage')), get_lang("BackToHomePage"), "</a>", "</p>\n";
	}
}

/**
 * retrieves all the courses that the user has already subscribed to
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @param int $user_id: the id of the user
 * @return array an array containing all the information of the courses of the given user
*/
function get_courses_of_user($user_id) {
	$table_course		= Database::get_main_table(TABLE_MAIN_COURSE);
	$table_course_user	= Database::get_main_table(TABLE_MAIN_COURSE_USER);

	// Secondly we select the courses that are in a category (user_course_cat <> 0) and sort these according to the sort of the category
	$user_id = intval($user_id);
	$sql_select_courses = "SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
								course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
								course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
								FROM    $table_course       course,
										$table_course_user  course_rel_user
								WHERE course.code = course_rel_user.course_code
								AND   course_rel_user.user_id = '".$user_id."'
								ORDER BY course_rel_user.sort ASC";
	$result = Database::query($sql_select_courses, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result)) {
		// we only need the database name of the course
		$courses[$row['k']] = array('db' => $row['db'], 'code' => $row['k'], 'visual_code' => $row['vc'], 'title' => $row['i'], 'directory' => $row['dir'], 'status' => $row['status'], 'tutor' => $row['t'], 'subscribe' => $row['subscr'], 'unsubscribe' => $row['unsubscr'], 'sort' => $row['sort'], 'user_course_category' => $row['user_course_cat']);
	}
	return $courses;
}

/**
 * Enter description here...
 *
 */
function display_create_course_link_tablet() {
	echo "<a href=\"main/create_course/add_course.php\">".Display::return_icon('pixel.gif', get_lang('CourseCreate'), array('class' => 'homepage_button homepage_create_course','align'=>'absmiddle')).get_lang('CourseCreate')."</a><br/>";
}

/**
 * Enter description here...
 *
 */
function display_edit_course_list_links_tablet() {
	echo "<a href=\"main/auth/courses.php\">".Display::return_icon('pixel.gif', get_lang('SortMyCourses'), array('class' => 'homepage_button homepage_catalogue','align'=>'absmiddle')).get_lang('SortMyCourses')."</a>";
}
