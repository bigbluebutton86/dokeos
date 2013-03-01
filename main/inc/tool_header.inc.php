<?php
/**
==============================================================================
*	This script displays the Dokeos header up to the </head> tag
*   IT IS A COPY OF header.inc.php EXCEPT that it doesn't start the body
*   output.
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
// Load header file if there is no course ID
if ($_cid == -1) { // In the future we should have only ONE header file
    require_once api_get_path(INCLUDE_PATH).'header.inc.php';
} else {
//Give a default value to $charset. Should change to UTF-8 some time in the future.
//This parameter should be set in the platform configuration interface in time.
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
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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

<?php

/*
 * Choose CSS style platform's, user's, course's, or Learning path CSS
 */

$platform_theme = api_get_setting('stylesheets'); 	// plataform's css
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

			if (!empty($lp_theme_css))
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

if (!empty($lp_theme_log)){
	$my_style=$platform_theme;
}

// Sets the css reference it is call from lp_nav.php, lp_toc.php, lp_message, lp_log.php
if (!empty($scorm_css_header))
{
	if (!empty($my_style))
	{
		$scorm_css=api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/scorm.css';
		$scormfs_css=api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/scormfs.css';
	}
	else
	{
		$scorm_css='scorm.css';
		$scormfs_css='scormfs.css';
	}

	if(!empty($display_mode) && $display_mode == 'fullscreen')
	{
		$htmlHeadXtra[] = '<style type="text/css" media="screen, projection">
							/*<![CDATA[*/
							@import "'.$scormfs_css.'";
							/*]]>*/
							</style>';
	}
	else
	{
		$htmlHeadXtra[] = '<style type="text/css" media="screen, projection">
							/*<![CDATA[*/
							@import "'.$scorm_css.'";
							/*]]>*/
							</style>';
	}
}

// A lot of portals are using old themes that doesn't exists anymore, this change should be done in the migration file
$theme_exists = true;
if (!file_exists(api_get_path(SYS_CODE_PATH).'css/'.$my_style.'/default.css')) {
	$theme_exists = false;
}
if(empty($my_style) || $theme_exists === false) {// If course theme in 1.8 platform doesn't exists then we are loading the platform theme
	$my_style = $platform_theme;
}
if($my_style!='') {
?>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/<?php echo $my_style;?>/default.css";
/*]]>*/
</style>

<?php
}
?>

<link rel="top" href="<?php echo api_get_path(WEB_PATH); ?>index.php" title="" />
<link rel="courses" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/courses.php" title="<?php echo api_htmlentities(get_lang('OtherCourses'),ENT_QUOTES,$charset); ?>" />
<link rel="profil" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/profile.php" title="<?php echo api_htmlentities(get_lang('ModifyProfile'),ENT_QUOTES,$charset); ?>" />
<link href="http://www.dokeos.com/documentation.php" rel="Help" />
<link href="http://www.dokeos.com/team.php" rel="Author" />
<link href="http://www.dokeos.com" rel="Copyright" />
<link rel="shortcut icon" href="<?php echo api_get_path(WEB_PATH); ?>favicon.ico" type="image/x-icon" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset ?>" />
<meta name="Generator" content="Dokeos"/>
<script language="javascript" src="<?php echo api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.2.min.js'; ?>" type="text/javascript"></script>
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
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/js/jquery-ui-1.8.1.custom.min.js"></script>';
$htmlHeadXtra[] = '<link type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/css/ui-lightness/jquery-ui-1.8.1.custom.css" rel="stylesheet" />';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/dokeos.js.php" language="javascript"></script>';
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

// Display all $htmlHeadXtra
if ( isset($htmlHeadXtra) && $htmlHeadXtra )
{
	foreach($htmlHeadXtra as $this_html_head)
	{
		echo($this_html_head);
	}
}


// Check if we have a CSS with tablet support
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
<?php
 if ((isset($_SESSION['oLP']) && isset($_GET['lp_id']) && $_GET['lp_id'] > 0) || (isset($_GET['exerciseId']) && $_GET['exerciseId'] > 0) || (isset($_GET['fromExercise']) && $_GET['fromExercise'] > 0)){
   // This CSS must be moved to dokeos2_orange.css file
?>
 <link rel="stylesheet" type="text/css" href="../css/<?php echo $my_style;?>/course_navigation.css" />
<?php
 }
?>
</head>
<body class="tool_background" dir="<?php echo  $text_dir ?>">

<?php
 if (isset($_SESSION['oLP']) && isset($_GET['lp_id']) && $_GET['lp_id'] > 0) 
 {
   require_once '../newscorm/learnpath.class.php';
   require_once '../newscorm/learnpathItem.class.php';
   require_once '../newscorm/course_navigation_interface.inc.php';
 ?>
<div id="main">
<div id="courseHeader">
	<?php
		echo display_author_header();
	?>
</div>
<?php
 } elseif((isset($_GET['exerciseId']) && $_GET['exerciseId'] > 0) || (isset($_GET['fromExercise']) && $_GET['fromExercise'] > 0)){
   // Load Learning path functions
  require_once api_get_path(SYS_PATH).'main/exercice/quiz_navigation_interface.inc.php';
 ?>
<div id="main">
<div id="courseHeader">
	<?php	 
		echo display_quiz_author_header();
	?>
</div>
<?php
 } else {
?>
<div id="main">
	<div id="generic_tool_header">
		<div id="header_background">
		<?php global $tool_name,$_cid,$_course;
                ?>
		<?php if (strcmp($tool_name,'Chat') == 0) $target = '_parent'; else $target = '_self';?>
                        <?php if ($_cid == -1) {?>
                            <a href="<?php echo api_get_path(WEB_PATH); ?>index.php" id="back2home" target="<?php echo $target ?>"><img src="<?php echo api_get_path(WEB_IMG_PATH);?>spacer.gif" width="42px" height="37px" /></a>
                        <?php } else {
                            $course_path = !empty($_course['path']) ? $_course['path'] : $_course['directory'];
                            ?>
                            <a href="<?php echo api_get_path(WEB_COURSE_PATH).$course_path; ?>/index.php" id="back2home" target="<?php echo $target ?>"><img src="<?php echo api_get_path(WEB_IMG_PATH);?>spacer.gif" width="42px" height="37px" /></a>
                        <?php }?>
		</div>
		<?php
			// name of training
			if(isset($_course) && array_key_exists('name', $_course))
				echo '<span id="global_course_name">'.$_course['name'].'</span>';
		?>
	</div>
<?php
 }
}
?>
