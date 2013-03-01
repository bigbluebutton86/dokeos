<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @author Bart Mollet
* @package dokeos.admin
*/


// name of the language file that needs to be included
$language_file = array ('admin');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationsessionexport';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
include(api_get_path(LIBRARY_PATH).'/fileManage.lib.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));

// Database Table Definitions
$tbl_user					= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course      			= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session      			= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user      		= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course      	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

// variable initialisation
$session_id=$_GET['session_id'];
$formSent=0;
$errorMsg='';
$archivePath=api_get_path(SYS_PATH).$archiveDirName.'/';
$archiveURL=api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';
$tool_name=get_lang('ExportSessionListXMLCSV');


set_time_limit(0);

if($_POST['formSent'] )
{
	$formSent=$_POST['formSent'];
	$file_type=($_POST['file_type'] == 'csv')?'csv':'xml';
	$session_id=$_POST['session_id'];
	$ses_count=$_POST['ses_count'];
	if($ses_count) {
	
	if(empty($session_id))
	{
		$sql = "SELECT id,name,id_coach,username,date_start,date_end,visibility,session_category_id FROM $tbl_session INNER JOIN $tbl_user
					ON $tbl_user.user_id = $tbl_session.id_coach ORDER BY id";

		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
			$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
			$sql = "SELECT id, name,id_coach,username,date_start,date_end,visibility,session_category_id FROM $tbl_session s INNER JOIN $tbl_session_rel_access_url as session_rel_url
				ON (s.id= session_rel_url.session_id) INNER JOIN $tbl_user u ON (u.user_id = s.id_coach)
				WHERE access_url_id = $access_url_id
				ORDER BY id";

			}
		}
		$result=Database::query($sql,__FILE__,__LINE__);
	}
	else
	{
		$sql = "SELECT id,name,username,date_start,date_end,visibility,session_category_id
				FROM $tbl_session
				INNER JOIN $tbl_user
					ON $tbl_user.user_id = $tbl_session.id_coach
				WHERE id='$session_id'";

		$result = Database::query($sql,__FILE__,__LINE__);

	}

	if(Database::num_rows($result))
	{
		if(!file_exists($archivePath))
		{
			mkpath($archivePath);
		}

		if(!file_exists($archivePath.'index.html'))
		{
			$fp=fopen($archivePath.'index.html','w');

			fputs($fp,'<html><head></head><body></body></html>');

			fclose($fp);
		}

		$archiveFile='export_sessions_'.$session_id.'_'.date('Y-m-d_H-i-s').'.'.$file_type;

		while( file_exists($archivePath.$archiveFile))
		{
			$archiveFile='export_users_'.$session_id.'_'.date('Y-m-d_H-i-s').'_'.uniqid('').'.'.$file_type;
		}
		$fp=fopen($archivePath.$archiveFile,'w');
        $archiveFile_Realpath=$archivePath.$archiveFile;
		if($file_type == 'csv')
		{
			$cvs = true;
			fputs($fp,"SessionName;Coach;DateStart;DateEnd;Visibility;SessionCategory;Users;Courses;\n");
		}
		else
		{
			$cvs = false;
			fputs($fp,"<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<Sessions>\n");
		}

		while($row=Database::fetch_array($result))
		{
			$add = '';
			$row['name'] = str_replace(';',',',$row['name']);
			$row['username'] = str_replace(';',',',$row['username']);
			$row['date_start'] = str_replace(';',',',$row['date_start']);
			$row['date_end'] = str_replace(';',',',$row['date_end']);
			$row['visibility'] = str_replace(';',',',$row['visibility']);
			$row['session_category'] = str_replace(';',',',$row['session_category_id']);
			if($cvs){
				$add.= $row['name'].';'.$row['username'].';'.$row['date_start'].';'.$row['date_end'].';'.$row['visibility'].';'.$row['session_category'].';';
			}
			else {
				$add = "\t<Session>\n"
						 ."\t\t<SessionName>$row[name]</SessionName>\n"
						 ."\t\t<Coach>$row[username]</Coach>\n"
						 ."\t\t<DateStart>$row[date_start]</DateStart>\n"
						 ."\t\t<DateEnd>$row[date_end]</DateEnd>\n"
						 ."\t\t<Visibility>$row[visibility]</Visibility>\n"
						 ."\t\t<SessionCategory>$row[session_category]</SessionCategory>\n";
			}

			//users
			$sql = "SELECT DISTINCT $tbl_user.username FROM $tbl_user
					INNER JOIN $tbl_session_user
						ON $tbl_user.user_id = $tbl_session_user.id_user
						AND $tbl_session_user.id_session = '".$row['id']."'";

			$rsUsers = Database::query($sql,__FILE__,__LINE__);
			$users = '';
			while($rowUsers = Database::fetch_array($rsUsers)){
				if($cvs){
					$users .= str_replace(';',',',$rowUsers['username']).'|';
				}
				else {
					$users .= "\t\t<User>$rowUsers[username]</User>\n";
				}
			}
			if(!empty($users) && $cvs)
				$users = api_substr($users , 0, api_strlen($users)-1);

			if($cvs)
				$users .= ';';

			$add .= $users;

			//courses
			$sql = "SELECT DISTINCT $tbl_course.code 
					FROM $tbl_course
					INNER JOIN $tbl_session_course_user
						ON $tbl_course.code = $tbl_session_course_user.course_code								
						AND $tbl_session_course_user.id_session = '".$row['id']."'";
													
			$rsCourses = Database::query($sql,__FILE__,__LINE__);

			$courses = '';
			while($rowCourses = Database::fetch_array($rsCourses)){

				// get coachs from a course
				$sql = "SELECT u.username 
					FROM $tbl_session_course_user scu
					INNER JOIN $tbl_user u ON u.user_id = scu.id_user		
					WHERE scu.course_code = '{$rowCourses['code']}'
						AND scu.id_session = '".$row['id']."' AND scu.status = 2 ";				

				$rs_coachs = Database::query($sql,__FILE__,__LINE__);
				$coachs = array();
				while ($row_coachs = Database::fetch_array($rs_coachs)) {
					$coachs[] = $row_coachs['username']; 
				}
				
				$coachs = implode(",",$coachs); 					
								
				if($cvs){										
					$courses .= str_replace(';',',',$rowCourses['code']);
					$courses .= '['.str_replace(';',',',$coachs).'][';
				}
				else {
					$courses .= "\t\t<Course>\n";
					$courses .= "\t\t\t<CourseCode>$rowCourses[code]</CourseCode>\n";
					$courses .= "\t\t\t<Coach>$coachs</Coach>\n";
				}

				// rel user courses
				$sql = "SELECT DISTINCT u.username
						FROM $tbl_session_course_user scu
						INNER JOIN $tbl_session_user su ON scu.id_user = su.id_user AND scu.id_session = su.id_session		
						INNER JOIN $tbl_user u
						ON scu.id_user = u.user_id
						AND scu.course_code='".$rowCourses['code']."'
						AND scu.id_session='".$row['id']."'";				

				$rsUsersCourse = Database::query($sql,__FILE__,__LINE__);
				$userscourse = '';
				while($rowUsersCourse = Database::fetch_array($rsUsersCourse)){
					
					if($cvs){
						$userscourse .= str_replace(';',',',$rowUsersCourse['username']).',';
					}
					else {
						$courses .= "\t\t\t<User>$rowUsersCourse[username]</User>\n";
					}
				}
				if($cvs){
					if(!empty($userscourse))
						$userscourse = api_substr($userscourse , 0, api_strlen($userscourse)-1);

					$courses .= $userscourse.']|';
				}
				else {
					$courses .= "\t\t</Course>\n";
				}
			}

			if(!empty($courses) && $cvs)
				$courses = api_substr($courses , 0, api_strlen($courses)-1);
			$add .= $courses;

			if($cvs) {
				$breakline = api_is_windows_os()?"\r\n":"\n";
				$add .= ";$breakline";
			} else {
				$add .= "\t</Session>\n";
			}

			fputs($fp, $add);
		}

		if(!$cvs)
			fputs($fp,"</Sessions>\n");
		fclose($fp);

		//$errorMsg=get_lang('UserListHasBeenExported').'<br/><a href="'.$archiveURL.$archiveFile.'">'.get_lang('ClickHereToDownloadTheFile').'</a>';
	}
	
	header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: public');
	header('Pragma: no-cache');
	header('Content-Type: '.$file_type);
	header('Content-Length: '.filesize($archiveFile_Realpath));
	header('Content-Disposition: attachment; filename='.$archiveFile);
	readfile($archiveFile_Realpath);
	exit; 
}

