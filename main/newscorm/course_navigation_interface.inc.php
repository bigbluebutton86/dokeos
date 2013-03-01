<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* 	Learning Path
*	Functions to render html for new Dokeos 2.0 styled interface in course view or others 
*	generate menu with main button with toggle jquery effect onclick
*	needs JQuery for JS scripts
*	@package dokeos.learnpath
*/

/**
 * Convert Toc to menuItemm for using renderCourseToggleMenu()
 * @param array tocs (from learnpath::get_toc())
 * @return array list of item menu with correct keys, or false on failure
*/
 function getMenuItemsFromToc($tocs = array(), $currentId = 1){
 		if(empty($tocs) || !is_array($tocs))		return false;
		 		
 		$menuItems = array();
 		 		
		for( $i=0 ; $i < count($tocs) ; $i++ ){
			$item = array();
			$currentClass = ($currentId == $tocs[$i]['id'])		?	" current"	: "";

			$item ['href']= "#";
			$item ['onclick']= 'javascript:switch_item('.$currentId.', '.$tocs[$i]['id'].'); hideCourseMenu(); return false;';
			$item ['text']= $tocs[$i]['title'];			
			$item ['class'] = str_replace(" ", '_', $tocs[$i]['status']) .  $currentClass;
			$item ['id'] = $tocs[$i]['id'];

			$menuItems []= $item;
		}
		
		return $menuItems;
 }
 
 /**
  * Render HTML Code to display top header in course view
  * @param string - title of current page
  * @return string - HTML code
  * 
  */ 
 function renderCourseHeader($title = "", $progressBar, $menuItems, $charset){
	  global $_course;
	
	$html =	"<div id='left'>";
	$html .= '<a id="back2home" class="course_main_home_button" href="lp_controller.php?'.api_get_cidreq().'&action=return_to_course_homepage" target="_self" onclick="window.parent.API.save_asset();" alt="'.$altHome.'" title="'.$altHome.'">';
	$html .= '<img src="'.api_get_path(WEB_IMG_PATH).'spacer.gif" width="42px" height="37px"  alt="'.$altHome.'" title="'.$altHome.'" />';
	$html .= '</a>';
 	$html .= "</div>";
	
 	$html.=	"<div id='courseTitle'>" .								// title + progress bar
 				"<div class='container'>".$title.renderProgressBar($progressBar)."</div>".
			"</div>";
 	
 	$html .= "<div id='bg_end_title'></div>";
 			
	$altHome = api_convert_encoding(get_lang('CourseHomepageLink'), $charset, api_get_system_encoding());		
 	

  if (count($menuItems)> 1) {
    $arrows = renderNavigationArrows();	// no navigation buttons if just one page

    $html.=	"<div id='right'>" .									// menu button + previous / next
       renderCourseToggleMenu($menuItems).
       $arrows.
      "</div>";
  } else {
    $html.=	"<div id='right'>&nbsp;</div>";
  }

 	return $html;
 }

 /**
  * Render HTML Code to display top header of the Author tool (copy of renderCourseHeader function)
  * This function is called in /main/inc/tool_header.inc.php
  * @param string - title of current page
  * @return string - HTML code
  *
  */
 function display_author_header(){
   global $_course, $charset;

   // Check if the Lp object exists
   if (isset($_SESSION['lpobject'])) {
    if ($debug > 0)
     error_log('New LP - SESSION[lpobject] is defined', 0);
    $oLP = unserialize($_SESSION['lpobject']);
    if (is_object($oLP)) {
     if ($debug > 0)
      error_log('New LP - oLP is object', 0);
     if ($myrefresh == 1 OR (empty($oLP->cc)) OR $oLP->cc != api_get_course_id()) {
      if ($debug > 0)
       error_log('New LP - Course has changed, discard lp object', 0);
      if ($myrefresh == 1) {
       $myrefresh_id = $oLP->get_id();
      }
      $oLP = null;
      api_session_unregister('oLP');
      api_session_unregister('lpobject');
     } else {
      $_SESSION['oLP'] = $oLP;
      $lp_found = true;
     }
    }
   }

		 // Get tocs from learnpath and convert for re-using in toggle menu
		 $currentId = $_SESSION['oLP']->current;

   // Get menu items
   $menuItems = getMenuItemsFromToc($_SESSION['oLP']->get_toc(), $currentId);
   $title = api_convert_encoding($_SESSION['oLP']->get_name(), $charset, api_get_system_encoding());

  // Html header for the Author tool
 	$html=	"<div id='left'>" .										// home button
			"<a id=\"back2home\" class='course_main_home_button' width='42px' height='37px' href=".api_get_path(WEB_COURSE_PATH).$_course['path'].'/index.php'.">";
 	$html .= '<img src="'.api_get_path(WEB_IMG_PATH).'spacer.gif" width="42px" height="37px" target="_self" onclick="window.parent.API.save_asset();" alt="'.$altHome.'" title="'.$altHome.'" />';
 	$html .= '</a><div style="padding-top: 5px; padding-left: 53px;">'.
	 		 renderCourseToggleMenu($menuItems).
 			"</div></div>";
 	$html.=	"<div id='courseTitle'>" .								// Title
 				"<div class='container'>".$title."</div></div>".
 				"<div id='bg_end_title'></div>".
			"";
	 $altHome = api_convert_encoding(get_lang('CourseHomepageLink'), $charset, api_get_system_encoding());
  
 	$arrows = "";	// no navigation buttons if only one page
  $lp_count = $_SESSION['oLP']->get_total_learning_path_count();
  if ($lp_count >= 1) {
    $lp_id = Security::remove_XSS($_GET['lp_id']);
    // Get the previous and next Lp ID
    $lp_info = $_SESSION['oLP']->get_previous_and_next_lp($lp_id);
    $lp_previous = $lp_info['previous']; // Previous Lp ID
    $lp_next = $lp_info['next']; // Next Lp ID
    
    $controller = api_get_path(WEB_PATH).'main/newscorm/lp_controller.php';
    // Arrows
    $arrows = '<a class="prev_button" href="'.$controller.'?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.$lp_previous.'">'.
    '<img id="coursepreviousbutton" src="../img/spacer.gif" class="button" title="'.get_lang('Previous').'" /></a>'.
    '<a class="next_button" href="'.$controller.'?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.$lp_next.'">'.
    '<img id="coursenextbutton" src="../img/spacer.gif" class="button" title="'.get_lang('Next').'"/>'.
    '</a>';
  }
  //if($_REQUEST['action'] != "add_item" || $_REQUEST['type'] != "step")
  if($_REQUEST['action'] == 'view')
  {
 	$html.=	"<div id='right'>". // menu button + previous / next
	 			$arrows.
 			"</div>";
  }

 	return $html;
 }
