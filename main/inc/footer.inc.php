<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This script displays the footer that is below (almost)
*	every Dokeos web page.
*
*	@package dokeos.include
==============================================================================
*/

/**** display of tool_navigation_menu according to admin setting *****/
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
global $_course;

if($_SESSION['studentview'] == "studentview"){
	$_SESSION['studentview'] = "teacherview";
}

if (api_get_setting('show_navigation_menu') != 'false') {

   $course_id = api_get_course_id();
   if (!empty($course_id) && ($course_id != -1)) {
   		if ( api_get_setting('show_navigation_menu') != 'icons') {
	    	echo '</div> <!-- end #center -->';
    		echo '</div> <!-- end #centerwrap -->';
		}
      	require_once api_get_path(INCLUDE_PATH).'tool_navigation_menu.inc.php';
      	show_navigation_menu();
   }
}
/***********************************************************************/
?>
 <div class="clear">&nbsp;</div> <!-- 'clearing' div to make sure that footer stays below the main and right column sections -->
</div> <!-- end of #main" started at the end of banner.inc.php -->

<div class="push"></div>
</div> <!-- end of #wrapper section -->

<div id="footer"> <!-- start of #footer section -->
	<div id="footerinner">
		<div id="bottom_corner"></div>
		<div class="copyright">
		<?php
		global $_configuration,$charset;
		$platform_lang_var = api_convert_encoding(get_lang('Platform'), $charset, api_get_system_encoding());

		echo $platform_lang_var, ' <a href="http://www.dokeos.com" target="_blank">Dokeos ', $_configuration['dokeos_version'], '</a>';
		// Server mode indicator.
		if (api_is_platform_admin()) {
			if (api_get_setting('server_type') == 'test') {
				echo ' <a href="'.api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Platform#server_type">';
				echo '<span style="background-color: white; color: red; border: 1px solid red;">&nbsp;Test&nbsp;server&nbsp;mode&nbsp;</span></a>';
			}
		}
		?>
		</div>

<?php
/*
-----------------------------------------------------------------------------
	Plugins for footer section
-----------------------------------------------------------------------------
*/
api_plugin('footer');

if (api_get_setting('show_administrator_data')=='true') {
	$manager_lang_var = api_convert_encoding(get_lang('Manager'), $charset, api_get_system_encoding());
	// Platform manager
	echo	'<span id="platformmanager">'.
				$manager_lang_var . ' : ' . Display::encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))) .
			'</span>';

}

if (api_get_setting('show_tutor_data')=='true'){
	$coach_lang_var = api_convert_encoding(get_lang('Coachs'), $charset, api_get_system_encoding());
	// course manager
	$id_course=api_get_course_id();
	$id_session=api_get_session_id();
	if (isset($id_course) && $id_course!=-1) {
		echo '<span id="coursemanager">';
		if ($id_session!=0){
			$coachs_email=CourseManager::get_email_of_tutor_to_session($id_session,$id_course);

				$email_link = array();
				foreach ($coachs_email as $coach_email) {				
					foreach ($coach_email as $email=>$username) {
						$email_link[] = Display::encrypted_mailto_link($email,$username);
					}
				}				
			echo '&nbsp;'.$coach_lang_var." : ".implode("&nbsp;|&nbsp;",$email_link);				
		}
		echo '</span>';
	}

}

if (api_get_setting('show_teacher_data')=='true') {
	// course manager
	$teachers_lang_var = api_convert_encoding(get_lang('Teachers'), $charset, api_get_system_encoding());
	$teacher_lang_var = api_convert_encoding(get_lang('Teacher'), $charset, api_get_system_encoding());

	$id_course=api_get_course_id();
	if (isset($id_course) && $id_course!=-1) {
		echo '<span id="coursemanager">';
		$mail=CourseManager::get_emails_of_tutors_to_course($id_course);
		if (!empty($mail)) {
			if (count($mail)>1){
				$bar='&nbsp;|&nbsp;';
				echo '&nbsp;'.$teachers_lang_var.' : ';
			} else {
				$bar='';
				echo '&nbsp;'.$teacher_lang_var.' : ';
			}
			foreach ($mail as $value=>$key) {
				foreach ($key as $email=>$name){
					echo Display::encrypted_mailto_link($email,$name).$bar;
				}
			}
		}
		echo '</span>';
	}

}
/*--- users online ---*/
$usersonline_lang_var = api_convert_encoding(get_lang('UsersOnline'), $charset, api_get_system_encoding());

if ((api_get_setting('showonline', 'world') == 'true' && !$_user['user_id']) || ((api_get_setting('showonline', 'users') == 'true' || api_get_setting('showonline', 'course') == 'true') && $_user['user_id'])) {
	if(!empty($_course['id'])) {
		$user_list = Who_is_online_in_this_course($_user['user_id'], api_get_setting('time_limit_whosonline'), $_course['id']);
	} else {
		$user_list = WhoIsOnline($_user['user_id'], $_configuration['statistics_database'], api_get_setting('time_limit_whosonline'));
	}
	echo '&nbsp;<span class="usersonline"><span class="usersonlinetitle">'. $usersonline_lang_var . ' : </span><span class="usersonlinecontent">'.count($user_list).'</span><span class="usersonlineicon"></span></span>';
}

?>&nbsp;
	</div>
</div> <!-- end of #footer -->
</body>
</html>