else
{

$errorMsg=get_lang('SessionNotAvaliable');
}
}

// display the header
Display::display_header($tool_name);

echo '<div class="actions">';
if(api_get_setting('show_catalogue')=='true'){
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/catalogue_management.php">' . Display::return_icon('pixel.gif',get_lang('Catalogue'), array('class' => 'toolactionplaceholdericon toolactioncatalogue')) . get_lang('Catalogue') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/topic_list.php">' . Display :: return_icon('pixel.gif', get_lang('Topics'),array('class' => 'toolactionplaceholdericon toolactiontopic')) . get_lang('Topics') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/programme_list.php">' . Display :: return_icon('pixel.gif', get_lang('Programmes'),array('class' => 'toolactionplaceholdericon toolactionprogramme')) . get_lang('Programmes') . '</a>';
}
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_add.php">'.Display :: return_icon('pixel.gif', get_lang('AddSession'),array('class' => 'toolactionplaceholdericon toolactionadd')).get_lang('AddSession').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_list.php">' . Display :: return_icon('pixel.gif', get_lang('SessionList'),array('class' => 'toolactionplaceholdericon toolactionsession')) . get_lang('SessionList') . '</a>';        
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_export.php">'.Display::return_icon('pixel.gif',get_lang('ExportSessionListXMLCSV'),array('class' => 'toolactionplaceholdericon toolactionexportcourse')).get_lang('ExportSessionListXMLCSV').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_import.php">'.Display::return_icon('pixel.gif',get_lang('ImportSessionListXMLCSV'),array('class' => 'toolactionplaceholdericon toolactionimportcourse')).get_lang('ImportSessionListXMLCSV').'</a>';	        
echo '<a href="'.api_get_path(WEB_CODE_PATH).'coursecopy/copy_course_session.php">'.Display::return_icon('pixel.gif',get_lang('CopyFromCourseInSessionToAnotherSession'),array('class' => 'toolactionplaceholdericon toolsettings')).get_lang('CopyFromCourseInSessionToAnotherSession').'</a>';	
echo '</div>';