/**
 * Render htmlcode to display simple header (with only home button)
 */
 function renderSimpleHeader(){
	
	$altHome = api_convert_encoding(get_lang('CourseHomepageLink'), $charset, api_get_system_encoding());		
 	$html =	"<a class='course_main_home_button' href='".api_get_path(WEB_COURSE_PATH).$_course['path'].'/index.php'."' target='_self' onclick='window.parent.API.save_asset();' alt='$altHome' title='$altHome'>".
 				"<img id='courseMainHomeButton' src='../img/tool_header_home.png' />" .
 			"</a>";
 	return $html;
 }
 
 /**
  * Return HTML code for menu button with togggled menu
  * with new style of Dokeos 2.0
  * @param array $items - array of menu items (each item must contain keys: text, onclick, href  )
  * @param int $maxItemsWithoutScrollingBar - if less items that than int, no scrollbar appears 
  * @return string HTML code
  * @since 2010.09.03
  */
 function renderCourseToggleMenu( $items = array(), $maxItemsWithoutScrollingBar = 15 ){
 	if(empty($items) || !is_array($items))		return false;
 	
 	// main button
 	$html .= '<a class ="course_menu_button" href="#" onclick="javascript:$(\'#courseToggleMenu\').toggle(\'slow\'); return false;">';
	$html .= '<img id="courseMenuButton" src="../img/spacer.gif" />';
	$html .= "</a>";
 	
	//hidden menu
 	$html .= "<div id='courseToggleMenu' style='display:none;'>";
 	
 	$ulStyle = (count($items) > $maxItemsWithoutScrollingBar) ? "scrollbar" : "";
 	
 	// TODO : JQuery scrollbar with sexy style to match screenshot of Françoise: http://www.kelvinluck.com/assets/jquery/jScrollPane/jScrollPane.html
 	
 	$html .= "<ul class='$ulStyle'>";
 	foreach($items as $item){
		$text = 	(array_key_exists('text', $item))		?	$item['text'] 		: "menu item";
		$onclick =	(array_key_exists('onclick', $item))	?	$item['onclick'] 	: "javascript:return false;";
		$href =		(array_key_exists('href', $item))		?	$item['href'] 		: "#";
		$class = 	(array_key_exists('class', $item))		?	$item['class'] 		: "";
		if (isset($_GET['action']) && $_GET['action']=="view") {// Allow display resource on click event
			$html.= "<li id=\"toggle_menu_item_".$item['id']."\" onclick='$onclick' class='$class'>";
			$html.= "<a href='$href' onclick='$onclick'>$text</a>";
        } else {// Allow display just informative items
           $html.= "<li  class='$class'>";
           $html.= "<span style='cursor:default;'>$text</span>";
        }
		/*$html.= "<li onclick='$onclick' class='$class'>";
		$html.= "<a href='$href' onclick='$onclick'>$text</a>";*/
		$html.= "</li>";
	}
	$html .= "</ul>";
	$html .= "</div>";
	return  $html;
 }
 

 /**
  * Render previous + next buttons in header
  * @return string - HTML Code img buttons
  */
 function renderNavigationArrows(){
 	$html = '<a class="prev_button" href="#" onclick="javascript:switch_item(3,\'previous\'); hideCourseMenu(); return false;">'.
			'<img id="coursepreviousbutton" src="../img/spacer.gif" class="button"/>'.
			'</a>'.
			'<a class="next_button" href="#" onclick="javascript:switch_item(3,\'next\'); hideCourseMenu(); return false;">'.
 			'<img id="coursenextbutton" src="../img/spacer.gif" class="button"/>'.
			'</a>';
 			
 	return $html;
 }
 
 
 /**
  * Render top(default) or bottom toolbar
  */
 function renderToolbar($items = array(), $top = true){
 	$txt = ($top) ? "top" : "bottom";
 	$html=	"";
 	$html.=	"<div id='".$txt."_toolbar' class='toolbar radiant rounded'>";
 	if(!empty($items)){
	 	foreach($items as $i){
	 		$html.=	"<div class='float_l'>";
	 		$html.=	$i;
	 		$html.=	"</div>";
	 	}
 	}
 	$html.="</div>";
 	return $html;
 }
 
  /**
  * Render HTML Code to display progress bar
  * @return string HTML code
  */
 function renderProgressBar($val){
 	if(!is_array($val))		return "";

 	if($val[1] == "%") 		$width = $val[0].$val[1];																	// i.e 33%	
 	else					$width = strval(intval((intval($val[0]) / intval(	substr($val[1],1)	)) *100))."%";		// i.e 10/33 => 30 %
 	
 	$html = "<div id='progressBar'>";
 	$html .= "<div id='percent' style='width:$width'></div>";
 	$html .= "</div>";
 	
 	return $html;
 }
 
?>