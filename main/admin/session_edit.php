<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

// name of the language file that needs to be included
$language_file ='admin';
$cidReset=true;
include('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$id=intval($_GET['id']);

$formSent=0;
$errorMsg='';

// Database Table Definitions
$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

$tool_name = get_lang('EditSession');

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_list.php","name" => get_lang('SessionList'));

$result=Database::query("SELECT name,max_seats,description,date_start,date_end,id_coach, session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end, session_category_id, visibility FROM $tbl_session WHERE id='$id'",__FILE__,__LINE__);

if (!$infos=Database::fetch_array($result)) {
	header('Location: session_list.php');
	exit();
}
list($year_start,$month_start,$day_start)=explode('-',$infos['date_start']);
list($year_end,$month_end,$day_end)=explode('-',$infos['date_end']);

if (!api_is_platform_admin() && $infos['session_admin_id']!=$_user['user_id']) {
	api_not_allowed(true);
}

if ($_POST['formSent']) {
	$formSent=1;
	$name= $_POST['name'];
	$max_seats = (html_entity_decode($_POST['max_seats']) == '&infin;'?'-1':intval($_POST['max_seats']));
	$description= $_POST['description'];
	$year_start= $_POST['year_start'];
	$month_start=$_POST['month_start'];
	$day_start=$_POST['day_start'];
	$year_end=$_POST['year_end'];
	$month_end=$_POST['month_end'];
	$day_end=$_POST['day_end'];
//	$nb_days_acess_before = $_POST['nb_days_access_before'];
//	$nb_days_acess_after = $_POST['nb_days_access_after'];
	$nolimit=$_POST['nolimit'];
	$id_coach=$_POST['id_coach'];
	$id_session_category = $_POST['session_category'];
//	$id_visibility = $_POST['session_visibility'];

//	$return = SessionManager::edit_session($id,$name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nb_days_acess_before,$nb_days_acess_after,$nolimit, $id_coach, $id_session_category,$id_visibility);
	$return = SessionManager::edit_session($id,$name,$description,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nolimit, $id_coach, $id_session_category, $max_seats);
	if ($return == strval(intval($return))) {
		header('Location: resume_session.php?id_session='.$return);
		exit();
	}
}

$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
$sql="SELECT user_id,lastname,firstname,username FROM $tbl_user WHERE status='1'".$order_clause;

if ($_configuration['multiple_access_urls']==true){
	$table_access_url_rel_user= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1) {
		$sql="SELECT DISTINCT u.user_id,lastname,firstname,username FROM $tbl_user u INNER JOIN $table_access_url_rel_user url_rel_user
			ON (url_rel_user.user_id = u.user_id)
			WHERE status='1' AND access_url_id = '$access_url_id' $order_clause";
	}
}

$result=Database::query($sql,__FILE__,__LINE__);

$Coaches=Database::store_result($result);
$thisYear=date('Y');

// display the header
Display::display_header($tool_name);

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/catalogue_management.php">' . Display::return_icon('pixel.gif',get_lang('Catalogue'), array('class' => 'toolactionplaceholdericon toolactioncatalogue')) . get_lang('Catalogue') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/topic_list.php">' . Display :: return_icon('pixel.gif', get_lang('Topics'),array('class' => 'toolactionplaceholdericon toolactiontopic')) . get_lang('Topics') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/programme_list.php">' . Display :: return_icon('pixel.gif', get_lang('Programmes'),array('class' => 'toolactionplaceholdericon toolactionprogramme')) . get_lang('Programmes') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_list.php">' . Display :: return_icon('pixel.gif', get_lang('SessionList'),array('class' => 'toolactionplaceholdericon toolactionsession')) . get_lang('SessionList') . '</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_export.php">'.Display::return_icon('pixel.gif',get_lang('ExportSessionListXMLCSV'),array('class' => 'toolactionplaceholdericon toolactionexportcourse')).get_lang('ExportSessionListXMLCSV').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_import.php">'.Display::return_icon('pixel.gif',get_lang('ImportSessionListXMLCSV'),array('class' => 'toolactionplaceholdericon toolactionimportcourse')).get_lang('ImportSessionListXMLCSV').'</a>';	        
echo '<a href="'.api_get_path(WEB_CODE_PATH).'coursecopy/copy_course_session.php">'.Display::return_icon('pixel.gif',get_lang('CopyFromCourseInSessionToAnotherSession'),array('class' => 'toolactionplaceholdericon toolsettings')).get_lang('CopyFromCourseInSessionToAnotherSession').'</a>';
echo '</div>';

// Start content page
echo '<div id="content">';

// display the tool title
// api_display_tool_title($tool_name);

if (!empty($return)) {
	Display::display_error_message($return,false);
}
?>

