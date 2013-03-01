<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationaddsessioncategory';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once(api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');
require_once('../inc/lib/xajax/xajax.inc.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_category_list.php","name" => get_lang('ListSessionCategory'));

// Database Table Definitions
$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);


$xajax = new xajax();
$xajax -> registerFunction ('search_coachs');

$formSent=0;
$errorMsg='';

if ($_POST['formSent']) {
	$formSent=1;
	$name= $_POST['name'];
	$year_start= $_POST['year_start'];
	$month_start=$_POST['month_start'];
	$day_start=$_POST['day_start'];
	$year_end=$_POST['year_end'];
	$month_end=$_POST['month_end'];
	$day_end=$_POST['day_end'];
	$return = SessionManager::create_category_session($name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end);
	if ($return == strval(intval($return))) {
		header('Location: session_category_list.php?action=show_message&message='.urlencode(get_lang('SessionCategoryAdded')));
		exit();
	}
}
$thisYear=date('Y');
$thisMonth=date('m');
$thisDay=date('d');
$tool_name = get_lang('langAddACategory');

//display the header
Display::display_header($tool_name);

// start the content div
echo '<div id="content">';


if (!empty($return)) {
	Display::display_error_message($return,false);
}
?>
<form method="post" name="form" action="<?php echo api_get_self(); ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
<table border="0" cellpadding="5" cellspacing="0" width="550">
<tr>
  <td width="30%"><?php echo get_lang('SessionCategoryName') ?>&nbsp;&nbsp;</td>
  <td width="70%"><input type="text" name="name" size="50" class="focus" maxlength="50" value="<?php if($formSent) echo api_htmlentities($name,ENT_QUOTES,$charset); ?>"></td>
</tr>
<tr >
<td colspan="2">
	<a href="javascript://" onclick="if(document.getElementById('options').style.display == 'none'){document.getElementById('options').style.display = 'block';}else{document.getElementById('options').style.display = 'none';}"><?php echo get_lang('AddTimeLimit') ?></a>
		<div style="display: <?php if($formSent && ($nb_days_acess_before!=0 || $nb_days_acess_after!=0)) echo 'block'; else echo 'none'; ?>;" id="options">
	<br>
	<div>
<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr><td colspan="2"><?php echo get_lang('TheTimeLimitsAreReferential');?></td>
</tr>
<tr>
  <td width="20%"><?php echo get_lang('DateStart') ?>&nbsp;&nbsp;</td>
  <td width="80%">
  <select name="day_start">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisDay == 2) || ($formSent && $day_start == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisDay == 3) || ($formSent && $day_start == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisDay == 4) || ($formSent && $day_start == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisDay == 5) || ($formSent && $day_start == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisDay == 6) || ($formSent && $day_start == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisDay == 7) || ($formSent && $day_start == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisDay == 8) || ($formSent && $day_start == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisDay == 9) || ($formSent && $day_start == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisDay == 10) || ($formSent && $day_start == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisDay == 11) || ($formSent && $day_start == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisDay == 12) || ($formSent && $day_start == 12)) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if((!$formSent && $thisDay == 13) || ($formSent && $day_start == 13)) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if((!$formSent && $thisDay == 14) || ($formSent && $day_start == 14)) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if((!$formSent && $thisDay == 15) || ($formSent && $day_start == 15)) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if((!$formSent && $thisDay == 16) || ($formSent && $day_start == 16)) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if((!$formSent && $thisDay == 17) || ($formSent && $day_start == 17)) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if((!$formSent && $thisDay == 18) || ($formSent && $day_start == 18)) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if((!$formSent && $thisDay == 19) || ($formSent && $day_start == 19)) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if((!$formSent && $thisDay == 20) || ($formSent && $day_start == 20)) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if((!$formSent && $thisDay == 21) || ($formSent && $day_start == 21)) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if((!$formSent && $thisDay == 22) || ($formSent && $day_start == 22)) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if((!$formSent && $thisDay == 23) || ($formSent && $day_start == 23)) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if((!$formSent && $thisDay == 24) || ($formSent && $day_start == 24)) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if((!$formSent && $thisDay == 25) || ($formSent && $day_start == 25)) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if((!$formSent && $thisDay == 26) || ($formSent && $day_start == 26)) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if((!$formSent && $thisDay == 27) || ($formSent && $day_start == 27)) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if((!$formSent && $thisDay == 28) || ($formSent && $day_start == 28)) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if((!$formSent && $thisDay == 29) || ($formSent && $day_start == 29)) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if((!$formSent && $thisDay == 30) || ($formSent && $day_start == 30)) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if((!$formSent && $thisDay == 31) || ($formSent && $day_start == 31)) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_start">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisMonth == 2) || ($formSent && $month_start == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisMonth == 3) || ($formSent && $month_start == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisMonth == 4) || ($formSent && $month_start == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisMonth == 5) || ($formSent && $month_start == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisMonth == 6) || ($formSent && $month_start == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisMonth == 7) || ($formSent && $month_start == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisMonth == 8) || ($formSent && $month_start == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisMonth == 9) || ($formSent && $month_start == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisMonth == 10) || ($formSent && $month_start == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisMonth == 11) || ($formSent && $month_start == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisMonth == 12) || ($formSent && $month_start == 12)) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_start">

