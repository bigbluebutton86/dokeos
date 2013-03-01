<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @author Patrick Cool
* @package dokeos.admin
*/
$language_file = array('courses');
include_once('../inc/global.inc.php');
?>
<html>
   <head>
      <?php
      if (isset($_GET['style']) AND $_GET['style']<>'') {
	$style=Security::remove_XSS($_GET['style']);
	echo '<link href="../css/'.$style.'/default.css" rel="stylesheet" type="text/css">';
} else {
	$currentstyle = api_get_setting('stylesheets');
	echo '<link href="../css/'.$currentstyle.'/default.css" rel="stylesheet" type="text/css">';
}
      ?>
      <style>
#main{
	margin: auto;
	width: 900px;
	height: 100%;
}
#content_with_menu{
	float: right;
	width: 700px;
}
.headerinner{
	margin: auto;
	overflow:hidden; /* IE needs */
	height:50px;
	width: 910px;
        position:relative;
}
#dokeostabs{
	float: left;
	padding: 0;
	margin-left: 0;
       width: 710px;
}
#header2 {
       width: 920px;
}
      </style>
   </head>
<body>
<div id="wrapper">

<div id="header">
	<div id="header1">
		<div class="headerinner">
			<div id="top_corner"></div> 
			<div id="languageselector"></div>
			<div id="institution">
				<a href="javascript:void(0)" target="_top"><?php echo api_get_setting('siteName'); ?></a>
				-&nbsp;
                                <a href="javascript:void(0)" target="_top"><?php echo api_get_setting('Institution'); ?></a>			
                        </div>             
	</div>
	</div>

	<div id="header2">
		<div class="headerinner">
			<ul id="logout">
                           <li><span>
                                 <a href="javascript:void(0)" target="_top"><span><?php echo get_lang('Logout'); ?>&nbsp;(admin)</span></a>
                              </span></li>
                        </ul>
                        <ul id="dokeostabs">
                           <a href="javascript:void(0)" target="_top"><li class="tab_mycampus"><div><span><?php echo get_lang('Home'); ?></span></div></li></a>
                           <a href="javascript:void(0)" target="_top"><li id="current" class="tab_mycourses_current"><div><span><?php echo get_lang('Courses'); ?></span></div></li></a>
                           <a href="javascript:void(0)" target="_top"><li class="tab_myagenda"><div><span><?php echo get_lang('Agenda'); ?></span></div></li></a>
                           <a href="javascript:void(0)" target="_top"><li class="tab_session_my_space"><div><span><?php echo get_lang('Reporting'); ?></span></div></li></a>
                           <a href="javascript:void(0)" target="_top"><li class="tab_platform_admin"><div><span><?php echo get_lang('PlatformAdmin'); ?></span></div></li></a>
                        </ul><div style="clear: both;" class="clear"> </div>		</div>
	</div>
		
	</div>



 <!-- end of the whole #header section -->
<div class="clear">&nbsp;</div>
<div id="main"> <!-- start of #main wrapper for #content and #menu divs -->
<!--   Begin Of script Output   -->
	<div class="maxcontent_"><div id="content"><div id="content_with_menu"><div class="course_list_category">Default category</div>
                 <ul class="courseslist">
                    <li>
                       <div class="independent_course_item" style="padding: 8px; clear:both;">
                          <a href="javascript:void(0)"><div class="coursestatusicons"><img src="<?php echo api_get_path(WEB_IMG_PATH); ?>miscellaneous22x22.png" alt="miscellaneous22x22.png" title="miscellaneous22x22.png"></div>
                             <strong>Training</strong></a>
                          <br/>TRAINING - John Doe</div></li>
                 </ul></div>
              <div id="main_left_content">	
                 <div style="height: 98px;" class="menu" id="menu">
                    <h3 class="tablet_title"><?php echo get_lang('Account'); ?></h3>
                    <a href="javascript:void(0)"><img src="<?php echo api_get_path(WEB_IMG_PATH); ?>pixel.gif" alt="Create a course" title="Create a course" class="homepage_button homepage_create_course" align="absmiddle"><?php echo get_lang('CourseCreate'); ?></a><br><a href="javascript:void(0);"><img src="<?php echo api_get_path(WEB_IMG_PATH); ?>pixel.gif" alt="Sort courses" title="Sort courses" class="homepage_button homepage_catalogue" align="absmiddle"><?php echo get_lang('SortMyCourses'); ?></a></div></div><div class="clear"></div></div> <div class="clear">&nbsp;</div> <!-- 'clearing' div to make sure that footer stays below the main and right column sections -->
</div> <!-- end of #main" started at the end of banner.inc.php -->
</div>
<div class="push"></div>
</div> <!-- end of #wrapper section -->

</body>
</html>