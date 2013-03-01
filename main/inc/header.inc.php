<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This script displays the Dokeos header.
*
*	@package dokeos.include
==============================================================================
*/

/*----------------------------------------
              HEADERS SECTION
  --------------------------------------*/

/*
 * HTTP HEADER
 */
if ($_cid <> -1 && isset($_GET['cidReq']) && !isset($_GET['course_scenario'])) { // In the future we should have only ONE header file, message tool was added to group tool. so that we need show the course header instead the header file
    require_once api_get_path(INCLUDE_PATH).'tool_header.inc.php';
} else {
//Give a default value to $charset. Should change to UTF-8 some time in the future.
//This parameter should be set in the platform configuration interface in time.
$charset = api_get_setting('platform_charset');
if(empty($charset))
{
	$charset = 'ISO-8859-15';
}

//header('Content-Type: text/html; charset='. $charset)
//	or die ("WARNING : it remains some characters before &lt;?php bracket or after ?&gt end");

header('Content-Type: text/html; charset='. $charset);
header('X-Powered-By: Dokeos');
if ( isset($httpHeadXtra) && $httpHeadXtra )
{
	foreach($httpHeadXtra as $thisHttpHead)
	{
		header($thisHttpHead);
	}
}

// Get language iso-code for this page - ignore errors
// The error ignorance is due to the non compatibility of function_exists()
// with the object syntax of Database::get_language_isocode()
@$document_language = Database::get_language_isocode($language_interface);
if(empty($document_language))
{
  //if there was no valid iso-code, use the english one
  $document_language = 'en';
}

/*
 * HTML HEADER
 */

?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
<head>
<title>
<?php
if(!empty($nameTools))
{
	echo $nameTools.' - ';
}

if(!empty($_course['official_code']))
{
	echo $_course['official_code'].' - ';
}

echo api_get_setting('siteName');

?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
<?php

$platform_theme= api_get_setting('stylesheets'); 	// plataform's css
$my_style=$platform_theme;

if(api_get_setting('user_selected_theme') == 'true')
{
	$useri = api_get_user_info();
	$user_theme = $useri['theme'];
	if(!empty($user_theme) && $user_theme != $my_style)
	{
		$my_style = $user_theme;					// user's css
	}
}
$mycourseid = api_get_course_id();
if (!empty($mycourseid) && $mycourseid != -1)
{
	if (api_get_setting('allow_course_theme') == 'true')
	{
		$mycoursetheme=api_get_course_setting('course_theme', null, true);

		if (!empty($mycoursetheme) && $mycoursetheme!=-1)
		{
			if(!empty($mycoursetheme) && $mycoursetheme != $my_style)
			{
				$my_style = $mycoursetheme;		// course's css
			}
		}

		$mycourselptheme=api_get_course_setting('allow_learning_path_theme');
		if (!empty($mycourselptheme) && $mycourselptheme!=-1 && $mycourselptheme== 1)
		{

			global $lp_theme_css; //  it comes from the lp_controller.php
			global $lp_theme_config; // it comes from the lp_controller.php

			if (!$lp_theme_config)
			{
				if ($lp_theme_css!='')
				{
					$theme=$lp_theme_css;
					if(!empty($theme) && $theme != $my_style)
					{
						$my_style = $theme;	 // LP's css
					}
				}
			}
		}
	}
}

global $show_learn_path;

if ($show_learn_path)
{
	$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/learnpath.css"/>';
	$htmlHeadXtra[] = "<link rel='stylesheet' type='text/css' href='dtree.css' />"; //will be moved
	$htmlHeadXtra[] = "<script src='dtree.js' type='text/javascript'></script>"; //will be moved
}

$my_code_path = api_get_path(WEB_CODE_PATH);
// A lot of portals are using old themes that doesn't exists anymore, this change should be done in the migration file
$theme_exists = true;
if (!file_exists(api_get_path(SYS_CODE_PATH).'css/'.$my_style.'/default.css')) {
	$theme_exists = false;
}
if(empty($my_style) || $theme_exists === false) {// If theme in 1.8 platform doesn't exists then we are loading the platform theme
	$my_style = $platform_theme;
}
echo '@import "'.$my_code_path.'css/'.$my_style.'/default.css";'."\n";
//echo '@import "'.$my_code_path.'css/'.$my_style.'/course.css";'."\n";
?>
/*]]>*/
</style>
<style type="text/css" media="print">
/*<![CDATA[*/
<?php
  echo '@import "'.$my_code_path.'css/'.$my_style.'/print.css";'."\n";
?>
/*]]>*/
</style>

<link rel="top" href="<?php echo api_get_path(WEB_PATH); ?>index.php" title="" />
<link rel="courses" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/courses.php" title="<?php echo api_htmlentities(get_lang('OtherCourses'),ENT_QUOTES,$charset); ?>" />
<link rel="profil" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/profile.php" title="<?php echo api_htmlentities(get_lang('ModifyProfile'),ENT_QUOTES,$charset); ?>" />
<link href="http://www.dokeos.com/documentation.php" rel="Help" />
<link href="http://www.dokeos.com/team.php" rel="Author" />
<link href="http://www.dokeos.com" rel="Copyright" />
<link rel="shortcut icon" href="<?php echo api_get_path(WEB_PATH); ?>favicon.ico" type="image/x-icon" />
<link rel="apple-touch-icon" href="<?php echo api_get_path(WEB_PATH); ?>apple-touch-icon.png" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset ?>" />
<meta name="Generator" content="Dokeos">

<script type="text/javascript">
//<![CDATA[
// This is a patch for the "__flash__removeCallback" bug, see FS#4378.
if ( ( navigator.userAgent.toLowerCase().indexOf('msie') != -1 ) && ( navigator.userAgent.toLowerCase().indexOf( 'opera' ) == -1 ) )
{
	window.attachEvent( 'onunload', function()
		{
			window['__flash__removeCallback'] = function ( instance, name )
			{ 
				try
				{ 
					if ( instance )
					{ 
						instance[name] = null ; 
					} 
				}
				catch ( flashEx )
				{

				} 
			} ;
		}
	) ;
}
//]]>
</script>

<?php
$htmlHeadDefault[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.2.min.js" language="javascript"></script>';
$htmlHeadDefault[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/js/jquery-ui-1.8.1.custom.min.js"></script>';
$htmlHeadDefault[] = '<link type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/css/ui-lightness/jquery-ui-1.8.1.custom.css" rel="stylesheet" />';
$htmlHeadDefault[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/dokeos.js.php" language="javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
        (function ($) {
           try {
                var a = $.ui.mouse.prototype._mouseMove; 
                $.ui.mouse.prototype._mouseMove = function (b) { 
                b.button = 1; a.apply(this, [b]); 
                } 
            }catch(e) {}
        } (jQuery));
    </script>';
$device_info = api_get_navigator();
$device = $device_info['device'];
$get_machine = $device['machine'];

if ($get_machine == 'ipad' || $get_machine == 'android') { // Load only when the device is an IPAD
  $htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.ui.touch-punch.min"></script>';
}
// Display the chat notification
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
MessageManager::display_chat_notifications();

$htmlHeadAll = array_merge($htmlHeadDefault,(array)$htmlHeadXtra);
if ( isset($htmlHeadAll) && $htmlHeadAll )
{
	foreach($htmlHeadAll as $this_html_head)
	{
		echo($this_html_head);
	}
}
if ( isset($htmlIncHeadXtra) && $htmlIncHeadXtra )
{
	foreach($htmlIncHeadXtra as $this_html_head)
	{
		include($this_html_head);
	}
}
//the following include might be subject to a setting proper to the course or platform
include(api_get_path(LIBRARY_PATH).'/javascript/email_links.lib.js.php');
?>
<script type="text/javascript" language="javascript">
	function h_search(){
		var input = $('#input').val();
		var loader = $('#h_loader');
		$('#main').html("");
		loader.show();
		$('#main').html('<div id="content"><div id="h_loader"><br/><br/>' + '<?php echo get_lang('Searching').'&nbsp;'; ?>'+'<strong>'+input+'</strong>' +'...</div></div>');
		// ajax request to show results
		$.ajax({
			  url: '<?php echo api_get_path(WEB_CODE_PATH).'search/get_results.ajax.php' ?>',
			  cache: false,
			  data: 'input='+input,
			  success: function(html){
			    h_showResults();
			    $('#main').html("<div id='content'><div id='result'>"+html+"</div></div>");
			  }
			});
		return false;		
	}
	
	function h_showResults(){
		var loader = $('#h_loader');
		var result = $('#main'); 
		loader.hide();
		result.fadeIn();
	}

	function h_hideResults(){
		var result = $('#main');
		var form = $('#search_form'); 
		result.hide();
		form.fadeIn();
		$('#input').focus();
	}
	
	// intercept "enter" to submit form
	$(document).ready(function(){
		$('#input').focus();
               if (jQuery.browser.msie && jQuery.browser.version <= 7) {
                  $("#dokeostabs li span").css("margin-top","11px");
               }
	});	
</script>  
<?php
// current css name
$css_name = api_get_setting('stylesheets');
// Check if we have a CSS with tablet support    
$css_info = array();
if (isset($GLOBALS['_cid']) && $GLOBALS['_cid'] != -1) {
    // if We are inside a course
    $css_name = api_get_setting('allow_course_theme') == 'true'?(api_get_course_setting('course_theme', null, true)?api_get_course_setting('course_theme', null, true):api_get_setting('stylesheets')):api_get_setting('stylesheets');
    $css_info = api_get_css_info($css_name);
} else {    
    $css_info = api_get_css_info();    
}
$css_type = !is_null($css_info['type']) ? $css_info['type'] : '';
if ($css_type == 'tablet') {
?>
<script type="text/javascript">
    $(function(){
       $(window).scroll(function(){
         $("#footer").css({"left":"0","bottom":"0"});
       });
       
       // center image and text inside menu header
       if ($("#dokeostabs li").length > 0) {
           $("#dokeostabs li").each(function(){          
                var image_bg = $(this).css("background-image");
                var image_path = image_bg.replace(/"/g,"").replace(/url\(|\)$/ig, "");
                var tab_text = $(this).find("span").html();
                var menu_img = '<img src="'+image_path+'" style="vertical-align:middle;" />&nbsp;';
                $(this).find("span").html(menu_img+tab_text);
                $(this).css("background-image", "none");
           });
       }
    });
</script>
<?php
} else {
?>
<script type="text/javascript">
    $(function(){
    if(navigator.platform == 'iPad' || navigator.platform == 'iPhone' || navigator.platform == 'iPod'){
        function footerStaticDinamic(){
            $("#footer").css({"left":"0","bottom":"0"});
         }
        
        footerStaticDinamic();  
        $(window).scroll(function(){
           footerStaticDinamic();  
        });
         
    } else {
         function footerStatic(){
            $("#footer").css({"left":"0","bottom":"0","position":"fixed"});
         }
        footerStatic();  
       $(window).scroll(function(){
         footerStatic();  
       });
     }   
    });        
    
</script>
<?php
}
?>
</head>
<body dir="<?php echo  $text_dir ?>" <?php
 if(defined('DOKEOS_HOMEPAGE') && DOKEOS_HOMEPAGE)
 echo 'onload="javascript:if(document.formLogin) { document.formLogin.login.focus(); }"';
 else if(defined('DOKEOS_EXERCISE') && DOKEOS_EXERCISE)
 echo 'onload="javascript:if(document.exercise_admin) { document.exercise_admin.exerciseTitle.focus(); }"';
 else if(defined('DOKEOS_QUIZGALLERY') && DOKEOS_QUIZGALLERY)
 echo 'onload="javascript:if(document.question_admin_form) { document.question_admin_form.questionName.focus(); }"';
 else if(defined('DOKEOS_GLOSSARY') && DOKEOS_GLOSSARY)
 echo 'onload="javascript:showGlossary(\'A - Z\',\'0\')"';
 ?>>

<?php
//  Banner
require_once api_get_path(INCLUDE_PATH)."banner.inc.php";
}
