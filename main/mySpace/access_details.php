<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This is the tracking library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================

* Calculates the time spent on the course
* @param integer $user_id the user id
* @param string $course_code the course code
*	Funzione scritta da Mario per testare cose
*/

// name of the language file that needs to be included
$language_file = array ('registration', 'index', 'tracking');

// including the global Dokeos file
require '../inc/global.inc.php';

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';

// the section (for the tabs)
$this_section = "session_my_space";


/* MAIN */
$user_id = Security::remove_XSS($_REQUEST['student']);
$course_code = Security::remove_XSS($_REQUEST['course']);

$connections = get_connections_to_course($user_id, $course_code);
if (api_is_xml_http_request()) {
	$type  = Security::remove_XSS($_GET['type']);
	$main_year = $main_month_year = $main_day = array();
	// get last 8 days/months
	$last_days = 8;
	$last_months = 5;
	for ($i = $last_days; $i >= 0; $i--) {
		$main_day[date ('d-m-Y', mktime () - $i * 3600 * 24)] = 0;
	}
	for ($i = $last_months; $i >= 0; $i--) {
		$main_month_year[date ('m-Y', mktime () - $i * 30 * 3600 * 24)] = 0;
	}

	$i = 0;
	if (is_array($connections) && count($connections) > 0) {
		foreach ($connections as $key => $data) {
			//creating the main array
			$main_month_year[date('m-Y', $data['login'])] += float_format(($data['logout'] - $data['login']) / 60, 0);
			$main_day[date('d-m-Y', $data['login'])] += float_format(($data['logout'] - $data['login']) / 60, 0);
			if ($i > 500) {
				break;
			}
			$i++;
		}

		switch ($type) {
			case 'day':
				$main_date = $main_day;
				break;
			case 'month':
				$main_date = $main_month_year;
				break;
			case 'year':
				$main_date = $main_year;
				break;
		}

		// the nice graphics :D
		$labels = array_keys($main_date);
		if (count($main_date) == 1) {
			$labels = $labels[0];
			$main_date = $main_date[$labels];
		}

		$data_set = new pData;
		$data_set->AddPoint($main_date, 'Q');
		if (count($main_date)!= 1) {
			$data_set->AddPoint($labels, 'Date');
		}
		$data_set->AddAllSeries();
		$data_set->RemoveSerie('Date');
		$data_set->SetAbsciseLabelSerie('Date');
		$data_set->SetYAxisName(get_lang('Minutes', ''));
		$graph_id = api_get_user_id().'AccessDetails'.api_get_course_id();
		$data_set->AddAllSeries();

		$cache = new pCache();
		// the graph id
		$data = $data_set->GetData();

		if ($cache->IsInCache($graph_id, $data_set->GetData())) {
		//if (0) {
			//if we already created the img
			//	echo 'in cache';
			$img_file = $cache->GetHash($graph_id, $data_set->GetData());
		} else {
			// if the image does not exist in the archive/ folder
			// Initialise the graph
			$test = new pChart(760, 230);

			//which schema of color will be used
			$quant_resources = count($data[0]) - 1;
			// Adding the color schemma
			$test->loadColorPalette(api_get_path(LIBRARY_PATH).'pchart/palette/default.txt');

			$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 8);
			$test->setGraphArea(70, 30, 680, 200);
			$test->drawFilledRoundedRectangle(7, 7, 693, 223, 5, 240, 240, 240);
			$test->drawRoundedRectangle(5, 5, 695, 225, 5, 230, 230, 230);
			$test->drawGraphArea(255, 255, 255, TRUE);
			$test->drawScale($data_set->GetData(), $data_set->GetDataDescription(), SCALE_START0, 150, 150, 150, TRUE, 0, 0);
			$test->drawGrid(4, TRUE, 230, 230, 230, 50);
			$test->setLineStyle(2);
			// Draw the 0 line
			$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 6);
			$test->drawTreshold(0, 143, 55, 72, TRUE, TRUE);

			if (count($main_date) == 1) {
				//Draw a graph
				echo '<strong>'.$labels.'</strong><br/>';
				$test->drawBarGraph($data_set->GetData(), $data_set->GetDataDescription(), TRUE);
			} else {
				//Draw the line graph
				$test->drawLineGraph($data_set->GetData(), $data_set->GetDataDescription());
				$test->drawPlotGraph($data_set->GetData(), $data_set->GetDataDescription(), 3, 2, 255, 255, 255);
			}

			// Finish the graph
			$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 8);

			$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 10);
			$test->drawTitle(60, 22, get_lang('AccessDetails', ''), 50, 50, 50, 585);

			//------------------
			//echo 'not in cache';
			$cache->WriteToCache($graph_id, $data_set->GetData(), $test);
			ob_start();
			$test->Stroke();
			ob_end_clean();
			$img_file = $cache->GetHash($graph_id, $data_set->GetData());
		}
		echo '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
	} else {
		Display::display_warning_message (get_lang('GraphicNotAvailable'));
	}
	exit;
}

