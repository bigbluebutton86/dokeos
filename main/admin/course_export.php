<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* This tool allows platform admins to export courses to CSV file
* @package dokeos.admin
*/

// Language files that should be included
$language_file = array('admin','courses');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationcourseexport';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'course.lib.php';

// section for the tabs
$this_section=SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();

// Setting the breadcrumbs
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// no time limit because this can take some time
set_time_limit(0);

// Display the header
Display :: display_header(get_lang('ExportCourses').' CSV');

//Actions
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/course_list.php">'.Display::return_icon('pixel.gif',get_lang('CourseList'), array('class' => 'toolactionplaceholdericon toolactionadmincourse')).get_lang('CourseList').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/course_add.php">'.Display::return_icon('pixel.gif',get_lang('AddCourse'), array('class' => 'toolactionplaceholdericon toolactioncreatecourse')).get_lang('AddCourse').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/course_enrolment.php">'.Display::return_icon('pixel.gif',get_lang('EnrolmentToCoursesAtRegistrationToPortal'),array('class' => 'toolactionplaceholdericon toolactionautomaticenrollment')).get_lang('EnrolmentToCoursesAtRegistrationToPortal').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/course_import.php">'.Display::return_icon('pixel.gif',get_lang('ImportCourses'),array('class' => 'toolactionplaceholdericon toolactionimportcourse')).get_lang('ImportCourses').'</a>';	
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/subscribe_user2course.php">'.Display::return_icon('pixel.gif',get_lang('AddUsersToACourse'),array('class' => 'toolactionplaceholdericon toolactionaddusertocourse')).get_lang('AddUsersToACourse').'</a>';
echo '</div>';

// start the content div
echo '<div id="content" class="maxcontent">';

$archivePath=api_get_path(SYS_PATH).$archiveDirName.'/';
$archiveURL=api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

$course_list = CourseManager::get_courses_list();

if($_POST['formSent'])
{			
	$formSent	=$_POST['formSent'];
	$select_type=intval($_POST['select_type']);
	$file_type = 'csv';	
	$courses = $selected_courses = array();
	
	if ($select_type == 2) {
		// Get selected courses from courses list in form sent 
		$selected_courses = $_POST['course_code'];
		if (is_array($selected_courses)) {		
			foreach ($course_list as $course) {			
				if (!in_array($course['code'],$selected_courses)) continue;
				$courses[] = $course;
			}
		}				
	} else {
		// Get all courses
		$courses = $course_list;
	}

	if (!empty($courses)) {			
		if(!file_exists($archivePath)) {
			mkpath($archivePath);
		}				
		$archiveFile='export_courses_list_'.date('Y-m-d_H-i-s').'.'.$file_type;
		$fp=fopen($archivePath.$archiveFile,'w');	
		if ($file_type == 'csv') {		
			$add = "Code;Title;CourseCategory;Teacher;Language;".PHP_EOL;						
			foreach($courses as $course) {
				$course['code'] = str_replace(';',',',$course['code']);
				$course['title'] = str_replace(';',',',$course['title']);
				$course['category_code'] = str_replace(';',',',$course['category_code']);
				$course['tutor_name'] = str_replace(';',',',$course['tutor_name']);						
				$course['course_language'] = str_replace(';',',',$course['course_language']);
				
				$add.= $course['code'].';'.$course['title'].';'.$course['category_code'].';'.$course['tutor_name'].';'.$course['course_language'].';'.PHP_EOL;			
			}		
			fputs($fp, $add);
		}	
		fclose($fp);
		$msg=get_lang('CoursesListHasBeenExported').'<br/><a href="'.$archiveURL.$archiveFile.'">'.get_lang('ClickHereToDownloadTheFile').'</a>';
	} else {
		$msg=get_lang('ThereAreNotSelectedCoursesOrCoursesListIsEmpty');
	}
}

if (!empty($msg)) {
	echo '<div class="confirmation-message rounded">'.$msg.'</div>';
}
?>


<form method="post" action="<?php echo api_get_self(); ?>">
<input type="hidden" name="formSent" value="1">
<div class="row"><div class="form_header"><?php echo get_lang('ExportCourses').' CSV'; ?></div></div>
<br />
<?php if (!empty($course_list)) { ?>
<div>
<input id="all-courses" class="checkbox" type="radio" value="1" name="select_type" <?php if(!$formSent || ($formSent && $select_type == 1)) echo 'checked="checked"'; ?> onclick="if(this.checked==true){document.getElementById('div-course-list').style.display='none';}"/>
<label for="all-courses"><?php echo get_lang('ExportAllCoursesList')?></label>
<br/>
<input id="select-courses" class="checkbox" type="radio" value="2" name="select_type" <?php if($formSent && $select_type == 2) echo 'checked="checked"'; ?> onclick="if(this.checked==true){document.getElementById('div-course-list').style.display='block';}"/>
<label for="select-courses"><?php echo get_lang('ExportSelectedCoursesFromCoursesList')?></label>
</div>
<br />
<div id="div-course-list" style="<?php echo (!$formSent || ($formSent && $select_type == 1))?'display:none':'display:block';?>">
<table border="0" cellpadding="5" cellspacing="0">
<tr>
  <td valign="top"><?php echo get_lang('WhichCoursesToExport'); ?> :</td>
  <td><select name="course_code[]" multiple="multiple" size="10">
<?php
foreach($course_list as $course) {
?>
	<option value="<?php echo $course['code']; ?>" <?php if(is_array($selected_courses) && in_array($course['code'],$selected_courses)) echo 'selected="selected"'; ?>><?php echo $course['code'].'-'.$course['title']; ?></option>
<?php
}
?>
</select></td>
</tr>
</table>
</div>
<div id="actions">
  <button class="save" type="submit" name="name" value="<?php echo get_lang('ExportCourses') ?>"><?php echo get_lang('ExportCourses') ?></button>
</div>
<?php } else { echo get_lang('ThereAreNotCreatedCourses'); }?>
</form>


<?php
// close the content div
echo '</div>';	

// Display the footer
Display :: display_footer();
?>