<?php
for ($i=$thisYear-5;$i <= ($thisYear+5);$i++) {
?>
	<option value="<?php echo $i; ?>" <?php if((!$formSent && $thisYear == $i) || ($formSent && $year_start == $i)) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
<?php
}
?>

  </select>
  </td>
</tr>
<tr>
  <td width="20%"><?php echo get_lang('DateEnd') ?>&nbsp;&nbsp;</td>
  <td width="80%">
  <select name="day_end">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisDay == 2) || ($formSent && $day_end == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisDay == 3) || ($formSent && $day_end == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisDay == 4) || ($formSent && $day_end == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisDay == 5) || ($formSent && $day_end == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisDay == 6) || ($formSent && $day_end == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisDay == 7) || ($formSent && $day_end == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisDay == 8) || ($formSent && $day_end == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisDay == 9) || ($formSent && $day_end == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisDay == 10) || ($formSent && $day_end == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisDay == 11) || ($formSent && $day_end == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisDay == 12) || ($formSent && $day_end == 12)) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if((!$formSent && $thisDay == 13) || ($formSent && $day_end == 13)) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if((!$formSent && $thisDay == 14) || ($formSent && $day_end == 14)) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if((!$formSent && $thisDay == 15) || ($formSent && $day_end == 15)) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if((!$formSent && $thisDay == 16) || ($formSent && $day_end == 16)) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if((!$formSent && $thisDay == 17) || ($formSent && $day_end == 17)) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if((!$formSent && $thisDay == 18) || ($formSent && $day_end == 18)) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if((!$formSent && $thisDay == 19) || ($formSent && $day_end == 19)) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if((!$formSent && $thisDay == 20) || ($formSent && $day_end == 20)) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if((!$formSent && $thisDay == 21) || ($formSent && $day_end == 21)) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if((!$formSent && $thisDay == 22) || ($formSent && $day_end == 22)) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if((!$formSent && $thisDay == 23) || ($formSent && $day_end == 23)) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if((!$formSent && $thisDay == 24) || ($formSent && $day_end == 24)) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if((!$formSent && $thisDay == 25) || ($formSent && $day_end == 25)) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if((!$formSent && $thisDay == 26) || ($formSent && $day_end == 26)) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if((!$formSent && $thisDay == 27) || ($formSent && $day_end == 27)) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if((!$formSent && $thisDay == 28) || ($formSent && $day_end == 28)) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if((!$formSent && $thisDay == 29) || ($formSent && $day_end == 29)) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if((!$formSent && $thisDay == 30) || ($formSent && $day_end == 30)) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if((!$formSent && $thisDay == 31) || ($formSent && $day_end == 31)) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_end">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisMonth == 2) || ($formSent && $month_end == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisMonth == 3) || ($formSent && $month_end == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisMonth == 4) || ($formSent && $month_end == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisMonth == 5) || ($formSent && $month_end == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisMonth == 6) || ($formSent && $month_end == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisMonth == 7) || ($formSent && $month_end == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisMonth == 8) || ($formSent && $month_end == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisMonth == 9) || ($formSent && $month_end == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisMonth == 10) || ($formSent && $month_end == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisMonth == 11) || ($formSent && $month_end == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisMonth == 12) || ($formSent && $month_end == 12)) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_end">

<?php
for ($i=$thisYear-5;$i <= ($thisYear+5);$i++) {
?>
	<option value="<?php echo $i; ?>" <?php if((!$formSent && ($thisYear+1) == $i) || ($formSent && $year_end == $i)) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
<?php
}
?>
  </select>
  </td>
</tr>

</table>
</div>
<br>
</div>
</td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><button class="save" type="submit" value="<?php echo get_lang('langAddACategory') ?>"><?php echo get_lang('langAddACategory') ?></button>
 </td>
</tr>

</table>

</form>
<script type="text/javascript">
function setDisable(select) {
	document.form.day_start.disabled = (select.checked) ? true : false;
	document.form.month_start.disabled = (select.checked) ? true : false;
	document.form.year_start.disabled = (select.checked) ? true : false;
	document.form.day_end.disabled = (select.checked) ? true : false;
	document.form.month_end.disabled = (select.checked) ? true : false;
	document.form.year_end.disabled = (select.checked) ? true : false;
}
</script>
<?php
// close the content div
echo '</div>';

// display the footer
Display::display_footer();
?>