$nameTools = get_lang('AccessDetails');

//StudentDetails
if (isset($_GET['origin']) && strcmp($_GET['origin'], 'tracking_course') === 0) {
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".Security::remove_XSS($_GET['course'])."&amp;studentlist=true&id_session=".api_get_session_id(), "name" => get_lang("Tracking"));
	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student'])."&details=true&origin=".Security::remove_XSS($_GET['origin'])."&amp;course=".Security::remove_XSS($_GET['course']).'&amp;cidReq='.Security::remove_XSS($_GET['course']), "name" => get_lang('DetailsStudentInCourse'));
	$interbreadcrumb[] = array ("url" => "javascript: void(0);", "name" => get_lang("Details"));
} elseif (isset($_GET['origin']) && strcmp($_GET['origin'], 'user_course') === 0) {
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".Security::remove_XSS($_GET['course']), "name" => get_lang("Users"));
	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student'])."&details=true&origin=".Security::remove_XSS($_GET['origin'])."&amp;course=".Security::remove_XSS($_GET['course']).'&amp;cidReq='.Security::remove_XSS($_GET['course']), "name" => get_lang('DetailsStudentInCourse'));
	$interbreadcrumb[] = array ("url" => "javascript: void(0);", "name" => get_lang("Details"));
}

//$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery-1.1.3.1.pack.js" type="text/javascript"></script>';
//$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.history_remote.pack.js" type="text/javascript"></script>';
//$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.tabs.pack.js" type="text/javascript"></script>';
//$htmlHeadXtra[] = '<link rel="stylesheet" href="../inc/lib/javascript/jquery.tabs.css" type="text/css" media="print, projection, screen">';

$htmlHeadXtra[] = '<!--[if IE]><script language="javascript" type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/excanvas.js"></script><![endif]-->';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.min.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/plugins/jqplot.barRenderer.min.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/plugins/jqplot.pointLabels.min.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/plugins/jqplot.cursor.min.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/js/jquery-ui-1.8.1.custom.min.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.css" />';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/css/overcast/jquery-ui-1.8.4.custom.css" />';

$htmlHeadXtra[] = '<script type="text/javascript">
$(function() {
	$("#container-9").tabs({ remote: true});
});

</script>';

Display :: display_tool_header('');
echo '<div class="actions">';
		echo '<a href="myStudents.php?student=' . Security :: remove_XSS($_GET['student']) . '&details=true&course=' . Security :: remove_XSS($_GET['course']) . '&amp;origin=' . Security :: remove_XSS($_GET['origin']) . '&amp;cidReq=' . Security :: remove_XSS($_GET['course']) . '">' . Display::return_icon('pixel.gif', get_lang('Report'), array('class' => 'toolactionplaceholdericon toolactionquizscores')). get_lang('Report') . '</a>';
echo '</div>';
echo '<div id="content">';
$tbl_userinfo_def = Database :: get_course_table(TABLE_USER_INFO);
$main_user_info = api_get_user_info($user_id);

$result_to_print = '';
$main_date_array = array();

foreach ($connections as $key => $data) {
	$result_to_print .= '&nbsp;&nbsp;'.date('d-m-Y (H:i:s)', $data['login']).' - '.api_time_to_hms($data['logout'] - $data['login']).'<br />'."\n";
}
api_display_tool_title(get_lang('DetailsStudentInCourse'));
echo '<div class="actions">';
echo '<strong>'.get_lang('User').': '.api_get_person_name($main_user_info['firstName'], $main_user_info['lastName']).'</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>'.get_lang('Course').': '.$course_code.'</strong></div><br/>';