// start the content div
echo '<div id="content">';

// display the tool title
// api_display_tool_title($tool_name);


//select of sessions
$sql = "SELECT id, name FROM $tbl_session ORDER BY name";
global $_configuration;
if ($_configuration['multiple_access_urls']==true) {
	$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1){
	$sql = "SELECT id, name FROM $tbl_session s INNER JOIN $tbl_session_rel_access_url as session_rel_url
		ON (s.id= session_rel_url.session_id)
		WHERE access_url_id = $access_url_id
		ORDER BY name";
	}
}


$result=Database::query($sql,__FILE__,__LINE__);

$Sessions=Database::store_result($result);
?>

<?php
$ses_count=count($Sessions);
if(!empty($errorMsg))
{
$s_style_error="border-width: 1px;
		 border-style: solid;
		 margin-left: 0;
		 margin-top: 10px;
		 margin-bottom: 10px;
		 min-height: 30px;
		 padding: 5px;
		 position: relative;
		 width: 500px;
		 background-color: #FFD1D1;
		 border-color: #FF0000;
		 color: #000;";
		 
	//Display::display_normal_message($errorMsg, false); //main API
	echo '<div style="'.$s_style_error.'"><div style="float:left; margin-right:10px;"><img src="'.api_get_path(WEB_IMG_PATH)."message_error.gif".'" alt="'.$alt_text.'" '.$attribute_list.'  /></div><div style="margin-left: 43px">'.$errorMsg.'</div></div>';

}
?>

<form method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<input type="hidden" name="ses_count" value="<?php echo $ses_count; ?>">
<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
<table border="0" cellpadding="5" cellspacing="0">
<tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('OutputFileType'); ?> :</td>
  <td>
	<input class="checkbox" type="radio" name="file_type" id="file_type_xml" value="xml" <?php if($formSent && $file_type == 'xml') echo 'checked="checked"'; ?>> <label for="file_type_xml">XML</label><br>
	<input class="checkbox" type="radio" name="file_type" id="file_type_csv"  value="csv" <?php if(!$formSent || $file_type == 'csv') echo 'checked="checked"'; ?>> <label for="file_type_csv">CSV</label><br>
  </td>
</tr>
<tr>
  <td><?php echo get_lang('WhichSessionToExport'); ?> :</td>
  <td><select name="session_id">
	<option value=""><?php echo get_lang('AllSessions') ?></option>

<?php
foreach($Sessions as $enreg)
{
?>

	<option value="<?php echo $enreg['id']; ?>" <?php if($session_id == $enreg['id']) echo 'selected="selected"'; ?>><?php echo $enreg['name']; ?></option>

<?php
}

unset($Courses);
?>

  </select></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>
  <button class="save" type="submit" name="name" value="<?php echo get_lang('ExportSession') ?>"><?php echo get_lang('ExportSession') ?></button>
  </td>
</tr>
</table>
</form>

<?php
// close the content div
echo '</div>';

// display the footer
Display::display_footer();
?>
