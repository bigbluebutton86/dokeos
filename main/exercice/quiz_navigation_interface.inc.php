<?php

 /**
  * Render HTML Code to display top header of the Author tool (copy of renderCourseHeader function)
  * This function is called in /main/inc/tool_header.inc.php
  * @param string - title of current page
  * @return string - HTML code
  *
  */
 function display_quiz_author_header(){
   global $_course,$charset;
   $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST, $db_name);
   if(isset($_GET['exerciseId']))
   {
	   $exerciseId = $_GET['exerciseId'];
   }
   elseif(isset($_GET['fromExercise']))
   {
	   $exerciseId = $_GET['fromExercise'];
   }
   $exerciseId = Security::remove_XSS($exerciseId);
   $sql = "SELECT title FROM $TBL_EXERCICES WHERE id = ".Database::escape_string($exerciseId);
   $result = Database::query($sql, __FILE__, __LINE__);
   
   while ($row = Database::fetch_array($result)) {
		$title = $row['title'];
	}
   $title = api_convert_encoding($title, $charset, api_get_system_encoding());

  // Html header for the Author tool
 	$html =	"<div id='left'>" .										// home button
			"<a id=\"back2home\" class='course_main_home_button' width='42px' height='37px' href=".api_get_path(WEB_COURSE_PATH).$_course['path'].'/index.php'.">";
 	$html .= '<img src="'.api_get_path(WEB_IMG_PATH).'spacer.gif" width="42px" height="37px" target="_self" onclick="window.parent.API.save_asset();" alt="'.$altHome.'" title="'.$altHome.'" /></a></div>';
 	$html.=	"<div id='courseTitle'>" .								// Title
 				"<div class='container'>".$title."</div></div>".
 				"<div id='bg_end_title'></div>".
			"";
	 
  
 	return $html;
 }
?>