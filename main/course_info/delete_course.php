<?php
/* For licensing terms, see /dokeos_license.txt */

// name of the language file that needs to be included
$language_file = array ('admin','course_info');

// setting the help
$help_content = 'deletecourse';

// including the global Dokeos file
include ('../inc/global.inc.php');

// include additional libraries
include (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once '../gradebook/lib/be/gradebookitem.class.php';
require_once '../gradebook/lib/be/category.class.php';

// section for the tabs
$this_section = SECTION_COURSES;

$currentCourseCode = $_course['official_code'];
$currentCourseName = $_course['name'];

if (!api_is_allowed_to_edit())
{
	api_not_allowed(true);
}
$tool_name = get_lang('DelCourse');
if (isset($_GET['delete']) && $_GET['delete'] == 'yes')
{
	CourseManager :: delete_course($_course['sysCode']);

	$obj_cat=new Category();
	$obj_cat->update_category_delete($_course['sysCode']);

	// DELETE CONFIRMATION MESSAGE
	unset ($_course);
	unset ($_cid);
	$noPHP_SELF = true;
	$message = get_lang('Course')." &quot;".$currentCourseName."&quot; "."(".$currentCourseCode.") ".get_lang('HasDel');
	$message .=  "<br /><br /><a href=\"../../index.php\">".get_lang('BackHome')." ".api_get_setting('siteName')."</a>";

} // end if $delete
else
{
	$message = "&quot;".$currentCourseName."&quot; "."(".$currentCourseCode.") "."<p>".get_lang("ByDel")."</p>"."<p>"."<a href=\"maintenance.php\">".get_lang("N")."</a>"."&nbsp;&nbsp;|&nbsp;&nbsp;"."<a href=\"".api_get_self()."?delete=yes\">".get_lang("Y")."</a>"."</p>";
	$interbreadcrumb[] = array ("url" => "maintenance.php", "name" => get_lang('Maintenance'));
}

// Display the header
Display :: display_tool_header($tool_name, "Settings");

// Display the tool title
// api_display_tool_title($tool_name);

// start the content div
echo '<div id="content">';

// display the warning message
Display::display_warning_message($message,false);

// Display content
echo $message;

// close the content div
echo '</div>';

// display the footer
Display::display_footer();
?>