<form method="post" name="form" action="<?php echo api_get_self(); ?>?page=<?php echo Security::remove_XSS($_GET['page']) ?>&id=<?php echo $id; ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">

<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>

<table border="0" cellpadding="5" cellspacing="0" width="650">

<tr>
  <td width="30%"><?php echo get_lang('SessionName') ?>&nbsp;&nbsp;</td>
  <td width="70%"><input type="text" name="name" size="50" maxlength="50" value="<?php if($formSent) echo api_htmlentities($name,ENT_QUOTES,$charset); else echo api_htmlentities($infos['name'],ENT_QUOTES,$charset); ?>"></td>
</tr>
<tr>
  <td width="40%" valign="top"><?php echo get_lang('Description') ?>&nbsp;&nbsp;</td>
  <td width="60%">
  <?php
  if($formSent) $description =  $description; else $description =  $infos['description'];
  echo api_return_html_area('description', $description, '', '', null, array('ToolbarSet' => 'Survey', 'Width' => '550', 'Height' => '180'))
  ?>
  <!--<textarea name="description" class="focus" rows="5" cols="37"><?php if($formSent) echo api_htmlentities($description,ENT_QUOTES,$charset); else echo api_htmlentities($infos['description'],ENT_QUOTES,$charset); ?></textarea>-->
  </td>
</tr>
<tr>
  <td width="40%"><?php echo get_lang('MaxSeats') ?></td>
  <td width="60%"><input type="text" name="max_seats" size="5" maxlength="6" value="<?php echo ($infos['max_seats'] == '-1'?'&infin;':$infos['max_seats']); ?>"></td>
</tr>
<tr>
  <td width="30%"><?php echo get_lang('CoachName') ?>&nbsp;&nbsp;</td>
  <td width="70%"><select name="id_coach" style="width:250px;">
	<option value="">----- <?php echo get_lang('Choose') ?> -----</option>