?>
<div id="container-9">
    <ul>
        <li><a href="access_details.php?type=day&course=<?php echo $course_code?>&student=<?php echo $user_id?>"><span> <?php echo api_ucfirst(get_lang('Day')); ?></span></a></li>
        <li><a href="access_details.php?type=month&course=<?php echo $course_code?>&student=<?php echo $user_id?>"><span><?php echo api_ucfirst(get_lang('MinMonth')); ?></span></a></li>
    </ul>
    <?php echo '<div id="show"></div>'; ?>
</div>
<?php

echo '<div id="graph"></div><br />';
echo '<div class="actions"><strong>', get_lang('DateAndTimeOfAccess'), ' - ', get_lang('Duration'), '</strong></div><br />';
echo $result_to_print;

/* Login time against logout time
foreach ($connections as $key => $data) {
    echo ("<tr><td>".date("d-m-Y (H:i:s)", $data['login'])."</td><td>".date("d-m-Y (H:i:s)", $data['logout'])."</td></tr>");
}
*/
/*
foreach ($connections as $key => $data) {
    echo ("<tr><td>".date("d-m-Y (H:i:s)", $data['login'])."</td><td>".api_time_to_hms($data['logout'] - $data['login'])."</td></tr>");
}
echo ("</table>");
*/
echo '</div>';
Display:: display_footer();


/*
-----------------------------------------------------------
	Functions
-----------------------------------------------------------
*/

/**
 * Gets the connections to a course as an array of login and logout time
 *
 * @param unknown_type $user_id
 * @param unknown_type $course_code
 * @return unknown
 */
function get_connections_to_course($user_id, $course_code) {
	// Database table definitions
    $tbl_track_course 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
    $tbl_main			= Database :: get_main_table(TABLE_MAIN_COURSE);

    $sql_query = 'SELECT visual_code as course_code FROM '.$tbl_main.' c WHERE code="'.Database::escape_string($course_code).'";';
    $result = Database::query($sql_query, __FILE__, __LINE__);
    $row_query = Database::fetch_array($result, 'ASSOC');
    $course_true = isset($row_query['course_code']) ? $row_query['course_code']: $course_code;

    $sql = 'SELECT login_course_date, logout_course_date FROM ' . $tbl_track_course . '
    	WHERE user_id = ' . intval($user_id) . '
    	AND course_code="' . Database::escape_string($course_true) . '" ORDER BY login_course_date ASC';

    $rs = Database::query($sql, __FILE__, __LINE__);
    $connections = array();

    while ($row = Database::fetch_array($rs)) {

        $login_date = $row['login_course_date'];
        $logout_date = $row['logout_course_date'];

        $timestamp_login_date = strtotime($login_date);
        $timestamp_logout_date = strtotime($logout_date);

        $connections[] = array('login' => $timestamp_login_date, 'logout' => $timestamp_logout_date);
    }
    return $connections;
}

/**
 * TODO: Not used, to b deleted?
 * Enter description here...
 *
 * @param unknown_type $user_id
 * @param unknown_type $course_code
 * @param unknown_type $year
 * @param unknown_type $month
 * @param unknown_type $day
 * @return unknown
 */
function get_connections_to_course_by_time($user_id, $course_code, $year = '', $month = '', $day = '') {
	// Database table definitions
    $tbl_track_course 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
    $tbl_main				= Database :: get_main_table(TABLE_MAIN_COURSE);

    $sql_query = 'SELECT visual_code as course_code FROM '.$tbl_main.' c WHERE code="'.Database :: escape_string($course_code).'";';
    $result = Database::query($sql_query, __FILE__, __LINE__);
    $row_query = Database::fetch_array($result,'ASSOC');
    $course_true = isset($row_query['course_code']) ? $row_query['course_code']: $course_code;

    $sql = 'SELECT login_course_date, logout_course_date FROM ' . $tbl_track_course . '
    				WHERE user_id = ' . intval($user_id) . '
    				AND course_code="' . Database::escape_string($course_true) . '"
    				ORDER BY login_course_date DESC';

    $rs = Database::query($sql, __FILE__, __LINE__);
    $connections = array();
    while ($row = Database::fetch_array($rs)) {
        $login_date = $row['login_course_date'];
        $logout_date = $row['logout_course_date'];
        $timestamp_login_date = strtotime($login_date);
        $timestamp_logout_date = strtotime($logout_date);
        $connections[] = array('login' => $timestamp_login_date, 'logout' => $timestamp_logout_date);
    }
    return $connections;
}