<?php
foreach($Coaches as $enreg) {
?>

	<option value="<?php echo $enreg['user_id']; ?>" <?php if((!$sent && $enreg['user_id'] == $infos['id_coach']) || ($sent && $enreg['user_id'] == $id_coach)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>

<?php
}

unset($Coaches);
?>

  </select></td>
</tr>
<?php
	$tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
	//$access_url_id = api_get_current_access_url_id();
	$sql = 'SELECT id, name FROM '.$tbl_session_category.' ORDER BY name ASC';
	$result = Database::query($sql,__FILE__,__LINE__);
	$Categories = Database::store_result($result);
?>
<!--<tr>
  <td width="30%"><?php echo get_lang('SessionCategory') ?></td>
  <td width="70%">
  	<select name="session_category" value="true" style="width:250px;">
		<option value="0"><?php get_lang('None'); ?></option>
		<?php foreach($Categories as $Rows): ?>
		<option value="<?php echo $Rows['id']; ?>" <?php if($Rows['id'] == $infos['session_category_id']) echo 'selected="selected"'; ?>><?php echo $Rows['name']; ?></option>
		<?php endforeach; ?>
	</select>
  </td>
</tr>-->

<tr>
  <td width="30%"><?php echo get_lang('NoTimeLimits') ?></td>
  <td width="70%">
  	<input type="checkbox" name="nolimit" onChange="setDisable(this)" <?php if($year_start=="0000") echo "checked"; ?>/>
  </td>
<tr>
<tr>
  <td width="30%"><?php echo get_lang('DateStartSession') ?>&nbsp;&nbsp;</td>
  <td width="70%">
  <select name="day_start">
	<option value="1">01</option>
	<option value="2" <?php if($day_start == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($day_start == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($day_start == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($day_start == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($day_start == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($day_start == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($day_start == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($day_start == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($day_start == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($day_start == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($day_start == 12) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if($day_start == 13) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if($day_start == 14) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if($day_start == 15) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if($day_start == 16) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if($day_start == 17) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if($day_start == 18) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if($day_start == 19) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if($day_start == 20) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if($day_start == 21) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if($day_start == 22) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if($day_start == 23) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if($day_start == 24) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if($day_start == 25) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if($day_start == 26) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if($day_start == 27) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if($day_start == 28) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if($day_start == 29) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if($day_start == 30) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if($day_start == 31) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_start">
	<option value="1">01</option>
	<option value="2" <?php if($month_start == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($month_start == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($month_start == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($month_start == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($month_start == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($month_start == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($month_start == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($month_start == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($month_start == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($month_start == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($month_start == 12) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_start">

<?php
for($i=$thisYear-5;$i <= ($thisYear+5);$i++)
{
?>

	<option value="<?php echo $i; ?>" <?php if($year_start == $i) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>

<?php
}
?>

  </select>
  </td>
</tr>
<tr>
  <td width="30%"><?php echo get_lang('DateEndSession') ?>&nbsp;&nbsp;</td>
  <td width="70%">
  <select name="day_end">
	<option value="1">01</option>
	<option value="2" <?php if($day_end == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($day_end == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($day_end == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($day_end == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($day_end == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($day_end == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($day_end == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($day_end == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($day_end == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($day_end == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($day_end == 12) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if($day_end == 13) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if($day_end == 14) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if($day_end == 15) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if($day_end == 16) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if($day_end == 17) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if($day_end == 18) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if($day_end == 19) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if($day_end == 20) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if($day_end == 21) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if($day_end == 22) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if($day_end == 23) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if($day_end == 24) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if($day_end == 25) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if($day_end == 26) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if($day_end == 27) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if($day_end == 28) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if($day_end == 29) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if($day_end == 30) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if($day_end == 31) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_end">
	<option value="1">01</option>
	<option value="2" <?php if($month_end == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($month_end == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($month_end == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($month_end == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($month_end == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($month_end == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($month_end == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($month_end == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($month_end == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($month_end == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($month_end == 12) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_end">

<?php
for($i=$thisYear-5;$i <= ($thisYear+5);$i++)
{
?>

	<option value="<?php echo $i; ?>" <?php if($year_end == $i) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>

<?php
}
?>

  </select>
  </td>
</tr>
<!--<tr>
	<td>
		&nbsp;
	</td>
	<td>
		<a href="javascript://" onclick="if(document.getElementById('options').style.display == 'none'){document.getElementById('options').style.display = 'block';}else{document.getElementById('options').style.display = 'none';}"><?php echo get_lang('DefineSessionOptions') ?></a>
		<div style="display:
			<?php
				if($formSent){
					if($nb_days_access_before!=0 || $nb_days_access_after!=0)
						echo 'block';
					else echo 'none';
				}
				else{
					if($infos['nb_days_access_before_beginning']!=0 || $infos['nb_days_access_after_end']!=0)
						echo 'block';
					else
						echo 'none';
				}
			?>
				;" id="options">
			<br>
			<input type="text" name="nb_days_access_before" value="<?php if($formSent) echo api_htmlentities($nb_days_access_before,ENT_QUOTES,$charset); else echo api_htmlentities($infos['nb_days_access_before_beginning'],ENT_QUOTES,$charset); ?>" style="width: 30px;">&nbsp;<?php echo get_lang('DaysBefore') ?><br>
			<input type="text" name="nb_days_access_after" value="<?php if($formSent) echo api_htmlentities($nb_days_access_after,ENT_QUOTES,$charset); else echo api_htmlentities($infos['nb_days_access_after_end'],ENT_QUOTES,$charset); ?>" style="width: 30px;">&nbsp;<?php echo get_lang('DaysAfter') ?>
			<br>
		</div>
	</td>
</tr>

<tr>
  <td width="30%"><?php echo get_lang('SessionVisibility') ?></td>
  <td width="70%">
  	<select name="session_visibility" style="width:250px;">
		<?php
		$visibility_list = array(SESSION_VISIBLE_READ_ONLY=>get_lang('ReadOnly'), SESSION_VISIBLE=>get_lang('Visible'), SESSION_INVISIBLE=>api_ucfirst(get_lang('Invisible')));
		foreach($visibility_list as $key=>$item): ?>
		<option value="<?php echo $key; ?>" <?php if($key == $infos['visibility']) echo 'selected="selected"'; ?>><?php echo $item; ?></option>
		<?php endforeach; ?>
	</select>
  </td>
</tr>-->


<tr>
  <td>&nbsp;</td>
  <td>
<button class="save" type="submit" value="<?php echo get_lang('ModifyThisSession') ?>"><?php echo get_lang('ModifyThisSession') ?></button>

  </td>
</tr>

</table>

</form>
<script type="text/javascript">

<?php if($year_start=="0000") echo "setDisable(document.form.nolimit);\r\n"; ?>

function setDisable(select){

	document.form.day_start.disabled = (select.checked) ? true : false;
	document.form.month_start.disabled = (select.checked) ? true : false;
	document.form.year_start.disabled = (select.checked) ? true : false;

	document.form.day_end.disabled = (select.checked) ? true : false;
	document.form.month_end.disabled = (select.checked) ? true : false;
	document.form.year_end.disabled = (select.checked) ? true : false;

	document.form.session_visibility.disabled = (select.checked) ? true : false;

	document.form.session_visibility.selectedIndex = 0;

}
</script>
<?php
// End content
echo '</div>';

Display::display_footer();
?>
